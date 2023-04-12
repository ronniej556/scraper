<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$base_url = 'https://www.coleschotz.com';
$spider_name = 'coleschotz';
$firm_name = 'Cole Schotz PC';

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
        if(strpos(strtolower($link->href), 'linkedin') !== false)
        {
            $linkedIn = $link->href;
            break;
        }
    }

    if(empty($linkedIn)) { $linkedIn = ''; }

    $values['names'] = json_encode(explode(' ', $pData['name']));

    $values['email'] = $pData['email'];

    $values['phone_numbers'] = json_encode(array($pData['phone']));

    $fullAddress = '';

    $education = array();
    $education[] = trim(preg_replace('/\s+/', ' ', $html->find('.professionals-education-honors__copy' , 0)->plaintext));

    $bar_admissions = array();
    $court_admissions = array();

    foreach(explode(', ', trim(preg_replace('/\s+/', ' ', $html->find('.professionals-education-honors__copy' , 1)->plaintext))) as $item)
    {
        if(strpos(strtolower($item), 'court'))
        {
            $court_admissions[] = $item;
        }
        else
        {
            $bar_admissions[] = $item;
        }
    }

    $practice_areas = array();
    if($html->find('.link-list ul', 0))
    {
        $ul = $html->find('.link-list ul', 0);

        foreach($ul->find('li') as $item)
        {
            $practice_areas[] = sEncode(trim(preg_replace('/\s+/', ' ', $item->plaintext)));
        }
    }

    $positions = json_encode(array($pData['position']));

    if($html->find('.leading-paragraph', 0))
    {
        $values['description'] = $html->find('.leading-paragraph', 0)->plaintext;
    }

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

    foreach($html->find('a') as $link)
    {
        if(strpos(strtolower($link), 'vcard') !== false)
        {
            $values['vCard'] = $link->href;
        }
    }

    if(!empty($values['vCard']))
    {
        $f = fetch($values['vCard']);

        file_put_contents($spider_name.'_temp.vcf', $f);
        $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

        if(!empty($vCard->adr['StreetAddress'])) { $fullAddress = $vCard->adr['StreetAddress']; }
        if(!empty($vCard->adr['Locality'])) { $fullAddress .= ', '.$vCard->adr['Locality']; }
        if(!empty($vCard->adr['Region'])) { $fullAddress .= ', '.$vCard->adr['Region']; }
        if(!empty($vCard->adr['PostalCode'])) { $fullAddress .= ', '.$vCard->adr['PostalCode']; }
        if(!empty($vCard->adr['Country'])) { $fullAddress .= ', '.$vCard->adr['Country']; }

        $primaryAddress = $vCard->adr['Region'];

    }
    else
    {
        $values['vCard'] = '';
    }

    $q = $pdo->prepare('INSERT INTO `people` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute(array(
        $values['names'],
        $values['email'],
        @$values['vCard'],
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
        sEncode($values['description']),
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