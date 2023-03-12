<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.perkinscoie.com';
$spider_name = 'perkinscoie';

$values = array();

$data = fetch($base_url.'/_site/search?f=0&s=99999&site=1&gda=1002960&v=attorney');
$values = json_decode($data, 1)['hits']['ALL']['hits'];

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