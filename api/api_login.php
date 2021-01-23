<?php   
    /*
    * req:
    *   type: 1=登录 2=登出 3=检查登录
    *   name: 账号
    *   pass: 密码
    * res:
    *   result: 1=成功 2=找不到用户 3=密码错误 4=其他
    *   name: 账号
    */
    $res = array(
        "result" => 4
    );
    $not_check_login = true;
    include "common.php";
    
    //登录
    if($req["type"] == 1)      
    {
        $sql = "select * from $dbgm.gm where name=". tosqlstr($req["name"]);
        $dt = DBQuery::execute($sql);
        $passCheck = strtolower(md5($req["pass"]."jkzx8hf2"));
        if(count($dt) == 0)
        {
            $res["result"] = 2;
        }
        else if($dt[0]["password"] != $passCheck)
        {
            $res["result"] = 3;
        }
        else
        {
            logError("login ok, name = " . $req["name"]);
            $_SESSION["login"] = "1";
            $_SESSION["name"] = $req["name"]; 
            addOperationLog(1, "");      //$LOGTYPE  登陆
            $res["result"] = 1;
            $res["name"] = $req["name"];  
        }
    }
    //登出
    else if($req["type"] == 2)
    {
        if($_SESSION["login"] == "1")
        {
            addOperationLog(2, "");      //$LOGTYPE  退出
            $_SESSION["login"] = "";
            $_SESSION["name"] = "";
        }
        $res["result"] = 1;
    }
    //检查登录
    else if($req["type"] == 3)
    {
        if($_SESSION["login"] == "1")
        {
            $res["result"] = 1;
            $res["name"] = $_SESSION["name"];
        }
    }
    
    echo(json_encode($res));
?>
