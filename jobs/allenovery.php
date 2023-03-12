<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'allenovery';
$firm_name = 'Allen & Overy';
$base_url = 'https://www.allenovery.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    $values = array();
    
    if($html->find('a.download', 0)->href)
    {

        $values['names'] = json_encode(explode(' ', $pData['fullName']));
        $values['email'] = str_replace('mailto:', '', $html->find('a.mail', 0)->href);
        $values['vCard'] = $base_url.$html->find('a.download', 0)->href;

        file_put_contents($spider_name.'_temp.vcf', file_get_contents($values['vCard']));
        $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

        if(isset($vCard->tel[0]['Value']))
        {
            $values['phone_numbers'] = json_encode(array(str_replace('tel:', '', $vCard->tel[0]['Value'])));
        }
        else
        {
            $values['phone_numbers'] = json_encode(array($pData[5]));
        }

        if(!empty($vCard->adr['StreetAddress']))
        {
            $address = $vCard->adr['StreetAddress']; } else { $address = '';
        }

        if(!empty($vCard->adr['Locality']))
        {
            $city = $vCard->adr['Locality']; } else { $city = '';
        }

        if(!empty($vCard->adr['Region']))
        {
            $state = $vCard->adr['Region']; } else { $state = '';
        }

        if(!empty($vCard->adr['PostalCode']))
        {
            $postalCode = $vCard->adr['PostalCode']; } else { $postalCode = '';
        }

        if(!empty($vCard->adr['Country']))
        {
            $country = $vCard->adr['Country']; } else { $country = '';
        }

        $education = array();
        $education[] = trim($html->find('.rte-content', 2)->plaintext);

        $bar_admissions = array();
        $court_admissions = array();

        $practice_areas = array();

        foreach($html->find('.section-right__section') as $item)
        {
            if($item->find('h4', 0)->plaintext == 'Practices')
            {
                $data_ = str_get_html(get_string_between($item->innertext, '<h4 class="uppercase-heading">Practices</h4>', '<h4 class="uppercase-heading">Sectors</h4>'));
                foreach($data_->find('a') as $area)
                {
                    $practice_areas[] = $area->plaintext;
                }
            }
        }

        $practice_areas = array_unique($practice_areas);

        $positions = array($pData['jobTitle']);

        if(isset($pData['imageUrl']))
        {
            $image = $base_url.$pData['imageUrl'];
        }
        else
        {
            $image = '';
        }

        $values['description'] = trim($html->find('.intro-text', 0)->plaintext.' '.$html->find('.content.rte-content', 0)->plaintext);

        foreach($education as $item)
        {
            if(strpos(preg_replace('/[^A-Za-z0-9\-]/', '', $item), 'JD') !== false)
            {
                $law_school = $item;
                break;
            }
        }

        if(empty($law_school))
        {
            $law_school = '';
        }

        $jd_year = (int) @filter_var($law_school, FILTER_SANITIZE_NUMBER_INT);

        $q = $pdo->prepare('INSERT INTO `people` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute(array(
            $values['names'],
            $values['email'],
            $address,
            $city,
            $state,
            $postalCode,
            $country,
            $values['vCard'],
            '',
            $values['phone_numbers'],
            '',
            json_encode($education),
            json_encode($bar_admissions), //bar admissions
            json_encode($court_admissions), //court admissions
            json_encode($practice_areas),
            '[]',
            '[]',
            json_encode($positions),
            json_encode(array('English')),
            $row['url'],
            $values['description'],
            time(),
            $image,
            $image,
            $spider_name,
            $firm_name,
            $law_school,
            str_replace('-', '', $jd_year),
            NULL
        ));
    }

    $q = $pdo->prepare('UPDATE `queue` SET `status`=\'complete\' WHERE `id`=?');
    $q->execute(array($row['id']));

}

@unlink($spider_name.'_temp.vcf');
exit();
?>