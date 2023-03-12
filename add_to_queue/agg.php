<?php
include '../config.php';

$base_url = 'https://www.agg.com';
$spider_name = 'agg';

$values = array();

$data = fetch($base_url.'/api/professionals?page=1&perpage=500');

$results = json_decode($data, 1)['results'];

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

foreach ($results as $key => $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $base_url.$row['path'], json_encode($row), 'pending', time(), NULL));

}

echo count($results);

?>