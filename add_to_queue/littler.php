<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.littler.com';
$spider_name = 'littler';

$values = array();

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

for ($i=0; $i < 80; $i++) {

    $data = fetch($base_url.'/lawyer-search?page='.$i);
    $data = get_string_between($data, '</head>', '</body>').'</body>';
    $html = str_get_html($data);

    foreach ($html->find('.lawyer-grid') as $item) {

        if(isset($item->find('img', 0)->src))
        {
            $image = $item->find('img', 0)->src;
        }
        else
        {
            $image = '';
        }

        $name = trim($item->find('h2.node-title', 0)->plaintext);
        $link = $base_url.$item->find('h2 a', 0)->href;
        $title = trim($item->find('.field-title-display', 0)->plaintext);
        $email = $item->find('.field-name-field-email', 0)->plaintext;
        $phone = @$item->find('.field-name-field-phone', 0)->plaintext;

        $values[] = array(
            'image' => $image,
            'name' => $name,
            'link' => $link,
            'title' => $title,
            'email' => $email,
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