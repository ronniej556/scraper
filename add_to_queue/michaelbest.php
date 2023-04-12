<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.michaelbest.com';
$spider_name = 'michaelbest';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/webportal/serveContent.v?src=ajax-website-search-result-lazy-load&guid=85a974b8bca82984cc4dc4c9d6989ea2:37003:33101/people&page=1&hitsPerPage=99999&urlPrefix=../&showIcons=true');

$html = str_get_html($data);

foreach($html->find('li.bio_item') as $item)
{
    $values[] = array(
        'image' => $base_url.'/'.str_replace('../', '', $item->find('img', 0)->src),
        'url' => $base_url.'/'.str_replace('../', '', $item->find('a', 0)->href),
        'name' => $item->find('.bio_name', 0)->plaintext,
        'position' => $item->find('.bio_title', 0)->plaintext,
        'phone' => $item->find('.bio_direct.phone_num a', 0)->plaintext,
        'email' => $item->find('.bio_email a', 0)->plaintext,
        'vCard' => $base_url.'/'.str_replace('../', '', $item->find('.bio_icon.atty_vcard.icon-vcard', 0)->href),
    );
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