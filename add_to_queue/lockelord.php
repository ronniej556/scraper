<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.lockelord.com';
$spider_name = 'lockelord';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach(range('A', 'Z') as $char)
{
    $data = fetch($base_url.'/api/sitecore/peoplesearch/get?letter='.strtolower($char).'&take=1000&page=1');
    $rows = json_decode(json_decode($data, 1), 1)['results'];
    foreach($rows as $value)
    {
        $values[] = $value;
    }
}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $base_url.$row['Url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>