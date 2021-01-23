<?php
function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    return $length === 0 || 
    (substr($haystack, -$length) === $needle);
}

$url = $_SERVER["REQUEST_URI"];
$url = str_replace("/php/http://localhost:8080/","/", $url);
if (endsWith($url, ".php"))
	require $url;
else 
	return false;
