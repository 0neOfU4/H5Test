<?php

function request($Url, $Params, $Method='post'){

    $Curl = curl_init();//初始化curl

    if ('get' == $Method){//以GET方式发送请求
        curl_setopt($Curl, CURLOPT_URL, "$Url?$Params");
    }else{//以POST方式发送请求
        curl_setopt($Curl, CURLOPT_URL, $Url);
        curl_setopt($Curl, CURLOPT_POST, 1);//post提交方式
        curl_setopt($Curl, CURLOPT_POSTFIELDS, $Params);//设置传送的参数
    }

    curl_setopt($Curl, CURLOPT_HEADER, false);//设置header
    curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
    curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 30);//设置等待时间
    curl_setopt($Curl, CURLOPT_SSL_VERIFYPEER, false);//跳过证书检测

    $Res = curl_exec($Curl);//运行curl
    $Err = curl_error($Curl);

    if (false === $Res || !empty($Err)){
        $Errno = curl_errno($Curl);
        $Info = curl_getinfo($Curl);
        curl_close($Curl);

        $rrr = array(
            'result' => false,
            'errno' => $Errno,
            'msg' => $Err,
            'info' => $Info,
        );

        return $rrr;
    }
    curl_close($Curl);//关闭curl
    return array(
        'result' => true,
        'msg' => json_decode($Res, true),
    );

}

function appleVerify($receipt_data, $passkey,$url,$orderId = 0)
{
    /*
     * 21000 App Store不能读取你提供的JSON对象
     * 21002 receipt-data域的数据有问题
     * 21003 receipt无法通过验证
     * 21004 提供的shared secret不匹配你账号中的shared secret
     * 21005 receipt服务器当前不可用
     * 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
     * 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
     * 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务
     * $receipt_data 苹果返回的支付凭证
     * 正式 ： https://buy.itunes.apple.com/verifyReceipt
     * 沙箱 ： https://sandbox.itunes.apple.com/verifyReceipt
     */

    $arr_params = array(
        'receipt-data' => $receipt_data
    );
    if(!empty($passkey)){
        $arr_params['password'] = $passkey;
    }

    $response = request($url, json_encode($arr_params), 'post');

    // $response = json_decode($response,true);
    // $data['status']==0  成功
    // $data['receipt']['in_app'][0]['transaction_id']  苹果订单号
    // $data['receipt']['in_app'][0]['product_id'];  商品
    // $data['receipt']['in_app'][0]['purchase_date_ms']

    $status = 0;
    $purchaseTime = 0;
    $productId = "";

	if($response['result'] != 1)
		return ['status'=>0,'purchaseTime' => 0];

    if ($response['msg']['status'] == 0) {
        $status = 1;
        try{
            $inapps = $response['msg']['receipt']['in_app'];
            $purchaseTime = $inapps[0]['purchase_date_ms'];
            $productId = $inapps[0]['product_id'];

            if(!empty($orderId)){
                foreach ($inapps as $item){
                    if($orderId == $item['transaction_id']){
                        $purchaseTime = $item['purchase_date_ms'];
                        break;
                    }
                }
            }

        }catch (Exception $e){
            return ['status'=>0,'purchaseTime' => 0];
        }
    }
    elseif($response['msg']['status'] == 21007)
    {
        // 用生产凭证验证了沙盒环境，需要再次调用更换验证地址
        $status = 2;
    }

    return ['status'=>$status,'purchaseTime' => $purchaseTime, 'productId' => $productId];
}

$receipt = $_POST["receipt"];
$passkey = $_POST["password"];
//$receipt = "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";
$ret = appleVerify($receipt, $passkey, "https://buy.itunes.apple.com/verifyReceipt");
if ($ret['status'] == 2){
    $ret = appleVerify($receipt, $passkey, "https://sandbox.itunes.apple.com/verifyReceipt");
}

print(json_encode($ret));
