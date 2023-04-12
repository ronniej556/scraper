<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.shutts.com';
$spider_name = 'shutts';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

    $data = fetch($base_url.'/professionals?do_item_search=1&letter='.$char);
    $html = str_get_html($data);

    foreach($html->find('#form-search-results li') as $item)
    {
    	if($item->find('img', 0))
    	{
    		$values[] = array(
    			'image' => $base_url.'/'.$item->find('img', 0)->src,
    			'url' => $base_url.'/'.$item->find('a', 0)->href,
    			'name' => $item->find('.title', 0)->plaintext,
    			'position' => $item->find('.position', 0)->plaintext,
    			'phone' => $item->find('.contact .phone', 0)->plaintext,
    			'email' => str_replace('mailto:', '', html_entity_decode($item->find('.contact .email a', 0)->href)),
    			'location' => $item->find('.contact .office a', 0)->plaintext,
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