<?php  
	session_start();
	$_SESSION["login"] = "1";
	$_SESSION["name"] = "temp";
	header("Location: ".$_GET["url"]);
?>
