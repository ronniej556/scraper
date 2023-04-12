<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'littler';
$firm_name = 'Littler Mendelson P.C.';
$base_url = 'https://www.littler.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $fullAddress = '';
    $primaryAddress = '';

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

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
        }

        if(!empty($vCard->adr['Region'])) {
            $fullAddress .= ', '.$vCard->adr['Region'];
        }

        if(!empty($vCard->adr['PostalCode'])) { $fullAddress .= ', '.$vCard->adr['PostalCode']; }

        if(!empty($vCard->adr['Country'])) { $fullAddress .= ', '.$vCard->adr['Country']; }

        $education = array();
        if($html->find('.bio-edu ul', 0))
        {
            $list = $html->find('.bio-edu ul', 0);
            foreach($list->find('li') as $item)
            {
                $education[] = trim($item->plaintext);
            }
        }

        $bar_admissions = array();
        foreach($html->find('.fullwidth.admissions .bar-adm-item') as $item)
        {
            $bar_admissions[] = trim($item->plaintext);
        }

        $court_admissions = array();
        if($html->find('.bio-courts ul', 0))
        {
            $list = $html->find('.bio-courts ul', 0);
            foreach($list->find('li') as $item)
            {
                $court_admissions[] = trim($item->plaintext);
            }
        }

        $languages = array();
        if($html->find('.bio-lang ul', 0))
        {
            $list = $html->find('.bio-lang ul', 0);
            foreach($list->find('li') as $item)
            {
                $languages[] = trim($item->plaintext);
            }
        }

        $practice_areas = array();

        if($html->find('.pane-node-field-focus', 0))
        {
            $list = $html->find('.pane-node-field-focus', 0);
            foreach($list->find('.pane-content a') as $item)
            {
                $practice_areas[] = trim($item->plaintext);
            }
        }

        $positions = array();
        $positions[] = $pData['title'];

        $values['description'] = trim(str_replace('Overview', '', $html->find('.overview-teaser', 0)->plaintext));

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

        $primaryAddress = trim(preg_replace('/[0-9]+/', '', $html->find('.office-address span', 3)->plaintext));

        if(!empty($values['email']))
        {
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