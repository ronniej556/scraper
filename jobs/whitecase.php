<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'whitecase';
$firm_name = 'White & Case LLP';
$base_url = 'https://www.whitecase.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $fullAddress = '';
    $primaryAddress = '';

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    $values = array();

    if($html->find('a.wc-icon-vcard', 0)->href)
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

        $values['names'] = json_encode(explode(' ', $pData['title']));
        $values['email'] = get_string_between($html->innertext, '"email": "', '"');
        $values['vCard'] = $base_url.$html->find('a.wc-icon-vcard', 0)->href;
        $values['phone_numbers'] = json_encode(array(urldecode(str_replace('tel:', '', $html->find('.field--name-field-phone a', 0)->href))));

        $fullAddress = '';
        $primaryAddress = $pData['location'];

        $education = array();
        if($html->find('.field--name-field-education', 0))
        {
            $list = $html->find('.field--name-field-education', 0);
            foreach($list->find('.paragraph--type--education') as $item)
            {
                $education[] = trim(preg_replace('/\s+/', ' ', $item->plaintext));
            }
        }

        $bar_admissions = array();
        $court_admissions = array();

        if($html->find('.field--name-field-admissions', 0))
        {
            $list = $html->find('.field--name-field-admissions', 0);
            foreach($list->find('.field--item') as $item)
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
        if($html->find('.field--name-field-related-services', 0))
        {
            $list = $html->find('.field--name-field-related-services', 0);
            foreach($list->find('.col-sm-6') as $item)
            {
                $practice_areas[] = trim($item->plaintext);
            }
        }

        $positions = array();
        $positions[] = $pData['role'];

        $values['description'] = trim($html->find('.field--type-text-with-summary', 0)->plaintext);

        $photo = $base_url.$html->find('.img-responsive', 0)->src;
        $thumb = str_replace('original_image', 'bio_thumbnail', $photo);

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
            $law_school = $education[0];
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

    unset($values);
    unset($law_school);
    unset($jd_year);
    unset($fullAddress);
    unset($primaryAddress);

}

@unlink($spider_name.'_temp.vcf');
exit();
?>