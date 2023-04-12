<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.clarkhill.com';
$spider_name = 'clarkhill';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {
    $data = fetch($base_url.'/api/people?page=1&letter=A');
    $result = json_decode($data, 1);
    foreach($result['results'] as $item)
    {
        $values[] = $item;
    }
}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        $base_url.$row['url'],
        json_encode($row),
        'pending',
        time(),
        NULL
    ));

}

echo count($values);

?>