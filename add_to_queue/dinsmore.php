<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.dinsmore.com';
$spider_name = 'dinsmore';

$values = array();

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

for ($i=1; $i < 26; $i++) {

    $data = fetch($base_url.'/attorneys/page/'.$i.'/?search%5Bkeyword%5D=');
    $data = get_string_between($data, '</head>', '</body>').'</body>';
    $html = str_get_html($data);

    foreach ($html->find('.person-listing') as $item) {

        if(isset($item->find('img', 0)->src))
        {
            $image = $item->find('img', 0)->src;
        }
        else
        {
            $image = '';
        }

        $name = trim($item->find('.name-wrapper a', 0)->plaintext);
        $link = $item->find('.name-wrapper a', 0)->href;
        $title = trim(@$item->find('.position-title ', 0)->plaintext);
        $email = trim($item->find('.person-email-link', 0)->plaintext);
        $phone = @$item->find('a.phone-link', 0)->plaintext;

        $values[] = array(
            'image' => $image,
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