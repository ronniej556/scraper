<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.moundcotton.com';
$spider_name = 'moundcotton';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/attorneys/?attysearch=&swp_category_limiter=0&swp_location_limiter=0');
$html = str_get_html($data);

foreach($html->find('.search-result') as $item)
{
	$values[] = array(
		'url' => $item->find('a', 0)->href,
		'name' => $item->find('h5 a', 0)->plaintext,
		'position' => str_replace("\n", '', trim($item->find('.attyCategory', 0)->plaintext)),
		'phone' => trim(str_replace('&nbsp;|&nbsp;', '', $item->find('.attyContact', 0)->plaintext)),
		'email' => get_string_between($item->find('.attyContact a', 0)->href, 'mailto:', '?subject=website inquiry'),
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