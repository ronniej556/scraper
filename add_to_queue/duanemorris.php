<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://webdynamic.amazon.duanemorris.com';
$spider_name = 'duanemorris';

$values = array();

foreach (range('A', 'Z') as $char) {

    $data = fetch($base_url.'/people/v1/searchJ?lastName='.$char.'&isAttorney=Y');
    if(json_decode($data, 1))
    {
        $rows = json_decode($data, 1)['roster'];

        foreach ($rows as $value) {
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
    $q->execute(array($spider_name, $row['friendlyURL'], json_encode($row), 'pending', time(), NULL));
}

echo count($values);

?>