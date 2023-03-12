<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://brownrudnick.com';
$spider_name = 'brownrudnick';

$values = array();

$data = fetch($base_url.'/all-people/');
$data = get_string_between($data, '</head>', '</body>').'</body>';
$html = str_get_html($data);

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

foreach ($html->find('.person__wrapper') as $key => $person) {

    $url = $person->find('a', 0)->href;
    $image = @$person->find('img', 0)->src;
    if(empty($image)) { $image = ''; }

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $url, $image, 'pending', time(), NULL));

}

echo ($key+1);

?>