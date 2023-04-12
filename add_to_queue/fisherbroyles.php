<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://backend.fisherbroyles.com';
$spider_name = 'fisherbroyles';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/wp-json/air/v1/attorney-names?search=%%%%&per_page=10000');
$result = json_decode($data, 1)['data'];

foreach($result as $value)
{
	$values[] = $value;
}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        'https://fisherbroyles.com/people/'.$row['slug'],
        json_encode($row),
        'pending',
        time(),
        NULL
    ));

}

echo count($values);

?>