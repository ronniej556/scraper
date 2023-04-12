<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.robinskaplan.com';
$spider_name = 'robinskaplan';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/sitecore/api/ssc/webapi/professionals/1/search?pageSize=500');
$result = json_decode($data, 1);
foreach($result['results'] as $item)
{
    $values[] = $item;
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