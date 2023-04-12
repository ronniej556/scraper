<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.paulweiss.com';
$spider_name = 'paulweiss';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

for ($i=1; $i <= 20; $i++) {

	$data = fetch($base_url.'/professionalsearchresult?all=all&results=50&page='.$i);
	$html = str_get_html($data);

	foreach($html->find('li') as $item)
	{
		if($item->find('img', 0) && $item->find('.profile-bottom li a', 0))
		{

			$values[] = array(
				'image' => $item->find('img', 0)->src,
				'name' => $item->find('span.name a', 0)->plaintext,
				'url' => $base_url.$item->find('span.name a', 0)->href,
				'location' => $item->find('[for="location"]', 0)->plaintext,
				'phone' => $item->find('span.phone', 0)->plaintext,
				'email' => str_replace('mailto:', '', html_entity_decode($item->find('.email a', 0)->href)),
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