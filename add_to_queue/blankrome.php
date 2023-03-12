<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.blankrome.com';
$spider_name = 'blankrome';

$values = array();

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$data = fetch($base_url.'/people?form_id=blankrome_people_people_search&query=&op=Searchform_id=blankrome_people_people_search&first_name=&last_name=&position=&office=&services=&industries=&bar_admissions=&law_school=');
$data = get_string_between($data, '</head>', '</body>').'</body>';
$html = str_get_html($data);

foreach($html->find('.tile-flush') as $item)
{

    if(isset($item->find('img', 0)->src))
    {
        $image = $base_url.$item->find('img', 0)->src;
    }
    else
    {
        $image = '';
    }

    $name = trim($item->find('a', 1)->plaintext);
    $link = $base_url.$item->find('a', 0)->href;
    $title = $item->find('.h4', 1)->plaintext;
    $email = $item->find('span.sub', 0)->plaintext;
    $phone = $item->find('.list-links a', 0)->plaintext;

    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $link, json_encode(array($name, $link, $image, $title, $email, $phone)), 'pending', time(), NULL));

}

?>