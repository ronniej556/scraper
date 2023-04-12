<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.manatt.com';
$spider_name = 'manatt';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$i = 1;
while ($i < 47) {

	$data = fetch($base_url.'/search/people?searchmode=anyword&searchtext=&servicesfilter=0&officefilter=0&positionfilter=0&lawschoolfilter=0&aliaspath=%2fSearch%2fPeople&page='.$i);

	$html = str_get_html($data);

	foreach($html->find('.clearfix') as $item)
	{

		if($item->find('h3.name', 0))
		{
			if($item->find('img', 0))
			{
				$image = $base_url.$item->find('img', 0)->src;
			}
			else
			{
				$image = '';
			}

			$values[] = array(
				'url' => $base_url.$item->find('a', 0)->href,
				'image' => $image,
				'name' => $item->find('h3.name', 0)->plaintext,
				'position' => trim(explode(', ', $item->find('p.detail', 0)->plaintext)[0]),
				'practice' => str_replace('Manatt ', '', explode(', ', $item->find('p.detail', 0)->plaintext)[1]),
				'email' => $item->find('ul.connect li', 0)->plaintext,
				'phone' => str_replace('Phone: ', '', $item->find('ul.connect li', 1)->plaintext)
			);
		}

	}

	$i++;

}

foreach ($values as $row) {

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['url'], json_encode($row), 'pending', time(), NULL));

}

echo count($values);

?>