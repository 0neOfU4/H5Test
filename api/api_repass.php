<?php   
    /*
    * req:
    *   oldpass: 旧密码
    *   newpass: 新密码
    * res:
    *   result: 1=成功 2=密码错误 4=其他
    *   name: 账号
    */
    $res = array(
        "result" => 4
    );
    include "common.php";
    $name = $_SESSION["name"];
    $sql = "select * from $dbgm.gm where name=". tosqlstr($name);
    $dt = DBQuery::execute($sql);
    $key = "jkzx8hf2";
    $passCheck = strtolower(md5($req["oldpass"].$key));
    if($dt[0]["password"] != $passCheck)
    {
        $res["result"] = 2;
    }
    else
    {
        logError("repass ok, name = $name");
        $newpass = strtolower(md5($req["newpass"].$key));
        $sql = "update $dbgm.gm set password='$newpass' where name=". tosqlstr($name);
        DBQuery::execute($sql);
        addOperationLog(3, "");
        $res["result"] = 1;
    }
    echo(json_encode($res));
?>

