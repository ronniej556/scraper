<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.bsk.com';
$spider_name = 'bsk';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/people/search?results=results&first+name=&last+name=&practices=0&office=0&law+school=0&undergrad+school=0&bar+admission=0&position=0&submit.x=1&submit.y=18&nopagination=true');
$html = str_get_html($data);

$values = array();

foreach ($html->find('.attorney') as $item) {

	$image = $item->find('img', 0)->src;
	$url = $item->find('a', 0)->href;
	$name = trim($item->find('.contact-info h3', 0)->plaintext);
	$position = $item->find('.contact-info h4', 0)->plaintext;
	$phone = $item->find('.contact-info p a', 0)->plaintext;
	$email = $item->find('.contact-info p a', 1)->plaintext;

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