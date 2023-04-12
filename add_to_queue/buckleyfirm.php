<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://buckleyfirm.com';
$spider_name = 'buckleyfirm';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/lawyers?first-name=&last-name=');
$html = str_get_html($data);

$values = array();

foreach ($html->find('.js-item.js-alphafilter-item') as $item) {

	$image = $item->find('img', 0)->src;
	$url = $item->find('a', 0)->href;
	$name = trim($item->find('.h4.name', 0)->plaintext);
	$position = trim($item->find('.h5.position', 0)->plaintext);
	$office = trim($item->find('.h6.office', 0)->plaintext);

	$values[] = array(
		'image'=>$image,
		'url'=>$base_url.$url,
		'name'=>$name,
		'position'=>$position,
		'office'=>$office,
	);

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>