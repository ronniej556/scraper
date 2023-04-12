<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.stroock.com';
$spider_name = 'stroock';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

for ($i=1; $i <= 15; $i++) {

	$data = fetch($base_url.'/people/search?first_name%2Clast_name=&position=0&office=0&school_name=0&services=0&s=&submit=Search&page='.$i);

	$html = str_get_html($data);

	foreach($html->find('.attorney-thumb.card') as $item)
	{
		$values[] = array(
			'url' => $item->find('a', 0)->href,
			'name' => $item->find('h3', 0)->plaintext,
			'position' => $item->find('p', 0)->plaintext,
			'location' => $item->find('p', 1)->plaintext,
			'phone' => $item->find('p', 2)->plaintext,
			'email' => $item->find('p', 3)->plaintext,
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