<?php
$op = $_POST["op"];
$devid = $_POST["devid"];

// 开关用途
error_log("once request 1", 3, "traceupload.log");
if($op == "1"){ 
    if ($devid == '不存在的')
        die("1");
    else
        die("0");
}

if($op == "0"){
    $fname = $_POST["fname"];
    $data = $_POST["data"];
    // 上传部分
    date_default_timezone_set('Asia/Shanghai');
    $tm = date("Ymd_His",time());
    //$data = $_REQUEST["data"];
    //$fname = $_REQUEST["fname"];
    $filename = $devid ."_". $tm ."_". $fname;
    //echo $filename;
    if($data == "")
        die("");

    $data = base64_decode($data);
    if($data == "")
        die("");

    $dir = "save/" . substr($filename, 0, 2);

    if(!is_dir($dir))
        mkdir($dir);

    file_put_contents($dir . "/" . $filename, $data);
    die("");
}

die("none");