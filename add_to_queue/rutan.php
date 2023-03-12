<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.rutan.com';
$spider_name = 'rutan';

$values = array();

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/attorneys/');
$data = get_string_between($data, '</head>', '</body>').'</body>';
$html = str_get_html($data);

foreach($html->find('.staff-archive-block') as $item)
{

    if(isset($item->find('img', 0)->src))
    {
        $image = $item->find('img', 0)->src;
    }
    else
    {
        $image = '';
    }

    $name = trim($item->find('.contact-card-name', 0)->plaintext);
    $link = $item->find('.contact-card-name a', 0)->href;
    $title = trim($item->find('.contact-card-title', 0)->plaintext);
    $email = str_replace('mailto:', '', $item->find('.contact-card-email', 0)->href);
    $phone = str_replace('tel:', '', $item->find('.contact-card-phone a', 0)->href);

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $link, json_encode(array($name, $link, $image, $title, $email, $phone)), 'pending', time(), NULL));

}

?>