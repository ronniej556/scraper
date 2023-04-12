<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.hollandhart.com';
$spider_name = 'hollandhart';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/bios.aspx?view=all');
$html = str_get_html($data);

$values = array();

foreach ($html->find('.s-bio-box') as $item) {

	if($item->find('[loading="lazy"]', 0))
	{
		$image = $item->find('[loading="lazy"]', 0)->src;
	}
	else
	{
		$image = $root.'/img/nophoto.png';
	}

	if($item->find('.s-bio-box__phone a', 0) && $item->find('.s-bio-box__extra-links a', 1))
	{
		$values[] = array(
			'image' => $base_url.$image,
			'url' => $base_url.'/'.$item->find('.s-bio-box__name', 0)->href,
			'name' => trim($item->find('.s-bio-box__name', 0)->plaintext),
			'position' => $item->find('.s-bio-box__title', 0)->plaintext,
			'location' => $item->find('.s-bio-box__location a', 0)->plaintext,
			'phone' => $item->find('.s-bio-box__phone a', 0)->plaintext,
			'email' => str_replace('mailto:', '', $item->find('.s-bio-box__extra-links a', 1)->href),
			'vCard' => $base_url.$item->find('.s-bio-box__extra-links a', 0)->href
		);
	}

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>