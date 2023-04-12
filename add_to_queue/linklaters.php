<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.linklaters.com';
$spider_name = 'linklaters';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$i = 0;
while ($i < 2830) {

	$data = fetch($base_url.'/en/api/lawyers/getlawyers?searchTerm=&sort=&ignoreLocations=true&showing='.$i);

	$results = json_decode($data, 1)['Results'];

	foreach ($results as $key => $value) {
		$values[] = $value;
	}

	$i = $i+30;

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $base_url.$row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>