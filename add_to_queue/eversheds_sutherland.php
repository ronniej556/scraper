<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.eversheds-sutherland.com';
$spider_name = 'eversheds_sutherland';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

    $data = fetch($base_url.'/global/en/who/people/search.page?name_A_to_Z='.strtolower($char).'&Management_Title=&Office=&country=&Legal_Services=&Legal_Sector=&frmSubmit=Go');
    $html = str_get_html($data);

    foreach($html->find('.peopleTable tr') as $item)
    {
    	if($item->find('a', 0))
    	{
    		$values[] = array(
    			'url' => $base_url.$item->find('a', 0)->href,
    			'name' => trim($item->find('a', 0)->plaintext),
    			'position' => trim($item->find('td', 1)->plaintext),
    			'phone' => trim($item->find('td', 2)->plaintext),
    			'country' => trim($item->find('td', 3)->plaintext),
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