<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$base_url = 'https://www.bsfllp.com';
$spider_name = 'bsfllp';
$firm_name = 'Boies Schiller Flexner LLP';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $fullAddress = '';
    $primaryAddress = '';

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    $values = array();

    foreach($html->find('a') as $link)
    {
        if(strpos(strtolower($link), 'vcard') !== false)
        {
            $values['vCard'] = $base_url.$link->href;
        }
    }

    if(isset($values['vCard']))
    {

        foreach($html->find('a') as $link)
        {
            if(strpos(strtolower($link->href), 'linkedin') !== false)
            {
                $linkedIn = $link->href;
                break;
            }
        }
        if(empty($linkedIn)) { $linkedIn = ''; }

        $values['names'] = json_encode(explode(' ', $html->find('h1', 0)->plaintext));
        $values['email'] = $pData['email'];
        $values['phone_numbers'] = json_encode(array($pData['office_1_phone']));

        $f = fetch($values['vCard']);

        if(!empty($f))
        {
            file_put_contents($spider_name.'_temp.vcf', $f);
            $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

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

            if(empty($primaryAddress))
            {
                $primaryAddress = '';
            }

            $education = array();
            foreach($html->find('.accordion-sidebar.js-accordion') as $item)
            {
                if(strpos($item->innertext, 'Education') !== false)
                {
                    foreach($item->find('li') as $item)
                    {
                        $education[] = trim($item->plaintext);
                    }
                }
            }

            $bar_admissions = array();
            $court_admissions = array();

            foreach($html->find('.accordion-sidebar.js-accordion') as $item)
            {
                if(strpos($item->innertext, 'Admissions') !== false)
                {
                    foreach($item->find('li') as $item)
                    {
                        if(strpos(strtolower($item->plaintext), 'court'))
                        {
                            $court_admissions[] = $item->plaintext;
                        }
                        else
                        {
                            $bar_admissions[] = $item->plaintext;
                        }
                    }
                }
            }

            $practice_areas = array();
            foreach($html->find('.accordion-sidebar.js-accordion') as $item)
            {
                if(strpos($item->innertext, 'Practices') !== false)
                {
                    foreach($item->find('li a') as $item)
                    {
                        $practice_areas[] = trim($item->plaintext);
                    }
                }
            }

            $positions = json_encode(array($pData['position_data']['name']));

            $values['description'] = $pData['main_content'];

            $photo = $base_url.$pData['asset_url'];
            $thumb = $base_url.$pData['asset_url'];

            foreach($education as $value)
            {
                $school = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $value));
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

            $jd_year = (int) @filter_var($law_school, FILTER_SANITIZE_NUMBER_INT);

            if(empty($values['description']))
            {
                $values['description'] = '';
            }

            $q = $pdo->prepare('INSERT INTO `people` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $q->execute(array(
                $values['names'],
                $values['email'],
                $values['vCard'],
                $fullAddress,
                $primaryAddress,
                $linkedIn,
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