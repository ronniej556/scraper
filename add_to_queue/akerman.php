<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.akerman.com';
$spider_name = 'akerman';

$data = json_decode(file_get_contents($base_url.'/_site/search?&html&v=attorney'), 1);

$html = str_get_html($data['rendered_view']);

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

foreach ($html->find('.people-search-result') as $item) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        $base_url.$item->find('a', 0)->href,
        $base_url.$item->find('img', 0)->src,
        'pending',
        time(),
        NULL
    ));

}

?>