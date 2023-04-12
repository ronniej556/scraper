<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.mvalaw.com';
$spider_name = 'mvalaw';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/people?results#form-search-results');

$html = str_get_html($data);

foreach($html->find('.bioList li') as $item)
{
    if($item->find('.title', 0))
    {
        $values[] = array(
            'image' => $base_url.'/'.$item->find('img', 0)->{'data-lazy'},
            'url' => $base_url.'/'.$item->find('a', 0)->href,
            'name' => $item->find('.title', 0)->plaintext,
            'position' => $item->find('.position', 0)->plaintext,
            'phone' => @$item->find('.phone a', 0)->plaintext,
            'email' => @str_replace('mailto:', '', html_entity_decode($item->find('.email a', 0)->href)),
        );
    }
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