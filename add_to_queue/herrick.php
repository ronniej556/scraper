<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.herrick.com';
$spider_name = 'herrick';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$i = 3;
while ($i<=3) {
	
	$data = fetch($base_url.'/people/?search%5Bname%5D&view=all&paged='.$i);
	$html = str_get_html($data);

	foreach($html->find('article.person') as $item)
	{
		if($item->find('a', 0))
		{
			$values[] = array(
				'image' => $item->find('img', 0)->src,
				'url' => $item->find('a', 0)->href,
				'name' => trim($item->find('.person-name-position a', 0)->plaintext),
				'position' => @trim($item->find('span.person-position-title', 0)->plaintext),
				'phone' => @trim(str_replace('Tel: ', '', $item->find('.person-phone', 0)->plaintext)),
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