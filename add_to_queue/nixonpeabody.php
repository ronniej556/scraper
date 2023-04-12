<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.nixonpeabody.com';
$spider_name = 'nixonpeabody';

$values = array();

$data = fetch($base_url.'/api/search/people?q=&start=0&rows=999999&sort=asc&lastinitial=');
$rows = json_decode($data, 1)['response']['docs'];

foreach ($rows as $value) {
    if(strpos($value['resulturl_s'], 'http') === false)
    {
        $value['resulturl_s'] = $base_url.$value['resulturl_s'];
    }
    $values[] = $value;
}

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

foreach($values as $row)
{
    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['resulturl_s'], json_encode($row), 'pending', time(), NULL));
}

echo count($values);

?>