<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.jmbm.com';
$spider_name = 'jmbm';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/cgi-bin/attorneysearch?first_name=&last_name=&title=&practice_area=&location=&law_school=&bar=&language=&submit=Submit');
$html = str_get_html($data);

$values = array();

foreach ($html->find('.peoplelist') as $item) {
	$values[] = array(
		'image' => str_replace('../', $base_url.'/', $item->find('img', 0)->src),
		'url' => str_replace('../', $base_url.'/', $item->find('a', 0)->href),
		'name' => preg_replace('/\s+/', ' ', trim($item->find('a', 1)->plaintext)),
		'position' => @trim($item->find('span.pos', 0)->plaintext),
		'email' => @trim(html_entity_decode($item->find('a.emllink', 0)->plaintext)),
		'phone' => trim(str_replace('Direct: ', '', $item->find('.blocknumbers span', 0)->plaintext)),
		'location' => trim($item->find('.cno_0', 0)->plaintext),
	);
}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>