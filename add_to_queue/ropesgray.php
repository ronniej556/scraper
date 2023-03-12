<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.ropesgray.com';
$spider_name = 'ropesgray';

$values = array();

$i = 0;

while ($i < 1800) {

    $data = fetch($base_url.'/en/utility/search-results.aspx?sf=1&sx='.$i.'&as=0&ps=0&is=0&ns=2&os=0');
    $data = get_string_between($data, '</head>', '</body>').'</body>';
    $html = str_get_html($data);

    foreach($html->find('.search-results-listing li') as $item)
    {

        if($item->find('h4', 0)->plaintext)
        {

            if(isset($item->find('img', 0)->src))
            {
                $image = $base_url.$item->find('img', 0)->src;
            }
            else
            {
                $image = '';
            }

            $name = trim($item->find('h4', 0)->plaintext);
            $link = $base_url.$item->find('a', 0)->href;
            $title = trim($item->find('p.title', 0)->plaintext);
            $email = $item->find('.email', 0)->plaintext;
            $phone = $item->find('.phone', 0)->plaintext;

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

    $i = $i+24;

}

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

foreach($values as $row)
{
    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array($spider_name, $row['link'], json_encode($row), 'pending', time(), NULL));
}

echo count($values);

?>