<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://kobrekim.com';
$spider_name = 'kobrekim';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/people/search?&filterNumPerPage=all');
$html = str_get_html($data);

$values = array();

foreach ($html->find('#people li') as $item) {

	if($item->find('h1', 0))
	{
		$values[] = array(
			'url' => $base_url.$item->find('a', 0)->href,
			'image' => $base_url.$item->find('img', 0)->src,
			'name' => $item->find('h1', 0)->plaintext,
			'position' => $item->find('h4', 0)->plaintext,
			'location' => @$item->find('h6 a', 0)->plaintext,
			'phone' => @trim(strip_tags(get_string_between($item->find('.staff-address', 0)->outertext, 'tel.', '</div>'))),
			'email' => html_entity_decode(str_replace('mailto:', '', $item->find('a.staffEmailLink', 0)->href)),
		);
	}

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>