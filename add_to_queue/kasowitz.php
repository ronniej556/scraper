<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.kasowitz.com';
$spider_name = 'kasowitz';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

for ($i=1; $i <= 16; $i++) {

	$data = fetch($base_url.'/people/search-results/?&page='.$i);
	$html = str_get_html($data);

	foreach ($html->find('.spotlight.spotlight--profile') as $item) {

		if($item->find('img', 0))
		{
			$image = $base_url.$item->find('img', 0)->src;
		}
		else
		{
			$image = '';
		}

		$values[] = array(
			'url' => $base_url.$item->find('a', 0)->href,
			'image' => $image,
			'name' => $item->find('.vcard_item.fn.n', 0)->plaintext,
			'position' => $item->find('span[class="role"]', 0)->plaintext,
			'location' => $item->find('span[class="region"]', 0)->plaintext,
			'email' => $item->find('a.email', 0)->plaintext,
			'phone' => preg_replace("/[^0-9]/", "", $item->find('a.tel', 0)->href),
			'vCard' => $base_url.$item->find('a.vcard_download', 0)->href,
		);
	}

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>