<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.mdmc-law.com';
$spider_name = 'mdmc';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'Z') as $char) {

    $data = fetch($base_url.'/attorneys?fulltext=&title=&office=&practice=&industry=&school=&admission=&clerkship=&first_letter='.$char);
    $html = str_get_html($data);

    foreach($html->find('.node--type-bio') as $item)
    {
    	$values[] = array(
    		'image' => $base_url.$item->find('img', 0)->src,
    		'url' => $base_url.$item->find('a', 0)->href,
    		'name' => $item->find('.field--name-title', 0)->plaintext,
    		'position' => $item->find('.field--name-field-position', 0)->plaintext,
    		'phone' => $item->find('.field--name-field-phone a', 0)->plaintext,
    		'email' => str_replace('/at/mdmc-law/dot/', '@mdc-law.', str_rot13($item->find('.field__item a', 0)->{'data-mail-to'})),
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