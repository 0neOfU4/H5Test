<?php
    
    class PHPSocket
    {
        public static function makeRequest($host, $port, $cmd, $data, $key)
        {
            $requestStr = json_encode(array(cmd => $cmd, data => $data));
            $requestMd5 = md5($requestStr . $key);
            $request = $requestMd5 . $requestStr;
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            
            if (!$socket)
            {
               //echo "socket_create() failed: reason: " . socket_strerror($socket) . "\n";
               return array(ret=> -20001, data => "socket_create() failed: reason: " . socket_strerror($socket));
            }
            $result = @socket_connect($socket, $host, $port);
            if (!$result)
            {
                //echo "socket_connect() failed.\nReason: ($result) " . socket_strerror($result) . "\n";
                return array(ret=>  -20002, data => "socket_connect() failed.\nReason: ($result) " . socket_strerror($result));
            }
            
            $packSize = 490;
            $packArray = array();
            while(strlen($request) > $packSize)
            {
                array_push($packArray, substr($request, 0, $packSize));
                $request = substr($request, $packSize);
            }
            array_push($packArray, $request);
            
            $totalPacks = count($packArray);
            
            for($i = 0; $i < $totalPacks; $i++)
            {
                $buff_head = chr(6) . chr(0) . chr(0) . chr(0);
                //$cmd = "PHPRPC";
                $cmd = "php_op";
                $requestBody = $packArray[$i];
                $body = PHPSocket::getShortLen(strlen($cmd)) . $cmd;
                $body .= PHPSocket::getShortLen($i + 1);
                $body .= PHPSocket::getShortLen($totalPacks);
                $body .= PHPSocket::getShortLen(strlen($requestBody)) . $requestBody;
                $buff = $buff_head . PHPSocket::getShortLen(strlen($body) + 4) . "\0\0" . $body;
                //echo("send:" . $buff . "<BR>");
                usleep(1);          //必须延迟一下，否则服务器会丢包
                if (!socket_write($socket, $buff))
                {
                    //echo "socket_write() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n";
                    return array(ret=> -20003, data => "socket_write() failed; reason: " . socket_strerror(socket_last_error($socket)));
                }
            }
            
            //die("ok");
            $outbuf = "";
             
            $start_time = PHPSocket::getmicrotime();
            $isok = false;
            socket_set_nonblock($socket);
            while((PHPSocket::getmicrotime() - $start_time) * 1000 < 20000)          //20000ms timeout
            {
                $tmp = "";
                if (false != ($bytes = @socket_recv($socket, $tmp, 1024, MSG_WAITALL))) 
                {
                    $outbuf .= $tmp;
                    if (strlen($outbuf) > 8)
                    {                        
                        $total_len = 4 + ord(substr($outbuf, 4)) + ord(substr($outbuf, 5)) * 256;
                        if(strlen($outbuf) >= $total_len)
                        {
                            $outbuf = substr($outbuf, 0, $total_len);
                            $isok = true;
                            break;
                        }
                    }
                } 
                else 
                {
                    //echo "socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n";
                    //return array(-20004, "socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)));
                }
                usleep(1);
                //echo("wait " . strlen($outbuf) . "<br>");
            }
            
            socket_close($socket);
            if(!$isok) 
                return array(ret=> -20005, data => "socket_recv() timeout");
            $offset = strlen($buff_head) + 4 + 2 + strlen($cmd) + 2;
            $ret = substr($outbuf, $offset, strlen($outbuf) - $offset);            
            //echo("ret=".$ret."\n");
            if($ret == "[]")
                        $ret = "{}";
            $retTble = json_decode($ret);
            if ($retTble == null) 
                return array( ret=> -20006, data => "json_decode()failed");
            return  $retTble;
        }
        
        public static function makeLargeRequest($host, $port, $cmd, $data, $retsize, $key)
        {
        	  echo("<br><font color = red>USE LARGE REQUEST!</font><br><br>");
        	  $recvpacksize = 1024;
        	  if ($retsize)
        	  	$recvpacksize = $retsize;
        	  	
            $requestStr = json_encode(array(cmd => $cmd, data => $data));
            $requestMd5 = md5($requestStr . $key);
            $request = $requestMd5 . $requestStr;
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$socket)
            {
               //echo "socket_create() failed: reason: " . socket_strerror($socket) . "\n";
               return array(ret=> -20001, data=> "socket_create() failed: reason: " . socket_strerror($socket));
            }
            $result = socket_connect($socket, $host, $port);
            if (!$result)
            {
                //echo "socket_connect() failed.\nReason: ($result) " . socket_strerror($result) . "\n";
                return array(ret=> -20002, data=> "socket_connect() failed.\nReason: ($result) " . socket_strerror($result));
            }
            
            $packSize = 490;
            $packArray = array();
            while(strlen($request) > $packSize)
            {
                array_push($packArray, substr($request, 0, $packSize));
                $request = substr($request, $packSize);
            }
            array_push($packArray, $request);
            
            $totalPacks = count($packArray);
            
            for($i = 0; $i < $totalPacks; $i++)
            {
                $buff_head = chr(6) . chr(0) . chr(0) . chr(0);
                $cmd = "PHPRPC";
                $requestBody = $packArray[$i];
                $body = PHPSocket::getShortLen(strlen($cmd)) . $cmd;
                $body .= PHPSocket::getShortLen($i + 1);
                $body .= PHPSocket::getShortLen($totalPacks);
                $body .= PHPSocket::getShortLen(strlen($requestBody)) . $requestBody;
                $buff = $buff_head . PHPSocket::getShortLen(strlen($body) + 4) . "\0\0" . $body;
                //echo("send:" . $buff . "<BR>");
                usleep(1);          //必须延迟一下，否则服务器会丢包
                if (!socket_write($socket, $buff))
                {
                    //echo "socket_write() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n";
                    return array(ret=> -20003, data =>"socket_write() failed; reason: " . socket_strerror(socket_last_error($socket)));
                }
            }
            
            //die("ok");
            $outbuf = "";
             
            $start_time = PHPSocket::getmicrotime();
            $isok = false;
            socket_set_nonblock($socket);
            while((PHPSocket::getmicrotime() - $start_time) * 1000 < 20000)          //20000ms timeout
            {
                $tmp = "";
                if (false != ($bytes = @socket_recv($socket, $tmp, $recvpacksize, MSG_WAITALL))) 
                {
                    $outbuf .= $tmp;
                    if (strlen($outbuf) > 8)
                    {                       
                        $total_len = 4 + ord(substr($outbuf, 4)) + ord(substr($outbuf, 5)) * 256;
                        if(strlen($outbuf) >= $total_len)
                        {
                            $outbuf = substr($outbuf, 0, $total_len);
                            $isok = true;
                            break;
                        }
                    }
                } 
                else 
                {
                    //echo "socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n";
                    //return array(-20004, "socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)));
                }
                usleep(1);
                //echo("wait " . strlen($outbuf) . "<br>");
            }
            
            socket_close($socket);
            if(!$isok) 
                return array(ret=> -20005, data=> "socket_recv() timeout");
            $offset = strlen($buff_head) + 4 + 2 + strlen($cmd) + 2;
            $ret = substr($outbuf, $offset, strlen($outbuf) - $offset);
            $retTble = json_decode($ret);
            if ($retTble == null) 
                return array(ret=> -20006, data =>"json_decode()failed");
            return  $retTble;
        }
        
        private static function getShortLen($len)
        {
            if ($len > 255)
            {
                return chr($len % 256) . chr($len / 256);
            }
            else
            {
                return chr($len) . "\0";
            }
        }
        
        private static function getmicrotime()
        {   
            list($usec, $sec) = explode(" ",microtime());   
            return ((float)$usec + (float)$sec);   
        } 
    }
   
    /**测试代码**/
    if(false)
    {
        function getmicrotime()   
        {   
            list($usec, $sec) = explode(" ",microtime());   
            return ((float)$usec + (float)$sec);   
        }  
        $time_start = getmicrotime(); 
        
        /*
        {
            $data = array
            (
                uname => 'aaa2',
                reg_site_no => 2,
                password => 'passw251',
                nick_name => 'nick_name_bba1',
                gender => 0,
                face => "face/3.jpg",
                ip => "123.0.0.5"
            );
            print_r(PHPSocket::makeRequest("127.0.0.1", 6000, "replace_into_player", $data));
        }*/
            
        echo("<br>");
        
        $data = array
        (
            uname => '600',
            reg_site_no => 0,
            friends => array
            (
                array(uname => '602')
                //array(uname => '602', nick => 'nick_602', gender => 1, face => 'face/1.jpg' )
                //,array(uname => '112', nick => 'nick_181', gender => 1, face => 'face/1.jpg' )
                //,array(uname => '186', nick => 'nick_185', gender => 1, face => 'face/1.jpg' )
            ),
            auto_reg_friend => 0            //是否自动注册未开通游戏的好友
        );
        
        print_r(PHPSocket::makeRequest("127.0.0.1", 6000, "update_friend", $data));
       
        $time_end = getmicrotime();   
        printf ("<br>[page run time: %.2fms]\n\n",($time_end - $time_start)*1000);  
    }
?>
