<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.wilsonelser.com';
$spider_name = 'wilsonelser';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

	$data = fetch($base_url.'/attorneys?name=&keywords=&letter='.strtolower($char).'&page=1&office%5B%5D=&service%5B%5D=&position%5B%5D=&language_spoken%5B%5D=&law_school%5B%5D=&state_bar%5B%5D=&court%5B%5D=');
	$html = str_get_html($data);

	foreach($html->find('table.listing tr') as $item)
	{
		if($item->find('.listing_name a', 0))
		{

			$values[] = array(
				'name' => $item->find('.listing_name a', 0)->plaintext,
				'url' => $base_url.$item->find('.listing_name a', 0)->href,
				'position' => $item->find('.listing_name strong', 0)->plaintext,
				'location' => $item->find('.attorney_office a', 0)->plaintext,
				'phone' => $item->find('.listing_contact_info span', 0)->plaintext,
				'email' => str_replace('mailto:', '', html_entity_decode($item->find('[data-category="Email"]', 0)->href)),
				'vCard' => $base_url.$item->find('[data-category="vCard"]', 0)->href,
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