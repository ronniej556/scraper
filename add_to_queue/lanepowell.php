<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.lanepowell.com';
$spider_name = 'lanepowell';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/Our-People/Professional-Search?alpha=ALL');
$html = get_string_between($data, '</head>', '<body>').'</body>';
$html = str_get_html($html);

foreach ($html->find('.sr_bio_item.bio_item') as $item) {

	if($item->find('a', 0))
	{
		$values[] = array(
			'url' => $base_url.$item->find('a', 0)->href,
			'image' => $base_url.$item->find('img', 0)->src,
			'name' => $item->find('.bio_name', 0)->plaintext,
			'position' => $item->find('.bio_title', 0)->plaintext,
			'location' => $item->find('.bio_office a', 0)->plaintext,
			'phone' => @trim(str_replace('D. ', '', $item->find('.bio_direct.phone_num', 0)->plaintext)),
			'email' => @$item->find('a.email_link', 0)->plaintext,
		);
	}
	
}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>