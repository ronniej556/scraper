<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.choate.com';
$spider_name = 'choate';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/attorneys/index.html?_lm=true&v=attorney&s=2000');
$html = str_get_html($data);

$values = array();

foreach ($html->find('.profile') as $item) {

	if($item->find('img', 0))
	{

		$image = $item->find('img', 0)->src;
		$url = $item->href;
		$name = trim($item->find('.type-h5', 0)->plaintext);

		$dp = trim(str_replace('Department', '', $item->find('.type-h6-standard', 0)->plaintext));

		if(strpos($dp, ' – ') !== false)
		{
			$ex = explode(' – ', $dp);
		}
		else
		{
			$ex = explode(' - ', $dp);
		}

		$position = $ex[0];
		$practice = @$ex[1];

		$values[] = array(
			'image' => $image,
			'url' => $base_url.$url,
			'name' => $name,
			'position' => $position,
			'practice' => $practice,
		);
	}

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>