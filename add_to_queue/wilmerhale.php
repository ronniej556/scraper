<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.wilmerhale.com';
$spider_name = 'wilmerhale';

$values = array();

$data = fetch($base_url.'/api/sitecore/Bio/People?currentpage=1&keyword=a&type=insight&pagesize=999999');
$rows = json_decode($data, 1)['ListResult']['Results'];

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
    $q->execute(array($spider_name, $base_url.$row['Link'], json_encode($row), 'pending', time(), NULL));
}

echo count($values);

?>