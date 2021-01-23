<meta charset="utf-8">
<?php
    $not_check_login = true;   

    include "common.php";     
   
    $debug = $get["debug"];
	$ret = DBQuery::execute("select * from $dbgm.gm_global where name='monitor'");
	
	$jsondata = json_decode(urldecode($ret[0]["data"]), true);
    if($jsondata["globalenabled"] != true)
    {
        echo "globalenabled = false";
        return;
    }

    $data = $jsondata["list"];
    echo "<br>";
	for($i = 0; $i < count($data); $i++)
	{
		$row = $data[$i];
		if($row["enabled"] == 1)
		{
            $name = $row["name"];
            $varname = $row["varname"];
            $interval = $row["interval"];
            $sql = $row["sql"];
            $value = $row["value"];    
            $comparetype = $row["comparetype"];    
            $times = $row["times"];    
            
            
            
            if($interval < 1) 
                $interval = 1;
			$dt = DBQuery::execute("select count(*) as c from $dbgm.gm_monitor_history_log where name='$varname' and tm>date_add(now(), interval -$interval*60+20 second)");
            if($dt[0]["c"] == 0 || $debug == 1)
            {    
                echo("<br>checking $name ...");
                
                $dt = DBQuery::execute($sql, 1);
                if($dt == null)
                    continue;
                $v = array_values($dt[0]);
                $commenttext = $v[1];
                $v = (double)$v[0];
                
                echo("返回结果：$v $commenttext<br>");  
                $comment = "0x" . bin2hex($commenttext);
                if($comment == "0x") 
                    $comment = "''";
                
                if($debug == 1)
                    continue;
                DBQuery::execute("insert into $dbgm.gm_monitor_history_log(name,tm,value,pass,comment)values('$varname',now(),'$v',if(value$comparetype'$value',0,1),$comment)");
                //print_r("insert into gm_monitor_history_log(name,tm,value)values('$varname',now(),'$v')");    
                
                
                $sql =<<<sql

select c as error_count, count(*) as total_count from $dbgm.gm_monitor_history_log,
(
select min(tm) as m, count(*) as c from 
 (
 select * from $dbgm.gm_monitor_history_log where name='$varname' and value$comparetype'$value' order by tm desc limit $times
 ) t
) t2
where name='$varname' and tm>=m 


sql;
                $dt = DBQuery::execute($sql); 
                if($dt[0]["error_count"] == $dt[0]["total_count"] && $dt[0]["error_count"] == $times)
                {
                    //发邮件
                    $text = "报警: $name = $v, 连续 $times 次 $comparetype $value ";
                    $dotime = date("y-m-d h:i:s");
                    $content = json_encode($req);
                    $sql = "INSERT INTO $dbgm.gm_log(rpc, content, tm) VALUES ('sendmail', '$text', now())"; //插入语句
                    $dt = DBQuery::execute($sql);
                    echo("<font color=red>$text</font><br>");
                    
				    $mailurl = $jsondata["mailurl"];
					if($row["mailurl"] != null && $row["mailurl"] != "")
						$mailurl = $row["mailurl"];
                                                    
                    if($mailurl != null && $mailurl != "")
                    {
                        $msg_title = $text;
                        $msg = $text;
                        $msg .= "\r\n" . $commenttext;
                        $url = sprintf($mailurl, urlencode($msg_title), urlencode($msg));
                        echo($url);
                        //return;
                        $Curl = curl_init();
                        curl_setopt($Curl, CURLOPT_URL, $url);
                        curl_setopt($Curl, CURLOPT_HEADER, false);
                        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 30);
                        curl_setopt($Curl, CURLOPT_SSL_VERIFYPEER, false);
                        $Res = curl_exec($Curl);
                    }                 
                }
            }		
		}
	}

    //print_r($data);
?>
