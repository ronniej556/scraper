<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.pillsburylaw.com';
$spider_name = 'pillsburylaw';

$values = array();

for ($i=1; $i <= 1; $i++) { 

    $data = fetch($base_url.'/_site/search?v=attorney&json&s=999999');
    $rows = json_decode($data, 1)['hits']['ALL']['hits'];

    foreach ($rows as $value) {
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