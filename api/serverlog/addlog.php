<?php
error_reporting(E_ALL ^ E_NOTICE);
require("../db.php");       
/*
$dbhost="127.0.0.1";
$dbuser="root";
$dbpwd="12345678";
$dbport="3306";
$dbquery="test";
*/
$conn = mysql_connect($dbhost.":".$dbport,$dbuser,$dbpwd);
if (!$conn) die ("对不起，发生错误！ 请检查conn.php中数据库的配置是否正确！");
mysql_query("set names gbk");
mysql_select_db($dbserverlog,$conn);
$req = json_decode(file_get_contents("php://input"));
$insert_id = 0;
for($i = 0; $i < count($req); $i++)
{    
    $host = $req[$i]->host;
    $server_type = $req[$i]->server_type;
    $server_no = $req[$i]->server_no;
    $path = "0x".bin2hex($req[$i]->path);
    $log_time = $req[$i]->log_time;
    $log = base64_decode($req[$i]->log);
    $log_level = 1;
    if(strpos($log, "collectgarbage:") || strpos($log, "[mysql]") || strpos($log, "[loglib]") || strpos($log, "f_onconn") || strpos($log, "f_onclose"))
        $log_level = 0;
    if(strpos($log, ".lua:") > 0 || strpos($log, "Error:") > 0 || strpos($log, "Can't get result") > 0)
        $log_level = 3;
    $log = "0x".bin2hex($log);
    $sql = "insert into server_log(host, server_type, server_no, path, log_level, log_time, log) values ('$host', '$server_type', '$server_no', $path, $log_level, '$log_time', $log)";
    $result = mysql_query($sql);
    if(!$result)
        print("Error: $sql\n");
    $insert_id = mysql_insert_id();

    //update counter
    $sql = "insert into cache(host,log_level, log_date, log_hour, count) values ('$host',$log_level,date('$log_time'),hour('$log_time'),1) on DUPLICATE key update count=count+1";
    $result = mysql_query($sql);
}
print("SUCCESS");

function execute($sql){
    $result = mysql_query($sql);   
    if (!$result) {
        die('Invalid query: ' . mysql_error() . "<br> sql: $sql");
    }
    $data_list = array();                
    if ($result != 1 && !empty($result))
    {
        $i = 0;        
        while($row = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            $data_list[$i] = $row;
            $i++;
        }
    }
    else
    {           
        return $result;
    }        
    return $data_list;
}

function logError($content)
{
  $logfile = 'debuglog'.date('Ymd').'.txt';
  if(!file_exists(dirname($logfile)))
  {
    @File_Util::mkdirr(dirname($logfile));
  }
  error_log(date("[Y-m-d H:i:s]")." -[".$_SERVER['REQUEST_URI']."] :".$content."\n", 3,$logfile);
}

return;		//服务器报警挪后台去了，这里不报警了。


//check every - now < 10s 
$sql = "update mail_send set last_check_time=now() where now() - last_check_time > 10";
execute($sql);
if(mysql_affected_rows() == 0) 
    return;

//if last_send - now < 900s, return
$sql = "select * from mail_send where last_send + 3600 * 24 < now();";
$dt = execute($sql);
if(count($dt) == 0) 
    return;
    
//check error
$last_check_id = $dt[0]["last_check_id"];
$sql = "select host, server_type, count(*) as c, log from server_log where log_level=3 and id>$last_check_id and id <=$insert_id group by host, server_type";
$dt = execute($sql);

$msg = "";
$msg_title = "[log]";
for($i = 0; $i < count($dt); $i++)
{
    $msg .= $dt[$i]["host"]."_".$dt[$i]["server_type"].":".$dt[$i]["c"]." \n";
    $msg .= $dt[$i]["log"] . "\n\n";
    $msg_title .= $dt[$i]["host"].":".$dt[$i]["c"]." ";
}
$sql = "update mail_send set last_check_id=$insert_id";
execute($sql);

if(count($dt) == 0) 
    return;   

$sql = "update mail_send set last_send=now()";
execute($sql);
    
$url = sprintf($mailurl, urlencode($msg_title), urlencode($msg));
logError($url);
//echo($url);
//return;
$Curl = curl_init();
curl_setopt($Curl, CURLOPT_URL, $url);
curl_setopt($Curl, CURLOPT_HEADER, false);
curl_setopt($Curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($Curl, CURLOPT_SSL_VERIFYPEER, false);
$Res = curl_exec($Curl);
//print($msg);
?>