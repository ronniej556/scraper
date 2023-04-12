<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.faegredrinker.com';
$spider_name = 'faegredrinker';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

    $data = fetch($base_url.'/webapi/professionals/search?letter='.$char.'&pageNum=1&pageSize=1000&sortBy=0&sortOrder=0&language=en&_dt=1679247328951');
    $result = json_decode($data, 1)['Results'];

    foreach($result as $value)
    {
    	$values[] = $value;
    }

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        $base_url.$row['Url'],
        json_encode($row),
        'pending',
        time(),
        NULL
    ));

}

echo count($values);

?>