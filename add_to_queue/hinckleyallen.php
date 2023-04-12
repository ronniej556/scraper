<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'http://www.hinckleyallen.com';
$spider_name = 'hinckleyallen';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

    $data = fetch($base_url.'/professionals/?letter='.$char);
    $html = str_get_html($data);

    foreach($html->find('.people-list__item') as $item)
    {
    	$values[] = array(
    		'image' => $item->find('img', 0)->src,
    		'url' => $item->find('a', 0)->href,
    		'name' => $item->find('.people-details h3 a', 0)->plaintext,
    		'position' => trim($item->find('small.people-title', 0)->plaintext),
    		'phone' => $item->find('.people-details a', 1)->plaintext,
    	);
    }

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