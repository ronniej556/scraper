<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://shermanhoward.com';
$spider_name = 'shermanhoward';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/wp-content/themes/sherman-howard/professionalssearch.php?c=empty&front=&back=');

$html = str_get_html($data);

foreach($html->find('.singlePostItem') as $item)
{
	$values[] = array(
		'image' => $base_url.$item->find('img', 0)->src,
		'url' => $base_url.$item->find('a', 0)->href,
		'name' => $item->find('.headshotInfo h4', 0)->plaintext,
		'position' => $item->find('.headshotInfo h4', 1)->plaintext,
		'location' => $item->find('.headshotInfo h4', 2)->plaintext,
	);
}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        $row['url'],
        json_encode($row),
        'pending',
        time(),
        NULL
    ));

}

echo count($values);

?>