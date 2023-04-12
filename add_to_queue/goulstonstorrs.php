<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.goulstonstorrs.com';
$spider_name = 'goulstonstorrs';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$i = 1;
while ($i<=10) {
	
	$data = fetch($base_url.'/people/page/'.$i.'/?search%5Bkeyword%5D=');
	$html = str_get_html($data);

	foreach($html->find('.person-listing.person') as $item)
	{
		if($item->find('a', 0))
		{
			$values[] = array(
				'image' => $item->find('img', 0)->src,
				'url' => $item->find('a', 0)->href,
				'name' => trim($item->find('.name-wrapper a', 0)->plaintext),
				'position' => @trim($item->find('.person-title.position-title', 0)->plaintext),
				'phone' => @trim($item->find('a.phone-link', 0)->plaintext),
				'email' => trim(html_entity_decode($item->find('.person-email-link a', 0)->plaintext)),
			);
		}
	}

	$i++;
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