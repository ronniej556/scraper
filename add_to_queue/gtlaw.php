<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.gtlaw.com';
$spider_name = 'gtlaw';

$values = array();

$data = fetch($base_url.'/sitecore/api/ssc/webapi/search/1/Professionals/?searchType=Professionals&pageSize=5000&pageNum=0&sortBy=1&sortOrder=0&language=en&noSkip=true&isFeatured=false');
$values = json_decode($data, 1)['data']['list'];

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

foreach($values as $row)
{
    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $base_url.$row['Url'], json_encode($row), 'pending', time(), NULL));
}

echo count($values);

?>