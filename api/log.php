<?php
$not_check_login = true;   

include "common.php";     

$device = $get["device"];
$ver = $get["ver"];
$runid = $get["runid"];
$ip = $_SERVER["REMOTE_ADDR"];
$msgid = $get["msgid"];
$msgtype = $get["msgtype"];
$msg = $get["msg"];

if($device == null) die("fail");

$ret = DBQuery::execute("insert into $dbserverlog.log_client(device, ver, runid, ip, msgid, msgtype, msg, dt, tm) values ("
	. tosqlstr($device) . ","
	. tosqlstr($ver) . ","
	. tosqlstr($runid) . ","
	. tosqlstr($ip) . ","
	. tosqlstr($msgid) . ","
	. (int)($msgtype) . ","
	. tosqlstr($msg) . "," .
	"date(now()), now())");

