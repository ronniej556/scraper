<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.sgrlaw.com';
$spider_name = 'sgrlaw';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/people/?sq=');

$html = str_get_html($data);

foreach($html->find('.c-search-results__item') as $item)
{
	$values[] = array(
		'url' => $item->find('a', 0)->href,
		'name' => $item->find('a', 0)->plaintext,
		'position' => $item->find('.vcard__type span', 0)->plaintext,
		'phone' => $item->find('.vcard__tel', 0)->plaintext,
		'email' => $item->find('a.email', 0)->plaintext,
		'location' => $item->find('.vcard__type a', 0)->plaintext,
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