<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.hklaw.com';
$spider_name = 'hklaw';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

for ($i=1; $i <= 100; $i++) {

	$data = fetch_post($base_url.'/api/ProfessionalsApi/Lawyers?page='.$i, array(
		'page' => $i
	));

	$results = json_decode($data, 1)['results'];
	foreach ($results as $key => $value) {
		$values[] = $value;
	}

}

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