<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.icemiller.com';
$spider_name = 'icemiller';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

	$data = fetch($base_url.'/people/attorneys/search/?letter='.$char.'#results');
	$html = str_get_html($data);

	foreach($html->find('.results-table tr') as $item)
	{
		if($item->find('a.name', 0))
		{

			$values[] = array(
				'name' => $item->find('a.name', 0)->plaintext,
				'url' => $base_url.$item->find('a.name', 0)->href,
				'position' => trim($item->find('td', 1)->plaintext),
				'location' => trim($item->find('td', 4)->plaintext),
				'phone' => trim($item->find('td', 2)->find('a', 0)->plaintext),
				'email' => trim($item->find('td', 3)->plaintext),
				'vCard' => $base_url.$item->find('a.v-card', 0)->href
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