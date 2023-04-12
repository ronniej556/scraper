<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'akerman';
$firm_name = 'Akerman LLP';
$base_url = 'https://www.akerman.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $fullAddress = '';
    $primaryAddress = '';

    $data = file_get_contents($row['url']);
    $data = get_string_between($data, '</head>', '</body>').'</body>';
    $html = str_get_html($data);

    $values = array();

    $values['names'] = explode(' ', trim($html->find('h1', 0)->plaintext));
    $values['email'] = get_string_between($html, 'href="mailto:', '"');

    foreach($html->find('.type__bio-info a') as $link)
    {
        if(trim($link->plaintext) == 'vCard')
        {
            $values['vCard'] = $base_url.$link->href;
        }
    }

    $values['LinkedIn'] = $html->find('[title="Share to LinkedIn"]', 0)->href;

    if(isset($values['vCard']))
    {
        file_put_contents($spider_name.'_temp.vcf', file_get_contents($values['vCard']));
        $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

        if(isset($vCard->tel[0]['Value']))
        {
            $values['phone_numbers'] = json_encode(array($vCard->tel[0]['Value']));
        }
        else
        {
            $values['phone_numbers'] = '[]';
        }

        $values['description'] = trim($html->find('.js-additional-attorney-information-destination-desktop', 0)->plaintext);

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

        $practice_areas = array();
        if($html->find('.js-sidebar-accordion', 0))
        {
            $list = $html->find('.js-sidebar-accordion', 0);
            foreach($list->find('li') as $item)
            {
                $practice_areas[] = trim($item->plaintext);
            }
        }

        $education = array();
        if($html->find('.js-sidebar-accordion', 1))
        {
            $list = $html->find('.js-sidebar-accordion', 1);
            foreach($list->find('li') as $item)
            {
                $education[] = trim($item->plaintext);
            }
        }

        $bar_admissions = array();
        $court_admissions = array();

        if($html->find('.js-sidebar-accordion', 2))
        {

            $list = $html->find('.js-sidebar-accordion', 2);

            if($list->find('ul', 0))
            {
                $bar = $list->find('ul', 0);
                foreach($bar->find('li') as $item)
                {
                    $bar_admissions[] = trim($item->plaintext);
                }
            }

            if($list->find('ul', 1))
            {
                $court = $list->find('ul', 1);
                foreach($court->find('li') as $item)
                {
                    $court_admissions[] = trim($item->plaintext);
                }
            }

        }

        $positions = array();
        $positions[] = trim(explode(',', $html->find('.bio-header__title', 0)->plaintext)[0]);

        $jd_year = (int) @filter_var($education[0], FILTER_SANITIZE_NUMBER_INT);
        $law_school = str_replace(',', '', trim(explode($jd_year, $education[0])[0]));

        $q = $pdo->prepare('INSERT INTO `people` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute(array(
            json_encode($values['names']),
            $values['email'],
            $values['vCard'],
            $fullAddress,
            $primaryAddress,
            $values['LinkedIn'],
            $values['phone_numbers'],
            '',
            json_encode($education),
            json_encode($bar_admissions),
            json_encode($court_admissions),
            json_encode($practice_areas),
            '[]',
            '[]',
            json_encode($positions),
            json_encode(array('English')),
            $row['url'],
            $values['description'],
            time(),
            $row['data'],
            $row['data'],
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
?>