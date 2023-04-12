<?php

ini_set('max_execution_time', 0);
set_time_limit(0);

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
ob_start();

header('Access-Control-Allow-Origin: *'); 

if(isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] !== 'https')
{
    //header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    //exit();
}

$dir = realpath(dirname(__FILE__));

$dbhost = "localhost";
$dbname = "attorneys_new";
$dbuser = "root";
$dbpass = "";

function send_mail($to, $subject, $message, $from)
{

  $headers = "From: ".strip_tags($from)."\r\n";
  $headers .= "Reply-To: ".strip_tags($from)."\r\n";
  $headers .= "BCC: devin@feis.link\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

  mail($to, $subject, $message, $headers);

}

function firstXChars($string, $chars = 500)
{
    preg_match('/^.{0,' . $chars. '}(?:.*?)\b/iu', $string, $matches);
    return @$matches[0];
}

$pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;port=3306;charset=utf8mb4", $dbuser, $dbpass, array(PDO::ATTR_PERSISTENT => false));

$script_location = ''; //for folders /test or /test1/test2

$root = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$script_location;
$_SEO = array();

if(strpos($script_location, '/') !== false)
{
  $in_folder = explode($script_location, $_SERVER['REQUEST_URI']);
  $next = $in_folder[1];
  foreach (explode('/', $next) as $key => $value)
  {
    $_SEO[$key] = $value;
  }
}
else
{
  foreach (explode('/', $_SERVER['REQUEST_URI']) as $key => $value)
  {
    $_SEO[$key] = $value;
  }
}

unset($_SEO[0]);

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function fetch($url)
{
    $ch = curl_init();

    $url = $url;
    $proxy = 'proxy.crawlera.com:8011';
    $proxy_auth = '7f36ff99ef6b44dab25d69d09c87bb94:';

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_auth);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING , '');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //required for HTTPS

    $scraped_page = curl_exec($ch);

    if($scraped_page === false)
    {
        return 'cURL error: ' . curl_error($ch);
    }
    else
    {
        return $scraped_page;
    }

    curl_close($ch);
}

function fetch_post($url, $content)
{
    $ch = curl_init();

    $url = $url;
    $proxy = 'proxy.crawlera.com:8011';
    $proxy_auth = '7f36ff99ef6b44dab25d69d09c87bb94:';

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_auth);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_ENCODING , '');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //required for HTTPS

    $scraped_page = curl_exec($ch);

    if($scraped_page === false)
    {
        return 'cURL error: ' . curl_error($ch);
    }
    else
    {
        return $scraped_page;
    }

    curl_close($ch);
}

function checkExternalFile($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $retCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $retCode;
}

function sEncode($string)
{
    return iconv(mb_detect_encoding($string, mb_detect_order(), true), "UTF-8", $string);
}

function cfDecodeEmail($encodedString){
  $k = hexdec(substr($encodedString,0,2));
  for($i=2,$email='';$i<strlen($encodedString)-1;$i+=2){
    $email.=chr(hexdec(substr($encodedString,$i,2))^$k);
  }
  return $email;
}

?>