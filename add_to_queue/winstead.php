<?php

include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.winstead.com';
$spider_name = 'winstead';

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$values = array();

$data = fetch($base_url.'/People/Search?alpha=ALL');

$html = str_get_html($data);

foreach($html->find('.searchresult-group li') as $item)
{
    if($item->find('img', 0))
    {
        foreach($item->find('a') as $link)
        {
            if(strpos($link->href, '@') !== false)
            {
                $email = $link->plaintext;
            }
        }

        foreach($item->find('a') as $link)
        {
            if(strpos($link->href, 'vCard') !== false)
            {
                $vCard = $base_url.get_string_between($link->href, 'javascript:vCard("', '")');
            }
        }

        $values[] = array(
            'image' => $base_url.'/'.str_replace('../', '', $item->find('img', 0)->src),
            'url' => $base_url.'/'.str_replace('../', '', $item->find('a', 0)->href),
            'name' => $item->find('.atty_name a strong', 0)->plaintext,
            'position' => @$item->find('div.atty_title', 0)->plaintext,
            'phone' => $item->find('.atty_col3 span', 0)->plaintext,
            'email' => $email,
            'vCard' => $vCard
        );
    }
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