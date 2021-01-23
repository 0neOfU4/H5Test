<?php
	//error_reporting(E_ALL^E_NOTICE); 
	error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));
    date_default_timezone_set('Asia/Shanghai');

	$input = file_get_contents("php://input");
    $req = json_decode($input, true);

	include "PHPSocket.class.php";
    include "db.php";
    include "pagelib.php"; 
    include "safe.php"; 
     
    class runtime
    { 
        var $StartTime = 0; 
        var $StopTime = 0; 
     
        function get_microtime() 
        { 
            list($usec, $sec) = explode(' ', microtime()); 
            return ((float)$usec + (float)$sec); 
        } 
     
        function start() 
        { 
            $this->StartTime = $this->get_microtime(); 
        } 
     
        function stop() 
        { 
            $this->StopTime = $this->get_microtime(); 
        } 
     
        function spent() 
        { 
            return round(($this->StopTime - $this->StartTime) * 1000, 1); 
        } 
     
    }
    //查询
    class DBQuery
    {
        var $m_format = "";
        var $m_page_size = 50;
        var $m_varList = array();
        function DBQuery($format)
        {
            $this->m_format = $format;
        }
        
        static function execute($sql, $ignoreerror = null)
        {
            global $conn;
            $rows = array();
            mysqli_multi_query($conn, "set names utf8");
            if(mysqli_multi_query($conn, $sql))
            {
                do
                {
                    if ($result=mysqli_store_result($conn))
                    {
                        $rows = array();
                        while($row =mysqli_fetch_assoc($result))
                            $rows[] = $row;   
                        mysqli_free_result($result);
                    }
                }
                while (mysqli_next_result($conn));
            }
            else
            {
                echo $sql . " Failed. <br>" . mysqli_error($conn) . "<br>";
                if($ignoreerror == null)
                    die();
                else
                    return null;
            }
            return $rows;
        }
        
        static function getData($format, $req)
        {
            preg_match_all('/\\{([^\\}]*)\\}/', $format, $matches); 
            $matches = $matches[1];
            
            //sql组装
            function cb_hua1($matches)
            {
              global $req;
              $value = $req[$matches[1]];
              if($value == null || $value == "")
                return "";
              else
                return $matches[0]; 
            }
            function cb_hua2($matches)
            {
              global $req;
              $value = $req[$matches[1]];
              if($value == null || $value == "")
                return "";
              else
                return str_replace("'", "\\'", $value); 
            }
            function cb_fang($matches)
            {
              $str1 = preg_replace_callback('/\\{([^\\}]*)\\}/', "cb_hua1", $matches[1]);
              $str2 = preg_replace_callback('/\\{([^\\}]*)\\}/', "cb_hua2", $matches[1]);
              if($str1 == $matches[1])
                return $str2;
              else 
                return "";
            }
            $sql = preg_replace_callback('/\\[([^\\]]*)\\]/', "cb_fang", $format);
            
            function cb_count1($matches)
            {
              return "count(*)";
            }
            
            function cb_count2($matches)
            {
              return $matches[1];
            }
            $sql_count = preg_replace_callback('/<<(.*)>>/', "cb_count1", $sql);
            $sql = preg_replace_callback('/<<(.*)>>/', "cb_count2", $sql);
            
            //print("<br>sql:$sql");
            //print("<br>sql_count:$sql_count<br>");
            
            //翻页处理
            $now_page = 1; $page_size = 100;
            if($req["pageno"] != null) $now_page = $req["pageno"];
            if($req["pagesize"] != null) $page_size = $req["pagesize"];
            $total_count = 0;
            {
                $result = mysql_query($sql_count);
                if($result == null) return array(null, 0, $sql);;
                while($row = mysql_fetch_array($result))
                {
                    $total_count = $row[0];
                }
            }
            $sql = $sql . " limit " . ($now_page - 1) * $page_size . "," . $page_size; 
            $result = DBQuery::execute($sql);            
            return array($result, $total_count, $sql);
        }
        
        function addUrlParam($key)
        {
            array_push($this->m_varList, $key);
        }
        
        function getContext()
        {
            global $get;
            $out = "";
            
            //查询框
            preg_match_all('/\\{([^\\}]*)\\}/', $this->m_format, $matches); 
            $matches = $matches[1];
            if (count($matches) > 0)
            {
                $out .= "<form action='' method='get'><table align='center'>";
                foreach($matches as $name)
                {
                    $value = $get[$name];
                    $out .= "<tr><td>$name : </td><td><input type='text' name='$name' value='$value'></td></tr>";        
                }    
                foreach($this->m_varList as $name)        
                {
                    $value = $get[$name];
                    $out .= "<input type='hidden' name='$name' value='$value'>";
                }
                $out .= "<tr><td colspan=2 align='right'><input type='submit' value='Search'></td></tr></table></form>";    
            }
            
            //sql组装
            function cb_hua1($matches)
            {
              global $get;
              $value = $get[$matches[1]];
              if($value == null || $value == "")
                return "";
              else
                return $matches[0]; 
            }
            function cb_hua2($matches)
            {
              global $get;
              $value = $get[$matches[1]];
              if($value == null || $value == "")
                return "";
              else
                return $value; 
            }
            function cb_fang($matches)
            {
              $str1 = preg_replace_callback('/\\{([^\\}]*)\\}/', "cb_hua1", $matches[1]);
              $str2 = preg_replace_callback('/\\{([^\\}]*)\\}/', "cb_hua2", $matches[1]);
              if($str1 == $matches[1])
                return $str2;
              else 
                return "";
            }
            $sql = preg_replace_callback('/\\[([^\\]]*)\\]/', "cb_fang", $this->m_format);
            
            function cb_count1($matches)
            {
              return "count(*)";
            }
            
            function cb_count2($matches)
            {
              return $matches[1];
            }
            $sql_count = preg_replace_callback('/<<(.*)>>/', "cb_count1", $sql);
            $sql = preg_replace_callback('/<<(.*)>>/', "cb_count2", $sql);
            
            //print("<br>sql:$sql");
            //print("<br>sql_count:$sql_count<br>");
            
            //翻页处理
            $now_page = intval($_GET['p']) == null?1:intval($_GET['p']);
            $page_size = $this->m_page_size;
            $total_count = 0;
            {
                $result = mysql_query($sql_count);
                while($row = mysql_fetch_array($result))
                {
                    $total_count = $row[0];
                }
            }
            $sql = $sql . " limit " . ($now_page - 1) * $page_size . "," . $page_size; 
            
            
            //查询
            mysql_query("set names utf8");
            $result = mysql_query($sql);      
            
            //表头
            $pages = ceil($total_count/$page_size);
            $out .= "<table border='0' width='90%'><tr><td align='right'>共<b>$total_count</b>条记录 共<b>$pages</b>页</td></tr></table>";   
            $out .= "<table border='0' align='center' width='90%'  class='datagrid2'><tr>";
            $i = 0;
            $collist = array();
            while ($i < mysql_num_fields($result)) {
                $meta = mysql_fetch_field($result);
                array_push($collist, $meta->name);
                $out = $out . "<th>" . $meta->name . "</th>";
                $i++;
            }
            $out = $out . "</tr>";
            
            //内容
            $i = 0;
            while($row = mysql_fetch_array($result))
            {
                $out = $out . "<tr>";
                for($i = 0; $i < count($row) / 2; $i++)
                {
                    $out = $out . "<td>" . $this->getShowText($collist[$i], $row[$i]) . "</td>";
                }
                $out = $out . "</tr>";
                $i++;
            }
            
            //表尾
            $out = $out . "</table>";
            
            //翻页处理
            $out .= "<table border='0' align='center'><tr><td>";
            $get_url_str = "?".$_SERVER["QUERY_STRING"]."&p=|";
            $params = array(                      
                    'total_rows'=>$total_count,
                    'method'    =>'html',
                    'parameter' =>$get_url_str,
                    'now_page'  =>$get['p'],
                    'list_rows' =>$page_size,
            );
            $page = new pagelib($params);
            $out .= $page->show(1);//参数为样式
            $out .= "</td></tr></table>";
            
            
            
            return $out;
            //return("sql:$sql<br>" . $out);
        }
        
        function getShowText($colname, $value)
        {
            return $value;    
        }
        
        function show()
        {
            echo $this->getContext();
        }
    }
    
    
    function addOperationLog($logType, $logComment)
    {
        global $conn;
        $user_name = $_SESSION["name"];
        if($user_name == "" || $user_name == null) $user_name = "unkown";
        if($logComment == null) $logComment = "";
        $ip = $_SERVER["REMOTE_ADDR"];
        $sql = "insert into operation_log(user_name, op_type, op_detail, ip, log_time) values ('$user_name', $logType, '$logComment', '$ip', now())";
        mysqli_query($conn, $sql);
    }
    
    session_start();     
    if($not_check_login != true)
    {
        //check login
        if ($_SESSION["login"] != "1")  die('{"result":-1001}');
    }
    
    function tosqlstr($str){
        return "0x".bin2hex($str);
    }
    
    function execute($sql)
    {
        mysql_query("set names utf8");
        $result = mysql_query($sql);
        if (!$result) {
            return false;
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
    function insertOperation($rpc, $req)
    {   
        $dotime = date("y-m-d h:i:s");
        $content = json_encode($req);
        $sql = "INSERT INTO gm_log(rpc, content, tm) VALUES ('$rpc', '$content', '$dotime')"; //插入语句
        $dt = DBQuery::execute($sql);
    }
    
    function getIP() {  
        return isset($_SERVER["HTTP_X_FORWARDED_FOR"])?$_SERVER["HTTP_X_FORWARDED_FOR"]  
        :(isset($_SERVER["HTTP_CLIENT_IP"])?$_SERVER["HTTP_CLIENT_IP"]  
        :$_SERVER["REMOTE_ADDR"]);  
    }
    
    function curl_post($url, $data) {
        $data_string = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        return $result;
    }
    
    function curl_get($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $result = curl_exec($ch);
        return $result;
    }
?>