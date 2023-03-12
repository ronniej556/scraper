<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.hoganlovells.com';
$spider_name = 'hoganlovells';

$values = array();

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

for ($i=1; $i < 500; $i++) {

    $data = fetch($base_url.'/UpdatePeopleSearchResults?sortby=Relevance&language=&pagenum='.$i);
    $html = str_get_html($data);

    if(!$html->find('.person-panel-text', 0)->plaintext)
    {
        break;
    }

    foreach ($html->find('.person-panel-text') as $item) {

        $name = trim($item->find('h3', 0)->plaintext);
        $link = $base_url.$item->find('a', 0)->href;
        $title = trim(explode("\n", strip_tags($item->find('p', 0)->plaintext))[0]);
        $email = trim($item->find('p a', 1)->plaintext);
        $phone = @$item->find('p a', 2)->plaintext;

        $values[] = array(
            'name' => $name,
            'link' => $link,
            'title' => $title,
            'email' => html_entity_decode($email, ENT_COMPAT, 'ISO-8859-1'),
            'phone' => $phone
        );

    }

}

foreach($values as $row)
{
    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['link'], json_encode($row), 'pending', time(), NULL));
}

echo count($values);

?>