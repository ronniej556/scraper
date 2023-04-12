<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.thompsoncoburn.com';
$spider_name = 'thompsoncoburn';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

foreach (range('A', 'B') as $char) {

	$data = fetch($base_url.'/people?lastNameLetter='.$char);
	$html = str_get_html($data);

	foreach($html->find('.professionals tr') as $item)
	{
		if($item->find('.pro-table-cell.search-person-text a', 0))
		{

			$values[] = array(
				'name' => $item->find('.pro-table-cell.search-person-text a', 0)->plaintext,
				'url' => $item->find('.pro-table-cell.search-person-text a', 0)->href,
				'position' => trim($item->find('.pro-table-cell', 1)->plaintext),
				'location' => trim($item->find('.pro-table-cell', 2)->plaintext),
				'phone' => $item->find('.tel-show-not-mobile', 0)->plaintext,
				'email' => str_replace('mailTo:', '', html_entity_decode($item->find('[data-modal-id="popup"]', 0)->href)),
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