<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'akingump';
$firm_name = 'Akin Gump Strauss Hauer &amp; Feld LLP';
$base_url = 'https://www.akingump.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    $values = array();

    if(isset($pData['name']))
    {

        $values['names'] = json_encode(explode(' ', $pData['name']));
        $values['email'] = $pData['email'];
        $values['vCard'] = $row['url'].'/vcard.vcf';

        file_put_contents($spider_name.'_temp.vcf', file_get_contents($values['vCard']));
        $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

        $values['phone_numbers'] = json_encode(array($pData['offices_info'][0]['repeater_module_office']['phone']));

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

        foreach($html->find('.ms-4.me-4 .u-hide-in-print') as $item)
        {
            if(@$item->find('h3 button span', 0)->plaintext == 'Education')
            {
                $list = $item->find('ul', 0);
                foreach($list->find('li') as $item)
                {
                    $education[] = trim($item->plaintext);
                }
            }
        }

        $languages = array();
        foreach($html->find('.ms-4.me-4 .u-hide-in-print') as $item)
        {
            if(@$item->find('h3 button span', 0)->plaintext == 'Languages')
            {
                $list = $item->find('ul', 0);
                foreach($list->find('li') as $item)
                {
                    $languages[] = trim($item->plaintext);
                }
            }
        }

        if(count($languages)<1) { $languages[] = 'English'; }

        $bar_admissions = array();
        $court_admissions = array();

        foreach($html->find('.ms-4.me-4 .u-hide-in-print') as $item)
        {
            if(@$item->find('h3 button span', 0)->plaintext == 'Bar Admissions')
            {
                $list = $item->find('ul', 0);
                foreach($list->find('li') as $item)
                {
                    $bar_admissions[] = trim($item->plaintext);
                }
            }
        }

        $practice_areas = array();
        foreach($html->find('.container') as $item)
        {
            if(@$item->find('h3', 0)->plaintext == 'Areas of Focus')
            {
                if($item->find('ul', 1))
                {
                    $list = $item->find('ul', 1);
                    foreach($list->find('li') as $item)
                    {
                        $practice_areas[] = trim($item->plaintext);
                    }
                }
            }
        }

        $positions = array();
        $positions[] = $pData['content_data']['position']['name'];

        $values['description'] = trim(str_replace('Biography ', '', $html->find('.container.pb-5.pb-print-0', 0)->plaintext));

        $photo = $base_url.$pData['attorney_card_photo_url'];
        $thumb = $base_url.$pData['attorney_card_photo_url'];

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
            $law_school = $education[0];
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
            $thumb,
            $photo,
            $spider_name,
            $firm_name,
            $law_school,
            $jd_year,
            NULL
        ));
    }

    $q = $pdo->prepare('UPDATE `queue` SET `status`=\'complete\' WHERE `id`=?');
    $q->execute(array($row['id']));

}

@unlink($spider_name.'_temp.vcf');
exit();
?>