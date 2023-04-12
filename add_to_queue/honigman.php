<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.honigman.com';
$spider_name = 'honigman';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/professionals');
$html = str_get_html($data);

foreach ($html->find('.results_list li') as $item) {
    $values[] = array(
        'image' => $base_url.'/'.$item->find('img', 0)->src,
        'url' => $base_url.'/'.$item->find('a', 0)->href,
        'name' => $item->find('.title a', 0)->plaintext,
        'position' => $item->find('div.position', 0)->plaintext,
        'phone' => $item->find('div.phone', 0)->plaintext,
        'email' => @str_replace('mailto:', '', html_entity_decode($item->find('.email a', 0)->href)),
        'vCard' => $base_url.'/'.$item->find('.vcard a', 0)->href,
    );
}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        $row['url'],
        json_encode($row),
        'pending',
        time(),
        NULL
    ));

}

echo count($values);

?>