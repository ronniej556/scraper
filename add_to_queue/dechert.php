<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://solr.ministrydesign.co.uk';
$spider_name = 'dechert';

$values = array();

$data = fetch($base_url.'/solr/people/select?rows=99999&start=0&fq=*&q=*&sort=personPosRelevance%20asc%2C%20personLastNmIndx%20asc');
$rows = json_decode($data, 1)['response']['docs'];

foreach ($rows as $value) {
    $values[] = $value;
}

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

foreach($values as $row)
{
    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, 'https://www.dechert.com'.$row['personPagePath'], json_encode($row), 'pending', time(), NULL));
}

echo count($values);

?>