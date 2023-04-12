<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.bracewell.com';
$spider_name = 'bracewell';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

	$data = fetch($base_url.'/people?letter='.$char);

    $html = str_get_html($data);

    foreach($html->find('.people-search-results-container article') as $item)
    {
        if($item->find('.card-name a', 0))
        {

            $values[] = array(
                'name' => $item->find('.card-name a', 0)->plaintext,
                'url' => $base_url.$item->find('.card-name a', 0)->href,
                'position' => trim($item->find('.card-name h4', 0)->plaintext),
                'phone' => trim($item->find('.card-phone a', 0)->plaintext),
                'email' => trim(str_replace('mailto:', '', $item->find('.card-email a', 0)->href)),
                'vCard' => $base_url.$item->find('.card-vcard a', 0)->href
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