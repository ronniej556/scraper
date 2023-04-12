<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.klgates.com';
$spider_name = 'klgates';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

	$data = fetch($base_url.'/Bio/Search?LangCode=en-US&pageSize=9999&alpha='.$char);

    $html = str_get_html($data);

    foreach($html->find('.s-bio-card') as $item)
    {
        if($item->find('a.s-bio-card__heading', 0))
        {

            $values[] = array(
                'image' => $item->find('img', 0)->src,
                'name' => $item->find('a.s-bio-card__heading', 0)->plaintext,
                'url' => $base_url.$item->find('a.s-bio-card__heading', 0)->href,
                'position' => trim($item->find('.s-bio-card__info', 0)->plaintext),
                'phone' => @trim($item->find('.s-bio-card__phone', 0)->plaintext),
                'email' => trim(str_replace('mailto:', '', $item->find('a.s-bio-card__email', 0)->href)),
                'vCard' => $base_url.$item->find('.s-bio-card__vcard', 0)->href
            );
        }

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