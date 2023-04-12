<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'dinsmore';
$firm_name = 'Dinsmore &amp; Shohl LLP';
$base_url = 'https://www.dinsmore.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $fullAddress = '';
    $primaryAddress = '';

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    $vCard_link = $html->find('a.vcard', 0)->href;

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

        if(!empty($vCard->adr['StreetAddress'])) { $fullAddress = $vCard->adr['StreetAddress']; }

        if(!empty($vCard->adr['Locality'])) {
            $fullAddress .= ', '.$vCard->adr['Locality'];
            $primaryAddress = $vCard->adr['Locality'];
        }

        if(!empty($vCard->adr['Region'])) {
            $fullAddress .= ', '.$vCard->adr['Region'];
            $primaryAddress .= ', '.$vCard->adr['Region'];
        }

        if(!empty($vCard->adr['PostalCode'])) { $fullAddress .= ', '.$vCard->adr['PostalCode']; }

        if(!empty($vCard->adr['Country'])) { $fullAddress .= ', '.$vCard->adr['Country']; }

        $education = array();
        if($html->find('ul.education-list', 0))
        {
            $list = $html->find('ul.education-list', 0);
            foreach($list->find('li') as $item)
            {
                $item_ = $item->innertext;
                if(strpos($item_, '<ul>') !== false)
                {
                    $education[] = trim(strip_tags(explode('<ul>', $item_)[0]));
                }
                else
                {
                    $education[] = trim(strip_tags($item_));
                }
            }
        }

        $bar_admissions = array();
        foreach($html->find('.bio-info-entry') as $item)
        {
            if($item->find('.bio-info-heading', 0)->plaintext == 'Bar Admissions')
            {
                $list = $item->find('ul', 0);
                foreach($list->find('li') as $item)
                {
                    $bar_admissions[] = trim($item->plaintext);
                }
            }
        }

        $court_admissions = array();
        foreach($html->find('li span.bar-admission') as $item)
        {
            if(strpos(strtolower($item->plaintext), 'court'))
            {
                $court_admissions[] = $item->plaintext;
            }
        }

        $memberships = array();
        foreach($html->find('.bio-info-entry') as $item)
        {
            if($item->find('.bio-info-heading', 0)->plaintext == 'Affiliations/Memberships')
            {
                if($item->find('ul', 0))
                {
                    $list = $item->find('ul', 0);
                    foreach($list->find('li') as $item)
                    {
                        $memberships[] = trim($item->plaintext);
                    }
                }
            }
        }

        $languages = array();
        $languages[] = 'English';

        $practice_areas = array();

        if($html->find('.associated-practices-widget', 0))
        {
            $list = $html->find('.associated-practices-widget', 0);
            foreach($list->find('li') as $item)
            {
                $practice_areas[] = trim($item->plaintext);
            }
        }

        $positions = array();
        $positions[] = $pData['title'];

        $values['description'] = trim($html->find('.description', 0)->plaintext);

        foreach($education as $value)
        {
            $school = strtolower(preg_replace('/[^A-Za-z0-9\-]/', ' ', $value));
            if(strpos($school, 'jd') !== false || strpos($school, 'doctor') !== false)
            {
                $law_school = $value;
                break;
            }
        }

        if(empty($law_school))
        {
            $law_school = '';
        }

        $jd_year = (int) @filter_var(html_entity_decode($law_school), FILTER_SANITIZE_NUMBER_INT);

        $q = $pdo->prepare('INSERT INTO `people` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute(array(
            $values['names'],
            $values['email'],
            $values['vCard'],
            $fullAddress,
            $primaryAddress,
            'https://www.linkedin.com/company/dinsmore-&-shohl-llp/',
            $values['phone_numbers'],
            '',
            json_encode($education),
            json_encode($bar_admissions), //bar admissions
            json_encode($court_admissions), //court admissions
            json_encode($practice_areas),
            '[]',
            json_encode($memberships),
            json_encode($positions),
            json_encode($languages),
            $row['url'],
            $values['description'],
            time(),
            $pData['image'],
            $pData['image'],
            $spider_name,
            $firm_name,
            $law_school,
            str_replace('-', '', $jd_year),
            NULL
        ));
    }

    $q = $pdo->prepare('UPDATE `queue` SET `status`=\'complete\' WHERE `id`=?');
    $q->execute(array($row['id']));

    unset($values);
    unset($law_school);
    unset($jd_year);
    unset($fullAddress);
    unset($primaryAddress);

}

@unlink($spider_name.'_temp.vcf');
exit();
?>