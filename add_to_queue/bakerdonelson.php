<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.bakerdonelson.com';
$spider_name = 'bakerdonelson';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

for ($i=1; $i <= 36; $i++) {

	$data = fetch($base_url.'/professionals?all=1&page='.$i.'#results');
	$html = str_get_html($data);

	foreach($html->find('.sharedbiolist-item') as $item)
	{
		$image = $base_url.$item->find('.sharedbiolist-pic.noprint', 0)->{'data-lazybg'};
		$url = $base_url.$item->find('.sharedbiolist-name a', 0)->href;
		$name = trim($item->find('.sharedbiolist-name a', 0)->plaintext);
		$position = $item->find('.sharedbiolist-title', 0)->plaintext;
		$location = $item->find('.sharedbiolist-office', 0)->plaintext;
		$phone = @$item->find('.sharedbiolist-phonelink-label', 0)->plaintext;
		$email = @str_replace('mailto:', '', $item->find('[title="Email Professional"]', 0)->href);

		$values[] = array(
			'image'=>$image,
			'url'=>$url,
			'name'=>$name,
			'position'=>$position,
			'location'=>$location,
			'phone'=>$phone,
			'email'=>$email
		);
	}

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>