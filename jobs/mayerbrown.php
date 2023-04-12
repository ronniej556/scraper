<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'mayerbrown';
$firm_name = 'Mayer Brown';
$base_url = 'https://www.mayerbrown.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $fullAddress = '';
    $primaryAddress = '';

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    $values = array();

    if(isset($pData['vCardLink']))
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
        $values['vCard'] = $base_url.$pData['vCardLink'];
        $values['phone_numbers'] = json_encode(array($pData['number']));

        $f = fetch($values['vCard']);

        if(!empty($f))
        {
            file_put_contents($spider_name.'_temp.vcf', $f);
            $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

            if(!empty($vCard->adr[0]['StreetAddress'])) { $fullAddress = $vCard->adr[0]['StreetAddress']; }

            if(!empty($vCard->adr[0]['Locality'])) {
                $fullAddress .= ', '.$vCard->adr[0]['Locality'];
            }

            if(!empty($vCard->adr[0]['Region'])) {
                $fullAddress .= ', '.$vCard->adr[0]['Region'];
            }

            if(!empty($vCard->adr[0]['PostalCode'])) { $fullAddress .= ', '.$vCard->adr[0]['PostalCode']; }

            if(!empty($vCard->adr[0]['Country'])) { $fullAddress .= ', '.$vCard->adr[0]['Country']; }

            $primaryAddress = $vCard->adr[0]['Country'];

            if(empty($primaryAddress))
            {
                $primaryAddress = $html->find('.contact-card__office.link.link__underlined', 0)->plaintext;
            }

            if(empty($primaryAddress))
            {
                $primaryAddress = '';
            }

            $education = array();
            foreach($html->find('.block') as $item)
            {
                if(strpos($item->innertext, 'Education') !== false)
                {
                    foreach($item->find('.richtext p') as $value)
                    {
                        $education[] = $value->plaintext;
                    }
                }
            }

            $bar_admissions = array();
            $court_admissions = array();

            if($html->find('[aria-label="Credentials"] ul', 0))
            {
                $list = $html->find('[aria-label="Credentials"] ul', 0);
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
            if($html->find('ul.styled-list__list', 0))
            {
                $list = $html->find('ul.styled-list__list', 0);
                foreach($list->find('li') as $item)
                {
                    $practice_areas[] = trim($item->plaintext);
                }
            }

            $positions = json_encode(array($pData['title']));

            $values['description'] = trim($html->find('.block__row.richtext', 0)->plaintext);

            $photo = $base_url.$pData['image'];
            $thumb = $base_url.$pData['image'];

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