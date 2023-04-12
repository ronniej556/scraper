<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.cozen.com';
$spider_name = 'cozen';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

	$data = fetch($base_url.'/data/people.ashx?s=p&starts='.strtolower($char));
	$rows = json_decode($data, 1);
	foreach($rows as $value)
	{
		$values[] = $value;
	}

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        $base_url.$row['Link'],
        json_encode($row),
        'pending',
        time(),
        NULL
    ));

}

echo count($values);

?>