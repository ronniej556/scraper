<?php
include '../config.php';
include '../simple_html_dom.php';

$base_url = 'https://www.allenmatkins.com';
$spider_name = 'allenmatkins';

$values = array();

$i = 0;

while($i<260)
{
    $f = file_get_contents($base_url.'/_site/search?f='.$i.'&v=attorney');
    foreach (json_decode($f, 1)['hits']['ALL']['hits'] as $key => $value) {
        if(!empty($key))
        {
            $values[] = $value;
        }
    }
    $i = $i+20;
}

$q = $pdo->prepare('DELETE FROM `queue` WHERE `spider_name`=?');
$q->execute(array($spider_name));

$q = $pdo->prepare('DELETE FROM `people` WHERE `spider_name`=?');
$q->execute(array($spider_name));

foreach($values as $value)
{
    $q = $pdo->prepare('INSERT INTO `queue` VALUES (?, ?, ?, ?, ?, ?)');
    $q->execute(array(
        $spider_name,
        $base_url.$value['url'],
        json_encode($value),
        'pending',
        time(),
        NULL
    ));
}

?>