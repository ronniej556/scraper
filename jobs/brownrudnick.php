<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

$spider_name = 'brownrudnick';
$firm_name = 'Brown Rudnick';
$base_url = 'https://brownrudnick.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $data = fetch($row['url']);
    $html = str_get_html($data);

    $pData = json_decode($row['data'], 1);

    $values = array();

    if($html->find('#download--vcard', 0)->href)
    {

        $values['names'] = json_encode(explode(' ', trim($html->find('#people--title', 0)->plaintext)));
        $values['email'] = trim($html->find('.email--people--link', 0)->plaintext);
        $values['vCard'] = $html->find('#download--vcard', 0)->href;

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

        if(!empty($vCard->adr['StreetAddress']))
        {
            $address = $vCard->adr['StreetAddress']; } else { $address = '';
        }

        if(!empty($vCard->adr['Locality']))
        {
            $city = $vCard->adr['Locality']; } else { $city = '';
        }

        if(!empty($vCard->adr['Region']))
        {
            $state = $vCard->adr['Region']; } else { $state = '';
        }

        if(!empty($vCard->adr['PostalCode']))
        {
            $postalCode = $vCard->adr['PostalCode']; } else { $postalCode = '';
        }

        if(!empty($vCard->adr['Country']))
        {
            $country = $vCard->adr['Country']; } else { $country = '';
        }

        $education = array();
        if($html->find('.experience__wrapper .large-6', 0))
        {
            $list = $html->find('.experience__wrapper .large-6', 0);
            foreach($list->find('.body__text') as $item)
            {
                $education[] = trim($item->plaintext);
            }
        }

        $bar_admissions = array();
        if($html->find('.experience__wrapper .large-6', 1))
        {
            $list = $html->find('.experience__wrapper .large-6', 1);
            foreach($list->find('.body__text') as $item)
            {
                $bar_admissions[] = trim($item->plaintext);
            }
        }

        if($html->find('#person-specialization', 0)->plaintext)
        {
            $practice_areas = json_encode(array(trim($html->find('#person-specialization', 0)->plaintext)));
        }
        else
        {
            $practice_areas = '[]';
        }

        $positions = json_encode(array(trim($html->find('.people__details.tag__text', 0)->plaintext)));

        $values['description'] = trim(str_replace('Biography', '', $html->find('.medium-9', 2)->plaintext));

        $law_school = trim(explode('–', $education[0])[0]);
        $jd_year = (int) @filter_var($education[0], FILTER_SANITIZE_NUMBER_INT);

        $q = $pdo->prepare('INSERT INTO `people` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute(array(
            $values['names'],
            $values['email'],
            $address,
            $city,
            $state,
            $postalCode,
            $country,
            $values['vCard'],
            '',
            $values['phone_numbers'],
            '',
            json_encode($education),
            json_encode($bar_admissions), //bar admissions
            '[]', //court admissions
            $practice_areas,
            '[]',
            '[]',
            $positions,
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

}

@unlink($spider_name.'_temp.vcf');
exit();
?>