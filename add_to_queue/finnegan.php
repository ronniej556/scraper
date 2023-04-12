<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.finnegan.com';
$spider_name = 'finnegan';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/_site/search?s=999999&f=0&v=attorney&json=true');

$values = json_decode($data, 1)['hits']['ALL']['hits'];

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        $base_url.$row['url'],
        json_encode($row),
        'pending',
        time(),
        NULL
    ));

}

echo count($values);

?>