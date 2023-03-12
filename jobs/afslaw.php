<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'afslaw';
$firm_name = 'ArentFox Schiff';
$base_url = 'https://www.afslaw.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $data = fetch($row['url']);
    $html = str_get_html($data);

    foreach($html->find('a') as $link)
    {
        if(strtolower($link->plaintext) == 'vcard')
        {
            $vCard_link = $link->href;
            break;
        }
    }

    if(!empty($vCard_link))
    {

        $values['names'] = json_encode(explode(' ', trim($html->find('h1', 0)->plaintext)));
        $values['vCard'] = $vCard_link;
        $values['email'] = str_replace('mailto:', '', $html->find('.field--name-field-email a', 0)->href);

        file_put_contents($spider_name.'_temp.vcf', file_get_contents($values['vCard']));
        $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

        if(isset($vCard->tel[0]['Value']))
        {
            $values['phone_numbers'] = json_encode(array($vCard->tel[0]['Value']));
        }
        else
        {
            $values['phone_numbers'] = json_encode(array($html->find('.field--name-field-phone a', 0)->plaintext));
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

        $education = array();
        if($html->find('.field--name-field-group-education', 0))
        {
            $list = $html->find('.field--name-field-group-education', 0);
            foreach($list->find('.field__item') as $item)
            {
                $education[] = trim(preg_replace('/\s+/', ' ',$item->plaintext));
            }
        }

        $bar_admissions = array();
        foreach($html->find('.views-field-field-bar-admissions li') as $item)
        {
            $bar_admissions[] = trim($item->plaintext);
        }

        $court_admissions = array();
        foreach($html->find('.views-field-field-court-admissions li') as $item)
        {
            $court_admissions[] = trim($item->plaintext);
        }

        $languages = array();
        $languages[] = 'English';

        $practice_areas = array();

        if($html->find('.view-bio-industries-and-services', 0))
        {
            $list = $html->find('.view-bio-industries-and-services', 0);
            foreach($list->find('.field-content a') as $item)
            {
                $practice_areas[] = trim($item->plaintext);
            }
        }

        $positions = array();

        if($html->find('.field--name-field-job-title', 0)->plaintext)
        {
            $positions[] = $html->find('.field--name-field-job-title', 0)->plaintext;
        }
        else
        {
            $positions[] = '';
        }

        $values['description'] = trim($html->find('.field--name-field-intro.field--type-string-long', 0)->plaintext);

        $image = $base_url.$html->find('.field--name-field-image img', 0)->src;

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
            json_encode($languages),
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