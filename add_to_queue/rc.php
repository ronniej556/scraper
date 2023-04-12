<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://rc.com';
$spider_name = 'rc';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/people/index.cfm');
$html = str_get_html($data);

foreach($html->find('#people-listing-grid li.item') as $item)
{
	if($item->find('a', 0))
	{
		$values[] = array(
			'image' => $base_url.$item->find('img', 0)->src,
			'url' => $base_url.$item->find('a', 0)->href,
			'name' => $item->find('.p-first-name', 0)->plaintext.' '.$item->find('.p-last-name', 0)->plaintext,
			'position' => $item->find('.p-title', 1)->plaintext,
			'phone' => $item->find('.contact-phone a', 0)->plaintext,
			'email' => $item->find('.contact-email a', 0)->plaintext,
			'vCard' => $base_url.$item->find('.dl-vcard a', 0)->href,
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