<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.ballardspahr.com';
$spider_name = 'ballardspahr';

$values = array();

foreach(range('A', 'Z') as $char) {
    $data = fetch($base_url.'/sitecore/api/people/search?lang=en&sc_apikey=%7B8BEE2997-A9B1-4874-A4C3-7EBA04C493EC%7D&page=0&Alpha='.strtolower($char));
    $rows = json_decode($data, 1)['Results'];
    foreach($rows as $value)
    {
        $values[] = $value;
    }
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