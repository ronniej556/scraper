<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'hoganlovells';
$firm_name = 'Hogan Lovells US LLP';
$base_url = 'https://www.hoganlovells.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    foreach($html->find('.flip-list a') as $item)
    {
        if(strpos($item->href, '/vcard') !== false)
        {
            $vCard_link = $base_url.$item->href;
        }
    }

    if(!empty($vCard_link))
    {

        $values['names'] = json_encode(explode(' ', $pData['name']));
        $values['email'] = $pData['email'];
        $values['vCard'] = $vCard_link;

        file_put_contents($spider_name.'_temp.vcf', file_get_contents($values['vCard']));
        $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

        if(isset($vCard->tel[0]['Value']))
        {
            $values['phone_numbers'] = json_encode(array($vCard->tel[0]['Value']));
        }
        else
        {
            $values['phone_numbers'] = json_encode(array($pData['phone']));
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

        if(!empty($vCard->adr['Country']))
        {
            $country = $vCard->adr['Country']; } else { $country = '';
        }

        if(!empty($vCard->adr['PostalCode']))
        {
            $postalCode = $vCard->adr['PostalCode']; } else { $postalCode = '';
        }

        if(!is_numeric($postalCode))
        {
            $postalCode = '';
        }

        $education = array();
        if($html->find('.education .bio-portfolio', 0))
        {
            $list = $html->find('.education .bio-portfolio', 0);
            foreach($list->find('p') as $item)
            {
                $education[] = trim(strip_tags($item->plaintext));
            }
        }

        unset($education[0]);

        $bar_admissions = array();
        if($html->find('.education .bio-portfolio', 1))
        {
            $list = $html->find('.education .bio-portfolio', 1);
            foreach($list->find('p') as $item)
            {
                $bar_admissions[] = trim(strip_tags($item->plaintext));
            }
        }

        unset($bar_admissions[0]);

        $court_admissions = array();

        $memberships = array();

        $languages = array();
        $languages[] = 'English';

        $practice_areas = array();

        if($html->find('.relatedCapabilities .tags', 0))
        {
            $list = $html->find('.relatedCapabilities .tags', 0);
            foreach($list->find('.tag') as $item)
            {
                $practice_areas[] = trim($item->plaintext);
            }
        }

        $positions = array();
        $positions[] = $pData['title'];

        $values['description'] = trim($html->find('div.intro', 0)->plaintext);

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
            json_encode(array_values($education)),
            json_encode(array_values($bar_admissions)), //bar admissions
            json_encode(array_values($court_admissions)), //court admissions
            json_encode($practice_areas),
            '[]',
            json_encode($memberships),
            json_encode($positions),
            json_encode($languages),
            $row['url'],
            $values['description'],
            time(),
            $base_url.$html->find('.biography-photo img', 0)->src,
            $base_url.$html->find('.biography-photo img', 0)->src,
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