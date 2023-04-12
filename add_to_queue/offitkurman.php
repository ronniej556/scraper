<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://offitkurman.com';
$spider_name = 'offitkurman';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/attorneys/');
$html = str_get_html($data);

foreach($html->find('.attorney.row') as $item)
{
	$values[] = array(
		'image' => $item->find('img', 0)->src,
		'url' => $item->find('a', 0)->href,
		'name' => preg_replace('/\s+/', ' ', $item->find('span.page-title', 0)->plaintext),
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