<?php
include '../config.php';

$base_url = 'https://www.allenovery.com';
$spider_name = 'allenovery';

$values = array();

for ($page=1; $page < 250; $page++) {

    $data = fetch($base_url.'/api/en-GB/allenovery/search-people?pageID=6614b08f-6c75-406a-be3a-d8f9eac68423&page='.$page);

    $data = json_decode($data, 1);

    foreach($data['results'] as $item)
    {
        $values[] = $item;
    }

    if($data['hasMore'] != true)
    {
        break;
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