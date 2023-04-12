<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.wlrk.com';
$spider_name = 'wlrk';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/attorneys/?asf_l=View%20All');
$data = get_string_between($data, '</head>', '</body>').'</body>';
$html = str_get_html($data);

$values = array();

foreach ($html->find('.attorneys-search--results-items .attorneys-search--results-item') as $item) {

	if($item->find('img', 0))
	{
		$image = $item->find('img', 0)->src;
		$url = $item->find('a', 0)->href;
		$name = $item->find('.attorneys-search--results-item-name', 0)->plaintext;
		$position = $item->find('.attorneys-search--results-item-position', 0)->plaintext;
		$practice = $item->find('.attorneys-search--results-item-practice', 0)->plaintext;

		$values[] = array('image'=>$image, 'url'=>$url, 'name'=>$name, 'position'=>$position, 'practice'=>$practice);
	}

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>