<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$base_url = 'https://www.cozen.com';
$spider_name = 'cozen';
$firm_name = 'Cozen OConnor';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $fullAddress = '';
    $primaryAddress = '';

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    $pData['image'] = $base_url.$pData['Photo'];

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
            //$values['vCard'] = $link->href;
            //break;
        }
    }

    $values['vCard'] = $base_url.'/Utilities/vCard.ashx?rebuilcache=true&NodeGuid='.$pData['vCard'];

    if(empty($linkedIn)) { $linkedIn = ''; }

    $values['names'] = json_encode(explode(' ', str_replace(', ', ' ', $pData['name'])));

    $values['phone_numbers'] = json_encode(array($pData['Phone']));

    $values['email'] = $pData['Email'];

    $fullAddress = '';

    $education = array();
    foreach($html->find('#EducationContainer') as $item)
    {
        if(strpos($item->innertext, 'Education') !== false)
        {
            foreach($item->find('li') as $item)
            {
                $education[] = trim(preg_replace('/\s+/', ' ', $item->plaintext));
            }
        }
    }

    $bar_admissions = array();
    $court_admissions = array();

    foreach($html->find('#Admissions') as $item)
    {
        if(strpos($item->innertext, ' ') !== false)
        {
            foreach($item->find('li') as $item)
            {
                $bar_admissions[] = trim($item->plaintext);
            }
        }
    }

    foreach($html->find('#CourtAdmissions') as $item)
    {
        if(strpos($item->innertext, ' ') !== false)
        {
            foreach($item->find('li') as $item)
            {
                $court_admissions[] = trim($item->plaintext);
            }
        }
    }

    $practice_areas = array();
    foreach($html->find('#RelatedPractices') as $item)
    {
        if(strpos($item->innertext, ' ') !== false)
        {
            foreach($item->find('.summary a') as $item)
            {
                $practice_areas[] = trim($item->plaintext);
            }
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

    $positions = json_encode(array(str_replace(',  ', '', $pData['Title'])));

    $values['description'] = trim($html->find('#MainContent p', 0)->plaintext);

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
        $primaryAddress = $pData['fields']['Offices'][0]['Office']['fields']['Name']['value'];
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