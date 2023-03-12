<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'http://www.cadwalader.com';
$spider_name = 'cadwalader';

$values = array();

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/find/professionals/');
$data = get_string_between($data, '</head>', '</body>').'</body>';
$html = str_get_html($data);

foreach($html->find('.pro-results') as $item)
{

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $item->find('a', 0)->href, '', 'pending', time(), NULL));

}

?>