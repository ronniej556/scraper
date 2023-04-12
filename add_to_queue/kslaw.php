<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.kslaw.com';
$spider_name = 'kslaw';

$values = array();

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

for ($i=1; $i < 7; $i++) {

    $data = fetch($base_url.'/people?locale=en&page='.$i.'&per_page=250');
    $data = get_string_between($data, '</head>', '</body>').'</body>';
    $html = str_get_html($data);

    foreach($html->find('.person') as $item)
    {

        if(isset($item->find('.box h2 a', 0)->plaintext))
        {
            if(isset($item->find('img', 0)->src))
            {
                $image = $item->find('img', 0)->src;
            }
            else
            {
                $image = '';
            }

            $name = trim($item->find('.box h2 a', 0)->plaintext);
            $link = $base_url.$item->find('a', 0)->href;
            $title = trim($item->find('.box p', 0)->plaintext);
            $email = $item->find('.contacts a', 1)->plaintext;
            $phone = $item->find('.contacts a', 0)->plaintext;

            $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
            $q->execute(array($spider_name, $link, json_encode(array($name, $link, $image, $title, $email, $phone)), 'pending', time(), NULL));
        }

    }

}

?>