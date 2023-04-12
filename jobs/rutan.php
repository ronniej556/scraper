<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'rutan';
$firm_name = 'Rutan &amp; Tucker, LLP';
$base_url = 'https://www.rutan.com';

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
        if(strpos(strtolower($link->href), 'vcard') !== false)
        {
            $vCard_link = $base_url.$link->href;
            break;
        }
    }

    if(!empty($vCard_link))
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

        $values['names'] = json_encode(explode(' ', $pData[0]));
        $values['email'] = $pData[4];
        $values['vCard'] = $vCard_link;

        file_put_contents($spider_name.'_temp.vcf', file_get_contents($values['vCard']));
        $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

        if(isset($vCard->tel[0]['Value']))
        {
            $values['phone_numbers'] = json_encode(array($vCard->tel[0]['Value']));
        }
        else
        {
            $values['phone_numbers'] = json_encode(array($pData[5]));
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
        if($html->find('ul.staff-education', 0))
        {
            $list = $html->find('ul.staff-education', 0);
            foreach($list->find('li') as $item)
            {
                if(strpos($item->innertext, '<ul>') !== false)
                {
                    $education[] = trim(explode('<ul>', $item->innertext)[0]);
                }
                else
                {
                    $education[] = trim($item->plaintext);
                }
            }
        }

        $bar_admissions = array();
        $court_admissions = array();

        if($html->find('ul.staff-bca', 0))
        {
            $list = $html->find('ul.staff-bca', 0);
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

        if($html->find('ul.staff-services', 0))
        {
            $list = $html->find('ul.staff-services', 0);
            foreach($list->find('li') as $item)
            {
                if(strpos($item->innertext, '<ul>') !== false)
                {
                    $practice_areas[] = trim(explode('<ul>', $item->innertext)[0]);
                }
                else
                {
                    $practice_areas[] = trim($item->plaintext);
                }
            }
        }

        $positions = array();
        $positions[] = $pData[3];

        $values['description'] = trim(str_replace(array('Overview', '+'), '', $html->find('#tab-id-0', 0)->plaintext));

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

        $jd_year = (int) @filter_var($law_school, FILTER_SANITIZE_NUMBER_INT);

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
            json_encode($positions),
            json_encode(array('English')),
            $row['url'],
            $values['description'],
            time(),
            $pData[2],
            $pData[2],
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