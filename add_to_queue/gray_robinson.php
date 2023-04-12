<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.gray-robinson.com';
$spider_name = 'gray_robinson';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

    $data = fetch($base_url.'/attorneys/by-letter/'.strtolower($char));
    $html = str_get_html($data);

    foreach($html->find('.profile-card') as $item)
    {
    	$values[] = array(
    		'image' => $base_url.$item->find('img', 0)->src,
    		'url' => $base_url.$item->find('a', 0)->href,
    		'name' => $item->find('h2', 0)->plaintext,
    		'position' => $item->find('span.h6', 0)->plaintext,
    		'location' => $item->find('span.text-tertiary.h6', 0)->plaintext,
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