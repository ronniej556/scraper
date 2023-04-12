<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.bsfllp.com';
$spider_name = 'bsfllp';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/_site/search?v=attorney&f=0&s=100&json');

$values = array();

$data = json_decode($data, 1)['hits']['ALL']['hits'];

foreach($data as $item)
{
    $values[] = $item;
}

foreach($values as $row)
{
    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $base_url.$row['url'], json_encode($row), 'pending', time(), NULL));
}

echo count($values);

?>