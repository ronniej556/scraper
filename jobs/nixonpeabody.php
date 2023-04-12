<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$base_url = 'https://www.nixonpeabody.com';
$spider_name = 'nixonpeabody';
$firm_name = 'Nixon Peabody International LLC';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $fullAddress = '';
    $primaryAddress = '';

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    //var_dump($pData);
    //var_dump($row);

    $values = array();

    foreach($html->find('a') as $link)
    {
        if(strpos(strtolower($link->href), 'linkedin') !== false)
        {
            $linkedIn = $link->href;
            break;
        }
    }

    foreach($html->find('a') as $link)
    {
        if(strpos(strtolower($link->href), 'vcard') !== false)
        {
            $values['vCard'] = $base_url.$link->href;
            break;
        }
    }

    if(empty($linkedIn)) { $linkedIn = ''; }

    $values['names'] = json_encode(explode(' ', $pData['itemtitle_t']));

    $values['phone_numbers'] = json_encode(array($pData['officephone_s']));

    $values['email'] = $pData['email_t'];

    $fullAddress = '';

    $education = array();
    foreach($html->find('.center.formatted-type') as $item)
    {
        if(strpos(strtolower($item->innertext), 'education') !== false)
        {
            foreach($item->find('p') as $item)
            {
                $education = explode('<br />', $item->innertext);
            }
        }
    }

    $bar_admissions = array();
    $court_admissions = array();

    foreach($html->find('.center.formatted-type') as $item)
    {
        if(strpos(strtolower($item->innertext), 'admitted') !== false)
        {
            $items = explode('<br />', $item->find('p', 0)->innertext);
            foreach($items as $value)
            {
                if(strpos(strtolower($value), 'court'))
                {
                    $court_admissions[] = trim($value);
                }
                else
                {
                    $bar_admissions[] = trim($value);
                }
            }
        }
    }

    $practice_areas = array();
    $i = 0;
    foreach($html->find('a') as $key => $item)
    {
        if(strpos($item->href, '/capabilities/practices/') !== false)
        {
            if($i>16)
            {
                $practice_areas[] = trim($item->plaintext);
            }
            $i++;
        }
    }

    $languages = array();
    foreach($html->find('#bio_languages') as $item)
    {
        if(strpos($item->innertext, 'Languages') !== false)
        {
            foreach($item->find('li') as $item)
            {
                $languages[] = trim(preg_replace('/\s+/', ' ', $item->plaintext));
            }
        }
    }

    if(count($languages)<1)
    {
        $languages[] = 'English';
    }

    $positions = json_encode(array(str_replace(',  ', '', $pData['biotitle_t'])));

    foreach($html->find('div') as $item)
    {
        if(strpos($item->class, 'bioDetail_introWrapper_') !== false)
        {
            $values['description'] = trim(str_replace('Introduction', '', $item->plaintext));
        }
    }

    $pData['image'] = $base_url.$pData['imageurl_s'];

    $photo = $pData['image'];
    $thumb = $pData['image'];

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

    if(!empty($values['vCard']))
    {
        $f = fetch($values['vCard']);

        file_put_contents($spider_name.'_temp.vcf', $f);
        $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

        if(isset($vCard->adr[0]))
        {
            if(!empty($vCard->adr[0]['StreetAddress'])) { $fullAddress = $vCard->adr[0]['StreetAddress']; }
            if(!empty($vCard->adr[0]['Locality'])) { $fullAddress .= ', '.$vCard->adr[0]['Locality']; }
            if(!empty($vCard->adr[0]['Region'])) { $fullAddress .= ', '.$vCard->adr[0]['Region']; }
            if(!empty($vCard->adr[0]['PostalCode'])) { $fullAddress .= ', '.$vCard->adr[0]['PostalCode']; }
            if(!empty($vCard->adr[0]['Country'])) { $fullAddress .= ', '.$vCard->adr[0]['Country']; }
            $primaryAddress = @$vCard->adr[0]['Locality'];
        }
        else
        {
            if(!empty($vCard->adr['StreetAddress'])) { $fullAddress = $vCard->adr['StreetAddress']; }
            if(!empty($vCard->adr['Locality'])) { $fullAddress .= ', '.$vCard->adr['Locality']; }
            if(!empty($vCard->adr['Region'])) { $fullAddress .= ', '.$vCard->adr['Region']; }
            if(!empty($vCard->adr['PostalCode'])) { $fullAddress .= ', '.$vCard->adr['PostalCode']; }
            if(!empty($vCard->adr['Country'])) { $fullAddress .= ', '.$vCard->adr['Country']; }
            $primaryAddress = @$vCard->adr['Locality'];
        }

    }
    else
    {
        $values['vCard'] = '';
    }

    if(empty($primaryAddress))
    {
        foreach($html->find('.sidebar__widget-wrap a') as $link)
        {
            if(strpos($link, 'offices') !== false)
            {
                $primaryAddress = $link->plaintext;
                break;
            }
        }
    }

    $q = $pdo->prepare('INSERT INTO `people` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute(array(
        $values['names'],
        $values['email'],
        @$values['vCard'],
        @sEncode($fullAddress),
        @sEncode($primaryAddress),
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
        json_encode($languages),
        $row['url'],
        sEncode(trim(strip_tags($values['description']))),
        time(),
        $thumb,
        $photo,
        $spider_name,
        $firm_name,
        $law_school,
        $jd_year,
        NULL
    ));

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