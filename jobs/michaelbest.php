<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$base_url = 'https://www.michaelbest.com';
$spider_name = 'michaelbest';
$firm_name = 'Michael Best & Friedrich LLP';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $fullAddress = '';
    $primaryAddress = '';

    $pData = json_decode($row['data'], 1);
    $row['url'] = str_replace('.vcf', '', $row['url']);
    $pData['url'] = str_replace('.vcf', '', $pData['url']);

    $data = fetch($row['url']);
    $html = str_get_html($data);

    var_dump($pData);

    $values = array();

    if($html->find('a'))
    {
        foreach($html->find('a') as $link)
        {
            if(strpos(strtolower($link->href), 'linkedin') !== false)
            {
                $linkedIn = $link->href;
                break;
            }
        }

        $values['vCard'] = $pData['vCard'];

        if(empty($linkedIn)) { $linkedIn = ''; }

        $values['names'] = json_encode(explode(' ', $pData['name']));

        $values['phone_numbers'] = json_encode(array($pData['phone']));

        $values['email'] = $pData['email'];

        $fullAddress = '';

        $education = array();
        foreach($html->find('.bio_related_list.education_list') as $item)
        {
            foreach($item->find('li') as $item)
            {
                $education[] = trim(preg_replace('/\s+/', ' ', $item->plaintext));
            }
        }

        $bar_admissions = array();
        $court_admissions = array();

        foreach($html->find('.admission_list li') as $item)
        {
            if(strpos(strtolower($item->plaintext), 'court'))
            {
                $court_admissions[] = trim($item->plaintext);
            }
            else
            {
                $bar_admissions[] = trim($item->plaintext);
            }
        }

        $practice_areas = array();
        foreach($html->find('#bio_details_related_practices') as $item)
        {
            foreach($item->find('a') as $item)
            {
                $practice_areas[] = trim(preg_replace('/\s+/', ' ', $item->plaintext));
            }
        }
        $practice_areas = array_unique($practice_areas);

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

        $positions = json_encode(array($pData['position']));

        if($html->find('.bio_details_content.content_section', 0))
        {

            $values['description'] = $html->find('.bio_details_content.content_section', 0)->plaintext;

            $pData['image'] = $base_url.'/'.str_replace('../', '', $html->find('img.bio_img', 0)->src);
            
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

                if(!empty($f))
                {
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

            }
            else
            {
                $values['vCard'] = '';
            }

            //var_dump($row);
            //var_dump($pData);

            if(isset($values['email']))
            {
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

}

@unlink($spider_name.'_temp.vcf');
exit();
?>