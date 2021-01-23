<?php
 //safe get & post
foreach ($_GET as $get_key=>$get_var)
{
    if (is_numeric($get_var))
    {
        $get[strtolower($get_key)] = get_safe_number($get_var);
    } 
    else
    {
        $get[strtolower($get_key)] = get_safe_str($get_var);
    }
}
foreach ($_POST as $post_key=>$post_var)
{
    if (is_numeric($post_var))
    {
        $post[strtolower($post_key)] = get_safe_number($post_var);
    } 
    else
    {
        $post[strtolower($post_key)] = get_safe_str($post_var);
    }
} 
function get_safe_number($number)
{
    return $number;
}
function get_safe_str($string)
{
    if(!get_magic_quotes_gpc())
    {
        return addslashes($string);
    }
    return $string;
}


function logError($content)
{
  $logfile = 'logs/accesslog'.date('Ymd').'.txt';
  if(!file_exists(dirname($logfile)))
  {
    mkdir(dirname($logfile));
  }
  error_log(date("[Y-m-d H:i:s]")."-[".$_SERVER["REMOTE_ADDR"]."]\t[".$_SERVER['REQUEST_URI']."]:".$content."\n", 3,$logfile);
}

logError(""); 
    
?>