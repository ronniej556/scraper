<?php
include '../config.php';
include '../simple_html_dom.php';
include '../../vCard.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$spider_name = 'adamsandreese';
$firm_name = 'Adams and Reese LLP';
$base_url = 'https://www.adamsandreese.com';

$q = $pdo->prepare('SELECT * FROM `queue` WHERE `status`=\'pending\' AND `spider_name`=? LIMIT 50');
$q->execute(array($spider_name));

foreach ($q as $row) {

    $data = fetch($row['url']);
    $data = get_string_between($data, '</head>', '</body>').'</body>';
    $html = str_get_html($data);

    $values = array();

    if($html->find('.profilecard__title', 0)->plaintext)
    {
        $values['photo'] = $html->find('.hero__image img', 0)->src;
        $values['names'] = json_encode(explode(' ', $html->find('.profilecard__title', 0)->plaintext));
        $values['email'] = get_string_between($data, '"email":"', '",');
        $values['vCard'] = $base_url.$html->find('.profilecard__row a', 0)->href;
        
        foreach($html->find('.profilecard__row a') as $link)
        {
            if(strpos(strtolower($link->href), 'linkedin') !== false)
            {
                $values['LinkedIn'] = $link->href;
            }
        }
        if(empty($values['LinkedIn']))
        {
            $values['LinkedIn'] = '';
        }

        $phone_numbers = array();
        foreach($html->find('.profilecard__meta a') as $link)
        {
            $phone_numbers[] = trim($link->plaintext);
        }
        $values['phone_numbers'] = json_encode($phone_numbers);

        $education = array();
        if($html->find('.barlist__list', 0))
        {
            $ul = $html->find('.barlist__list', 0);
            foreach($ul->find('.barlist__text') as $item)
            {
                $education[] = $item->plaintext;
            }
            $values['education'] = json_encode($education);
        }
        else
        {
            $values['education'] = '[]';
        }

        $bar_admissions = array();
        if($html->find('.barlist__list', 1))
        {
            $ul = $html->find('.barlist__list', 1);
            foreach($ul->find('.barlist__text') as $item)
            {
                $bar_admissions[] = $item->plaintext;
            }
            $values['bar_admissions'] = json_encode($bar_admissions);
        }
        else
        {
            $values['bar_admissions'] = 0;
        }

        if($html->find('.barlist__list', 2))
        {
            $court_admissions = array();
            $ul = @$html->find('.barlist__list', 2);
            foreach($ul->find('.barlist__text') as $item)
            {
                $court_admissions[] = $item->plaintext;
            }
            $values['court_admissions'] = json_encode($court_admissions);
        }
        else
        {
            $values['court_admissions'] = '[]';
        }

        $practice_areas = array();
        if($html->find('.barlinklist__list', 0))
        {
            $ul = $html->find('.barlinklist__list', 0);
            foreach($ul->find('.barlinklist__item') as $item)
            {
                $practice_areas[] = $item->plaintext;
            }
            $values['practice_areas'] = json_encode($practice_areas);
        }
        else
        {
            $values['practice_areas'] = '[]';
        }

        $acknowledgements = array();
        $ul = $html->find('.richtext ul', 0);
        if($ul = $html->find('.richtext ul', 0))
        {
            foreach($ul->find('.ARBioList') as $item)
            {
                $acknowledgements[] = $item->plaintext;
            }
            $values['acknowledgements'] = json_encode($acknowledgements);
        }
        else
        {
            $values['acknowledgements'] = '[]';
        }

        $memberships = array();
        if($html->find('.richtext ul', 1))
        {
            $ul = $html->find('.richtext ul', 1);
            foreach($ul->find('.ARBioList') as $item)
            {
                $memberships[] = $item->plaintext;
            }
            $values['memberships'] = json_encode($memberships);
        }
        else
        {
            $values['memberships'] = '[]';
        }

        $positions = array();
        foreach($html->find('.profilecard__subtitle') as $item)
        {
            $positions[] = $item->plaintext;
        }
        $values['positions'] = json_encode($positions);

        $values['languages'] = '["English"]';

        $values['source'] = $row['url'];

        if($html->find('.readmore__block', 0))
        {
            $values['description'] = $html->find('.readmore__block', 0)->plaintext;
        }
        else
        {
            $values['description'] = '';
        }
        

        $locations = array();
        foreach($html->find('.profilecard__location') as $location)
        {
            $locations[] = trim($location->plaintext);
        }
        $values['locations'] = json_encode($locations);

        file_put_contents($spider_name.'_temp.vcf', file_get_contents($values['vCard']));
        $vCard = new vCard($spider_name.'_temp.vcf', false, array('Collapse' => true));

        if(!empty($vCard->adr['StreetAddress'])) { $address = $vCard->adr['StreetAddress']; } else { $address = ''; }

        if(!empty($vCard->adr['Locality'])) { $city = $vCard->adr['Locality']; } else { $city = ''; }

        if(!empty($vCard->adr['Region'])) { $state = $vCard->adr['Region']; } else { $state = ''; }

        if(!empty($vCard->adr['PostalCode'])) { $postalCode = $vCard->adr['PostalCode']; } else { $postalCode = ''; }

        if(!empty($vCard->adr['Country'])) { $country = $vCard->adr['Country']; } else { $country = ''; }

        $law_school_data = $education[0];
        $law_school = explode(', ', $law_school_data)[0];
        $jd_year = str_replace('-', '', (int) filter_var($law_school_data, FILTER_SANITIZE_NUMBER_INT));

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
            $values['LinkedIn'],
            $values['phone_numbers'],
            '',
            $values['education'],
            $values['bar_admissions'],
            $values['court_admissions'],
            $values['practice_areas'],
            $values['acknowledgements'],
            $values['memberships'],
            $values['positions'],
            $values['languages'],
            $values['source'],
            $values['description'],
            time(),
            json_decode($row['data'], 1)['image'],
            $values['photo'],
            $spider_name,
            $firm_name,
            $law_school,
            $jd_year,
            NULL
        ));

        $q = $pdo->prepare('UPDATE `queue` SET `status`=\'complete\' WHERE `id`=?');
        $q->execute(array($row['id']));

    }

}

@unlink($spider_name.'_temp.vcf');
?>