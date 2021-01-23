<?php
/**
 * 将要参与签名的参数按要求拼接
 * @param $data
 * @return string
 */
function signQueryString($data){
    // 去空
    $data = array_filter($data);
    //签名步骤一：按字典序排序参数
    ksort($data);
    $string_a = http_build_query($data);
    $string_a = urldecode($string_a);
    return $string_a;
}
 
/**
 * 支付宝RSA签名加密
 * @param $data  要参与加密的参数
 * @param $private_key  应用私钥
 * @return array|string
 */
function RSASign($data,$private_key){
    //要签名的参数字符串
    $query_string = signQueryString($data);
    //应用私钥
    $private_key = chunk_split($private_key, 64, "\n");
    $private_key = "-----BEGIN RSA PRIVATE KEY-----\n$private_key-----END RSA PRIVATE KEY-----\n";
    $private_key_id = openssl_pkey_get_private($private_key);
    if ($private_key_id === false){
        return array(-1,'提供的私钥格式不对');
    }
    $rsa_sign = false;
    if($data['sign_type'] == 'RSA'){
        $rsa_sign = openssl_sign($query_string, $sign, $private_key_id,OPENSSL_ALGO_SHA1);
    }else if($data['sign_type'] == 'RSA2'){
        $rsa_sign = openssl_sign($query_string, $sign, $private_key_id,OPENSSL_ALGO_SHA256);
    }
    //释放资源
    openssl_free_key($private_key_id);
    if ($rsa_sign === false){
        return array(-1,'签名失败');
    }
    $signature = base64_encode($sign);
    return $signature;
}
 
/**
 * 支付宝支付
 * @param array $params  构造好的支付参数
 * @return array|string
 */
function aliPay(array $params){
    $public = [
        'app_id' => $params['app_id'],
        'method' => $params['method'],
        'sign_type' => $params['sign_type'],
        'format' => 'JSON',
        'charset' => 'utf-8',
        'version' => '1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'biz_content' => $params['biz_content'],
    ];
    if(!empty($params['notify_url'])){
        $public['notify_url'] = $params['notify_url'];
    }
    if(!empty($params['return_url'])){
        $public['return_url'] = $params['return_url'];
    }
    $sign = RSASign($public,$params['private_key']);
    if(is_array($sign)){
        return $sign;
    }
    $public['sign'] = $sign;
    $url = http_build_query($public,'', '&');
    return $url;
}


// alipay_sign.php?subject=月卡&product_code=AA&orderid=20200103&price=0.01&notify_url=xxx

$subject = $_GET["subject"];			//道具名称
$product_code = $_GET["product_code"];	//道具编码
$price = $_GET["price"];				//价格
$orderid = $_GET["orderid"];			//订单号
$notify_url = $_GET["notify_url"];		//回调地址

$biz_content = [
    'subject' => $subject,
    'out_trade_no' => $orderid,
    'product_code' => $product_code,
    'total_amount' => $price,
];
$params = [
    'app_id'  => '2021001107645981',							//appid
    'method'  => 'alipay.trade.app.pay',						//接口名称
    'sign_type'  => 'RSA2',										//签名加密方式
    'notify_url'  => $notify_url,
    'biz_content'  => json_encode($biz_content),				//请求参数
];
$params['private_key'] = "MIIEpAIBAAKCAQEA1b3rtfqStvb5fZ+rDPsxVMgF2Q79Ch1pCGPMg0ivnEfj+wmOVbP83OH8BSojoGybn6HBLiejZ1Hp4w9k3uF0ZwscPTBpSM2eCwItahb4puRp7N6jMjSBhcrVefZaNXaosE82uJn4VOYGSseLPYvNjX1N159/1zeClMnhRh5zo1VYW/2Cpi+fSVi8zRe/Cs8tSeE9ko8xztAhlKu4kqrngLCvRN+QhmeE30fW+mCeOKpwCqwGG3F3JRRzVscT/diumaLroHDUVGzVT93WtsGQWCncHAR8quI04JkM3o9mBUC2ko4qQ/h+cnYlz0KbpBO8VSP0WuYw9s0XqKyhvr0LXwIDAQABAoIBAEQqltLpwAK0D2t+EYpJTjlQtXS9L/wa1xWC5oV0F/WKg+3a5Vb12Q4E0GBBSS1vlc46QybaI2XfHO16Slw+oOYACBiHqPw7/0xZfGxaOSDYteOCOZ/YWPp3bs+/vpNQbYoVKUmEaUuCNL2CmCifvoWCUeITjyzvGikjnUHEknKjPvhuIBwnty20rfjbMP+nMGFFkIYuKvlSnQOQ0nslkU2QpoTYutc3IZuvdQt5mgxxIQfSVMyR6vuk4GSCoaaeR0yZV9ucbVzzvCcoC0lsr/Lm6UfM24cEKh4clcuqOxX0aCbQfMTrzjbIDMOGUITjRzuWO0lm66ng4nd+JwIDv0kCgYEA9Lwcq1fmYSlktAufwKjfYYqefmIRYot5f4TSKrtzj1RasbGMwH2mBKk3hh1XPQ9VcHaS2pRyoifK9r9qhKwx7etdfzvad3RoO0jt3IynpE9SGhxdverpxSJ36rncy3FFPhXbAgaoVAY/2pkBUSUWQfEijSuan9iVtUiV5s4f0JUCgYEA35SYtGUbTsobc/IRUZMJmjvywZfboQtkw2yCdNerNQHGHzcahg4+T/SYoS0Cc+/701arSJ44tdXWVzq9gKR8qBNZDTyxAMOKJ6q0twOUt9NOvBk5U+kEnA2pJqXLJv1GMhsr9EcOQaNc0exMod9TKkbWN6dmDxfp/u1s9rvQqyMCgYB4DN6zXkbwWnG/sAQJac1J98mPjWOhx3EuEGVX+OPS5zol+EZnFjueBbPq4fGtThxHMqMO/crNqF9zcqo5so47ez098IpWFpAapMepbIW/n/lSPZ/uTZGm1iWkys94LHQe5HGuKL3hmb8w5+UxfNPSPQsJ3N3Yk1G8v4Jo2avZkQKBgQC5gEm+ipDAq1Fhvrr9yphR/mEonH1ePdzJg7MtjG8BWMpvxcFc7l5m6lLkjzqUxM1UiHkulBALVjMzRopKPK8sqHjfUbauIo7y4GB15CO07T5LNEZrR09Kxs7MQQyv+b4O/ppWX8oFTaxKg9sFBwyya/l6TYqRBU9g8s7QW/4CrwKBgQDQcMF7RVDk8QqreXEfDqMuaE1MU7J/jTG5SRsXJVN1fBvQY+cn2fKt7vJWwP0+zYMSMSLDhcOHgrikUK6tTOyuURCJ7K5nhhRR33itrSQhRtwR67tYKxUv4cbN4fHtWh1vCevXzzFocrQRyrsRC7xX9bZIOtPHuKfmOOQuCDW5kw==";	//应用私钥

// 应用公钥:MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1b3rtfqStvb5fZ+rDPsxVMgF2Q79Ch1pCGPMg0ivnEfj+wmOVbP83OH8BSojoGybn6HBLiejZ1Hp4w9k3uF0ZwscPTBpSM2eCwItahb4puRp7N6jMjSBhcrVefZaNXaosE82uJn4VOYGSseLPYvNjX1N159/1zeClMnhRh5zo1VYW/2Cpi+fSVi8zRe/Cs8tSeE9ko8xztAhlKu4kqrngLCvRN+QhmeE30fW+mCeOKpwCqwGG3F3JRRzVscT/diumaLroHDUVGzVT93WtsGQWCncHAR8quI04JkM3o9mBUC2ko4qQ/h+cnYlz0KbpBO8VSP0WuYw9s0XqKyhvr0LXwIDAQAB
$data = aliPay($params);

echo $data;
