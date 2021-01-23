<?php   
$res = array(
    "result" => 4
);
include "common.php";

$allow_url = array(
    "/gm/player/",
    "/gm/server/notice",     
    "/gm/server/mail",     
);  


$url = $req["url"];
$method = $req["method"];

$find = 0;
foreach($allow_url as $v)
{
    if(strpos($url, $v) === FALSE)
        continue;
    $find = 1;
}

if($find == 0)
{
    echo(json_encode($res));    
    die();
}

if(strpos($url, "?")>0)
{
    $url .= "&__key=" . $login_server_key;
}
else
{
    $url .= "?__key=" . $login_server_key;    
}

$url = $login_server . $url;

if($method == "post")
{
    echo(curl_post($url, $req));
}
else
{
    echo(curl_get($url));
}
