<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.buchalter.com';
$spider_name = 'buchalter';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/attorneys/');
$html = str_get_html($data);

foreach($html->find('[itemtype="http://schema.org/Person"]') as $item)
{

	if($item->find('[itemprop="email"]', 0))
	{
		$image = $item->find('.thumbnail img', 0)->src;
		$url = $item->find('h2.name a', 0)->href;
		$name = $item->find('h2.name a', 0)->plaintext;
		$position = $item->find('.row-title span', 0)->plaintext;
		$location = $item->find('.office-locations li', 0)->plaintext;
		$phone = $item->find('[itemprop="telephone"] a', 0)->plaintext;
		$email = cfDecodeEmail(str_replace('/cdn-cgi/l/email-protection#', '', $item->find('[itemprop="email"]', 0)->href));
		$vCard = $item->find('a[aria-label="vcard"]', 0)->href;

		$values[] = array(
			'image'=>$image,
			'url'=>$url,
			'name'=>$name,
			'position'=>$position,
			'location'=>$location,
			'phone'=>$phone,
			'email'=>$email,
			'vCard'=>$vCard
		);
	}

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>