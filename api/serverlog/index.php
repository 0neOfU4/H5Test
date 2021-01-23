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
mysql_query("set names utf8");
mysql_select_db($dbserverlog,$conn);

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

$hosts = execute("select host from server_log group by host");

if ($_GET["m"] == "1")
{
    $t1 = $_GET["t1"];
    $t2 = $_GET["t2"];
	if($t1 == null) $t1 = date('Y-m-d H:i:s',time()-60);
	if($t2 == null) $t2 = date('Y-m-d H:i:s',time()+30*24*3600);
    $host = $_GET["host"];
    $host_sql = "";
    if($host != null)
        $host_sql = "and host='$host'";
        
    $server_type = $_GET["server_type"];
    $server_type_sql = "";
    if($server_type != null)
        $server_type_sql = "and server_type='$server_type'";
        
    $server_no = $_GET["server_no"];
    $server_no_sql = "";
    if($server_no != null)
        $server_no_sql = "and server_no='$server_no'";
    
	$log_path = $_GET["path"];
	$log_path_sql = "";
    if($log_path != null)
        $log_path_sql = "and path="."0x".bin2hex($log_path);

	$log_text = $_GET["text"];
	$log_text_sql = "";
    if($log_text != null)
        $log_text_sql = "and log like '%$log_text%'";

    $f = $_GET["f"];
    $f_sql = "";
    if($f != "1")
        $f_sql = "and log_level=3";    
    
    $sql = "select * from server_log where log_time>='$t1' and log_time<='$t2' $server_type_sql $server_no_sql $log_path_sql $f_sql $host_sql $log_text_sql order by log_time desc, id desc limit 5000";
    //print($sql);
    $dt = execute($sql);
    
    echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8" />';
    echo "<table align='center' width='90%'><tr><td bgcolor='#EFEFEF'>";
    echo "<div align='left'><form action='index.php'>";
    

    //host
    echo "Host:<select name='host'>";
    if ($host == null)
    {
        echo "<option value='' selected>ALL</option>";
    }
    else
    {
        echo "<option value=''>ALL</option>";
    }
    for($j = 0; $j < count($hosts); $j++)
    {
        $host1 = $hosts[$j]["host"];
        echo "<option value='$host1' ";
        if ($host == $host1) echo "selected";
        echo ">$host1</option>";
    }
    echo "</select>";
          
    //server type
    echo "Server:<select name='server_type'>";
    if ($host == null)
    {
        echo "<option value='' selected>ALL</option>";
    }
    else
    {
        echo "<option value=''>ALL</option>";
    }
    $servers = array('gameserver', 'gateserver', 'zoneserver', 'loginserver', 'dockerserver');
    for($j = 0; $j < count($servers); $j++)
    {
        $server = $servers[$j];
        echo "<option value='$server' ";
        if ($server == $server_type) echo "selected";
        echo ">$server</option>";
    }
    echo "</select>";
    
    
    //server no
    echo "No:<select name='server_no'>";
    if ($host == null)
    {
        echo "<option value='' selected>ALL</option>";
    }
    else
    {
        echo "<option value=''>ALL</option>";
    }
    for($j = 0; $j < 8; $j++)
    {
        $serverno = ($j + 1) + "";
        echo "<option value='$serverno' ";
        if ($serverno == $server_no) echo "selected";
        echo ">$serverno</option>";
    }
    echo "</select>";
    
	//log_path
	echo " Path:<input type='text' value='$log_path' name='path'>";

	//log_path
	echo " 模糊搜索:<input type='text' value='$log_text' name='text'>";

    //from & to
    echo " From:<input type='text' value='$t1' name='t1'>";
    echo " To:<input type='text' value='$t2' name='t2'>";

    echo " 显示所有日志:<input type='checkbox' name='f' value='1' ";
    if ($f == 1) echo "checked";
    echo ">";

	echo " <input type='hidden' name='m' value='1'> ";
    echo " <input type='submit' value='O K'>";
    echo "</form></div>";
    
    for($i = count($dt) - 1; $i >= 0; $i--)
    {
        $host = $dt[$i]["host"];
        $server_type = $dt[$i]["server_type"];
        $server_no = $dt[$i]["server_no"];
        $path = $dt[$i]["path"];
        $message = trim($dt[$i]["log"], "\n");
        //$message = iconv("GBK","UTF-8",$message);
        $sourcemsg = $message;
        $message = str_replace("&", "&amp;",$message); 
        $message = str_replace("<", "&lt;",$message);
        $message = str_replace(">", "&gt;",$message);
        $log_time = $dt[$i]["log_time"];
        $log_level = $dt[$i]["log_level"];
        $log_path = $dt[$i]["path"];
        if ($log_level == 3) $message =  "<a href='http://192.168.2.18/traceback/traceback.php?text=".urlencode($sourcemsg)."' target='_blank'>More</a><font color='red'>$message</font>";
        
        $title = "<b>$host: $server_type"."_$server_no</b> path:$log_path";
        if($lasttitle == $title)
        {
            echo "$message\n";    
        }
        else
        {
            if($lasttitle != null)
				echo "</pre>";
            echo "<hr>$title <b>$log_time</b><br>";  
            echo "<pre>$message\n"; 
        }
        $lasttitle = $title;        
    }
    
    echo "</pre>共:" . count($dt) . "条(最多显示5000条)<br><br></td></tr></table>";
	return;
}

$time = $_GET["t"];
if($time == null || strtotime($time) > time())
    $time = date("Y-m-d", time()) ;


$pagesize = $_GET["p"];
if($pagesize == null)
    $pagesize = 7;

?>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<style type="text/css">
    body
{
    margin: 0;
    padding: 0;
    color: #000000;
    /* font: normal 10pt Arial,Helvetica,sans-serif;*/
    background: #EFEFEF;
}
a
{
    color:#000000;
    background-color:transparent;
    font-size:15px;
    /*font-weight:bold;*/
    text-decoration:none;
    padding:0px 8px;
}

a:hover
{
    color1: #000000;
    text-decoration:none;
}

table.datagrid1 {
    border-collapse: collapse;
    border-bottom: 1px solid #666;
    border: 1px #33 solid;
}

.top_menu{
    padding: 8px 15px;
    margin: 0 0 20px;
    list-style: none;
    background-color: #f5f5f5;
    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
    border-radius: 4px;
}

table.datagrid1 th {
    color: #333;
    padding: 3px;
    font-family: monospace;
    background: #9cf;
    text-align: left;
    border-top: 1px solid #666;
    border-bottom: 1px solid #666;
}

table.datagrid1 td {
    padding: 3px;
    border-top: 1px solid #ccc;
}

table.datagrid2 {
    border-collapse: collapse;
    /*margin-left: 10px;*/
}

table.datagrid2 th {
    text-align: center;
    background: #9cf;
    padding: 3px;
    border: 1px #333 solid;
}

table.datagrid2 td {
    padding: 3px;
    border: none;
    border: 1px #333 solid;
    cursor: default;
}

table.datagrid2 tr:hover {
    background: #BFBEFF;
}

input {
    border: 1px solid #000000;
    background: #ffffff;
}

body {
    background-color: #F7EED6;
}




</style>


<br>

<p align="center"><font size="+2">Server Logs</p>
<p align="center">
	<a href="?p=30&t=<?=$time?>">30天</a> 
	<a href="?p=7&t=<?=$time?>">7天</a> 
</p>


<table class="datagrid2" align="center">
    <tr>
        <th>server</th>
        <th>start</th>
        <th>latest</th>
        <?php
        for($i = $pagesize - 1; $i >= 0; $i--)
        {
            $t = mktime(0,0,0,date('m',strtotime($time)),date('d',strtotime($time)) - $i, date('Y',strtotime($time)));
            $weekarray=array("日","一","二","三","四","五","六");
            $d = date("m-d", $t);
			$dt1 = date("Y-m-d", $t);
            $w = $weekarray[date("w", $t)];
            echo "<th><a target='_blank' href='index.php?m=1&t1=$dt1&t2=$dt1+23:59:59'>$d</a><br>$w</th>";
        }
        ?>
    </tr>
    <?php
	for($j = 0; $j < count($hosts); $j++)
    {
        $auth = $hosts[$j]["host"];
        $time1 = $hosts[$j]["t"];
        $time2 = $hosts[$j]["t2"];  
		$t1 = date("Y-m-d", mktime(0,0,0,date('m',strtotime($time)),date('d',strtotime($time)) - $pagesize + 1, date('Y',strtotime($time))));
		$t2 = date("Y-m-d", mktime(0,0,0,date('m',strtotime($time)),date('d',strtotime($time)) - 0, date('Y',strtotime($time))));
        echo "<tr>";
        echo "<td><a target='_blank' href='index.php?m=1&host=$auth&t1=$t1&t2=$t2+23:59:59'>$auth</a></td>";
        echo "<td>$time1</td>";
        echo "<td>$time2</td>"; 
        $sql = "";
		$dd = array();
        for($i = $pagesize - 1; $i >= 0; $i--)
        {
            $d = date("Y-m-d", mktime(0,0,0,date('m',strtotime($time)),date('d',strtotime($time)) - $i, date('Y',strtotime($time))));

//select sum(count) as c from cache where log_date='$d' and host='$auth' and log_level<>3


			$sqltmp = "select sum(count) as c from cache where log_date='$d' and host='$auth'";
            $sql = $sql . "select ($sqltmp) as s, ($sqltmp and log_level=3) as s1";
            if($i > 0)
                $sql = $sql . " union all ";
			$dd[] = $d;
        }
		//echo $sql;
		//return;
        $dt = execute($sql);
        for($i = 0; $i < count($dt); $i++) 
        {
			$t = $dd[$i];
            $logline = $dt[$i]["s"];
            $logline2 = $dt[$i]["s1"];
		
			$logline = "<a target='_blank' href='index.php?m=1&host=$auth&t1=$t&t2=$t+23:59:59&f=1'>$logline</a>";
			if ($logline2 > 0)
				$logline2 = "<font color='red'><b>$logline2</b></font>";
			$logline2 = "<a target='_blank' href='index.php?m=1&host=$auth&t1=$t&t2=$t+23:59:59&f=0'>$logline2</a>";
			echo "<td> $logline <br> $logline2 </td>";
        }   
        echo "</tr>";
    }
    ?>
</table>
<?php
	$d1 = date("Y-m-d", mktime(0,0,0,date('m',strtotime($time)),date('d',strtotime($time)) - $pagesize, date('Y',strtotime($time))));
	$d2 = date("Y-m-d", mktime(0,0,0,date('m',strtotime($time)),date('d',strtotime($time)) + $pagesize, date('Y',strtotime($time))));
?>
<p align="center">
	<a href="index.php?p=<?=$pagesize?>&t=<?=$d1?>">上一页</a> 
	<a href="index.php?p=<?=$pagesize?>&t=<?=$d2?>">下一页</a>
	<a href="index.php?p=<?=$pagesize?>&t=<?=date("Y-m-d", time())?>">今天</a>
</p>
