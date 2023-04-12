<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.kutakrock.com';
$spider_name = 'kutakrock';

$values = array();

foreach(range('A', 'Z') as $char) {
    $data = fetch($base_url.'/sitecore/api/ssc/webapi/peoplesearch/1/search?letter='.$char);
    $rows = json_decode($data, 1)['results'];
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