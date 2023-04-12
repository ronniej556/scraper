<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.procopio.com';
$spider_name = 'procopio';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$i = 1;
while ($i < 13) {

	if($i == 1)
	{
		$data = fetch($base_url.'/page-data/people/page-data.json');
	}
	else
	{
		$data = fetch($base_url.'/page-data/people/'.$i.'/page-data.json');
	}
	$data = json_decode($data, 1);

	foreach ($data['result']['data']['allWpPeople']['edges'] as $key => $value)
	{
		$values[] = $value['people'];
	}

	$i++;

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        $base_url.$row['uri'],
        json_encode($row),
        'pending',
        time(),
        NULL
    ));

}

echo count($values);

?>