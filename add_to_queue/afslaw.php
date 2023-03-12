<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.afslaw.com';
$spider_name = 'afslaw';

$values = array();

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

//characters A-Z
foreach (range('A', 'Z') as $char) {

    $data = fetch($base_url.'/attorneys?glossary='.$char.'&job_title=All&industries_practices=All&bar_admissions=All&court_admissions=All&office_locations=All&international=All');
    $data = get_string_between($data, '</head>', '</body>').'</body>';
    $html = str_get_html($data);

    foreach ($html->find('.views-row') as $item) {

        $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
        $q->execute(array($spider_name, $base_url.$item->find('a', 0)->href, '', 'pending', time(), NULL));

    }


}

?>