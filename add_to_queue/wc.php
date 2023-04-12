<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.wc.com';
$spider_name = 'wc';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/Attorneys/Search?alpha=ALL');
$html = str_get_html($data);

$values = array();

foreach ($html->find('.sr_bio_item.bio_item') as $item) {

	if($item->find('.bio_img', 0))
	{
		$image = str_replace('../', '/', $item->find('.bio_img', 0)->src);
	}
	else
	{
		$image = $root.'/img/nophoto.png';
	}

	$url = str_replace('../', '/', $item->find('a', 0)->href);
	$name = trim($item->find('.bio_name', 0)->plaintext);
	$position = $item->find('.bio_title', 0)->plaintext;
	$phone = @$item->find('.bio_email a', 0)->plaintext;
	$email = @$item->find('.bio_direct.phone_num', 0)->plaintext;

	$values[] = array(
		'image'=>$image,
		'url'=>$url,
		'name'=>$name,
		'position'=>$position,
		'phone'=>$phone,
		'email'=>$email
	);

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>