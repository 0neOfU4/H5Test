<?php
    //$not_check_login = true;   

    include "common.php";   
   
    $sql = $req["sql"];
    //$sql = "call mode_kill(now(), '1', 0)";
    DBQuery::execute("use $dbgm;");
    $ret = DBQuery::execute($sql);
    echo(json_encode($ret));
?>
