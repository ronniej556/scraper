<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.quinnemanuel.com';
$spider_name = 'quinnemanuel';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

for ($i=1; $i <= 20; $i++) {

    $data = fetch($base_url.'/Umbraco/surface/AttorneysListSurface/GetAttorneyListByFilter?byCountry=&byChar=all&byProfileType=&bySearch=&byOffice=&byPracticeArea=&byLawClerk=&byAdp=&currentPage='.$i);

    $html = str_get_html($data);

    foreach($html->find('.attorney-card') as $item)
    {
        if($item->find('.attorney-rewrite', 0))
        {

            $values[] = array(
                'image' => $base_url.$item->find('img', 0)->src,
                'name' => $item->find('h3 a', 0)->plaintext,
                'url' => $item->find('a.attorney-rewrite', 0)->href,
                'position' => trim($item->find('.designation', 0)->plaintext),
                'location' => @$item->find('.location a', 0)->plaintext,
                'vCard' => $base_url.$item->find('.more-bio a', 1)->href
            );
        }

    }

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        $row['url'],
        json_encode($row),
        'pending',
        time(),
        NULL
    ));

}

echo count($values);

?>