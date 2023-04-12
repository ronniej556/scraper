<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.kelleydrye.com';
$spider_name = 'kelleydrye';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/SearchPeople?searchtext=&searchmode=anyword&smartsearchfilter3=&smartsearchfilter2=0&smartsearchfilter1=0&smartsearchfilter=0&smartsearchfilter4=0&smartsearchfilter5=0');

$html = get_string_between($data, '</head>', '<body>').'</body>';

$html = str_get_html($html);

foreach ($html->find('.our-people-wrapper a') as $item) {
	if(strpos($item->href, 'Our-People') !== false)
	{
		$values[] = array(
			'url' => $base_url.$item->href,
			'name' => trim(str_replace(array('/Our-People/', '-'), ' ', ucwords($item->href)))
		);
	}
}


foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>