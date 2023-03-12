<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.whitecase.com';
$spider_name = 'whitecase';

$values = array();

$data = fetch($base_url.'/people-export/lawyers.json');
$values = json_decode($data, 1);

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

foreach($values as $row)
{
    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['path'], json_encode($row), 'pending', time(), NULL));
}

echo count($values);

?>