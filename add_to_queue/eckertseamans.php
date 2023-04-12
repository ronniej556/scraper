<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.eckertseamans.com';
$spider_name = 'eckertseamans';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch_post($base_url.'/cms/wp-admin/admin-ajax.php', array(
	'action' => 'fetch_attorneys',
	'searchTermEntered' => '',
	'officeTerm' => '',
	'practiceTerm' => '',
	'stateBarTerm' => '',
	'educationTerm' => '',
	'languageTerm' => '',
	'showAll' => 'true',
));

$values = json_decode($data, 1);

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['URL'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>