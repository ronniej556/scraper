<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'ropesgray';
$firm_name = 'Ropes &amp; Gray LLP';
$base_url = 'https://www.ropesgray.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $fullAddress = '';
    $primaryAddress = '';

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    $vCard_link = $row['url'].'?vcard=1';

    if(!empty($vCard_link) && !empty($pData['phone']))
    {

        foreach($html->find('a') as $link)
        {
            if(strpos(strtolower($link->href), 'linkedin') !== false)
            {
                $linkedIn = $link->href;
                break;
            }
        }
        if(empty($linkedIn)) { $linkedIn = 'https://www.linkedin.com/company/ropes-&-gray-llp/'; }

        $pData['phone'] = str_replace('T', '', $pData['phone']);

        $values['names'] = json_encode(explode(' ', $pData['name']));
        $values['email'] = $pData['email'];
        $values['vCard'] = $vCard_link;

        $f = fetch($values['vCard']);

        if(strpos($f, '<head>') === false)
        {

            file_put_contents($spider_name.'_temp.vcf', $f);
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

            if(empty($fullAddress))
            {
                $fullAddress = '';
            }

            $education = array();
            if($html->find('#education-content ul', 0))
            {
                $list = $html->find('#education-content ul', 0);
                foreach($list->find('li') as $item)
                {
                    $education[] = trim($item->plaintext);
                }
            }

            $bar_admissions = array();
            $court_admissions = array();

            if($html->find('#admissions-content ul', 0))
            {
                $list = $html->find('#admissions-content ul', 0);
                foreach($list->find('li') as $item)
                {
                    if(strpos(strtolower($item->plaintext), 'court') !== false)
                    {
                        $court_admissions[] = trim($item->plaintext);
                    }
                    else
                    {
                        $bar_admissions[] = trim($item->plaintext);
                    }
                }
            }

            $memberships = array();

            $languages = array();
            $languages[] = 'English';

            $practice_areas = array();
            if($html->find('#practices-content ul', 0))
            {
                $list = $html->find('#practices-content ul', 0);
                foreach($list->find('li') as $item)
                {
                    $practice_areas[] = trim($item->plaintext);
                }
            }

            $positions = array();
            $positions[] = $pData['title'];

            $values['description'] = trim(get_string_between($html->find('#main-content .column-inner', 0)->innertext, '</hgroup>', '<ul class="nav nav-tabs hidden-phone">'));

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

            foreach($html->find('.module-content-inner') as $item)
            {
                foreach($item->find('a') as $link)
                {
                    if(strpos($link->href, '/en/locations/') !== false)
                    {
                        $primaryAddress = $link->plaintext;
                        break;
                    }
                }
            }

            if(!empty($values['email']))
            {
                $q = $pdo->prepare('INSERT INTO `people` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $q->execute(array(
                    $values['names'],
                    $values['email'],
                    $values['vCard'],
                    @$fullAddress,
                    $primaryAddress,
                    $linkedIn,
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