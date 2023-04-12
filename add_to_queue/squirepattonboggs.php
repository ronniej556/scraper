<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.squirepattonboggs.com';
$spider_name = 'squirepattonboggs';

$values = array();

$data = fetch($base_url.'/api/professionals/search?letter=&keyword=&serviceGuids=&levelGuids=&officeGuids=&pageSize=999999&pageNum=0&loadAllByPageSize=false');
$rows = json_decode($data, 1)['data']['list'];

foreach ($rows as $value) {
    $values[] = $value;
}

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

foreach($values as $row)
{
    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $base_url.$row['url'], json_encode($row), 'pending', time(), NULL));
}

echo count($values);

?>