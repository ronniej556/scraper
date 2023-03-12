<?php
include '../config.php';

$base_url = 'https://www.akingump.com';
$spider_name = 'akingump';

$values = array();

//characters A-Z
foreach (range('A', 'Z') as $char) {

    $data = fetch($base_url.'/_site/search?l='.$char.'&f=0&v=attorney');
    $data = json_decode($data, 1)['hits']['ALL']['hits'];

    foreach($data as $item)
    {
        $values[] = $item;
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