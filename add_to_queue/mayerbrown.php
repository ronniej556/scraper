<?php
include '../config.php';

$base_url = 'https://www.mayerbrown.com';
$spider_name = 'mayerbrown';

$values = array();

//characters A-Z
foreach (range('A', 'Z') as $char) {

    $data = fetch($base_url.'/sitecore/api/ssc/webapi/people/1/search?letter='.$char.'&pageSize=100&isInitialSearch=false');

    $data = json_decode($data, 1);

    if($data['results'])
    {
        foreach($data['results'] as $key => $value)
        {
            $values[] = $value;
        }
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