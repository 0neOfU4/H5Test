<?php
$text = $_GET["text"];
$errtext = base64_encode($text);
$out = shell_exec("webrun 127.0.0.1:1234 147258gbgbkk123q \"lua D:\\xampp\\htdocs\\traceback\\showtrace.lua $errtext\"");
//print($out);
$rs = preg_match("/\[([^\]]+)/", $out, $arr);
print(base64_decode($arr[1]));
