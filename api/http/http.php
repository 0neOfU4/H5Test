<?php
error_reporting (E_ALL & ~E_NOTICE);
$channel = $_GET["channel"];
$filename = $channel.".txt";
if(file_exists($filename)) 
{
	readfile($filename);
}
else
{
	readfile("default.txt");
}
