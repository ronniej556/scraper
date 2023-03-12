<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'blankrome';
$firm_name = 'Blank Rome LLP';
$base_url = 'https://www.blankrome.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    $values = array();

    foreach($html->find('.list-links li a') as $item)
    {
        if(strpos($item->href, '/vcard') !== false)
        {
            $vCard_url = $base_url.$item->href;
            break;
        }
    }

    if($vCard_url)
    {

        $values['names'] = json_encode(explode(' ', $pData[0]));
        $values['email'] = $pData[4];
        $values['vCard'] = $vCard_url;
        $values['phone_numbers'] = json_encode(array($pData[5]));

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
            $state = $vCard->adr['Region']; } else { $state = $html->find('.list-unbulleted li a', 0)->plaintext;
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
        if($html->find('#credentials ul', 1))
        {
            $list = $html->find('#credentials ul', 1);
            foreach($list->find('li') as $item)
            {
                $education[] = trim(preg_replace('/\s+/', ' ', $item->plaintext));
            }
        }

        $bar_admissions = array();
        $court_admissions = array();

        if($html->find('#credentials ul', 0))
        {
            $list = $html->find('#credentials ul', 0);
            foreach($list->find('li') as $item)
            {
                $text = trim($item->plaintext);
                if(strpos(strtolower($text), 'court') !== false)
                {
                    $court_admissions[] = $text;
                }
                else
                {
                    $bar_admissions[] = $text;
                }
            }
        }

        $practice_areas = array();
        if($html->find('.content-main .box ul', 0))
        {
            $list = $html->find('.content-main .box ul', 0);
            foreach($list->find('li') as $item)
            {
                $practice_areas[] = trim($item->plaintext);
            }
        }

        $positions = json_encode(array($pData[3]));

        $desc = $html->find('.content-main .grid div', 0)->innertext;
        $desc = explode('<div class="h4 margin-compact sub">Share This</div>', $desc)[0];

        $values['description'] = trim(strip_tags($desc));

        $photo = $pData[2];
        $thumb = $pData[2];

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
            $positions,
            json_encode(array('English')),
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