<?php
    #$inapp_purchase_data = '{"orderId":"GPA.3369-0053-7724-55642","packageName":"com.funova.gunsouldtsa","productId":"test0","purchaseTime":1541660995251,"purchaseState":0,"developerPayload":"bGoa+V7g\/yqDXvKRqq+JTFn4uQZbPiQJo4pf9RzJ","purchaseToken":"hfpkmfneeafapmgnlcelmapk.AO-J1OykLKFXEqXViUfuc83hb2oiWSyojkTd-YrX-8IDr0rZav5BoKDnZiJWixlxkuiSMJr0hd9ql9NtZlwOhU8NxSzAYJFvA_jlRCZkaf5xmzResXbJjLo"}';
    $inapp_purchase_data = $_GET["purchaseData"];
    $inapp_purchase_data = stripslashes($inapp_purchase_data);
    $inapp_purchase_data = str_replace(' ', '+', $inapp_purchase_data);
    #echo $inapp_purchase_data;
    
    #echo '<br/>';
    #$inapp_data_signature = 'LVCA2a2VPX2xOfg0aKVCCRkqeNZajZLhWpmVs/tKVP2RFfRupfxvIFWpLgqwn5u+edxk8O3Aw/RkBY8bhOOXgsInBaTEBFxnYmEcU7PST2aYIGdMDt2dMr7AA+6wI/iETeyQGvWTrdN/k8ezE0h7NBAgcsPANB/yubiO1tYAULv/++y6+UHR5IehS9Q0C01Oa7EwQ9XNcsRCLt+zAFBZZCN98PtMxSZbOD7e/0YflkOx2gSVf1fmNcpDLHIUw+hzjSyBmCflqW8YN3r6KfeS/RK7IXo85WVNntxdF1dA6qd69VhWR87u4Vf35W7V/fom4e7zgJRHHtlMGMSEmCHeaw==';
    $inapp_data_signature = $_GET["signature"];
    $inapp_data_signature = str_replace(' ', '+', $inapp_data_signature);
    #echo $inapp_data_signature;    
    
    $google_public_key = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAi6xJLAO6+JmTqtJBfgGQfh1n6QcIzJ4Hrs3/UhXZOKOwfotadMcKYcsxN4sDmlwy5+YZK+09kij9aKDKZeXtFAx45J2+Hwnx5bN2sjpC9AmuYGZ0+oNIUw5gpcdGVzNqFsEgaSDsMwrGQqMAqJFIM7+nKuVuMmcoNjmHHu+yd6Q7NfvpxtCcdi3zjLJm76lkcvd7MHlm8vmGtJ6Yvm6j0yFBhLkUtFCo86/WkyyA3XLsNhQbLt09jVGKiGn2DkK/ZE+/d38GGL7D2Ygxt73NJtcX23HOTIf5YrIlwbRU/poW86d98P/HKce0TukyscGsSdCE2ttFZ6K6m1BTyPWNJwIDAQAB';
    
    $public_key = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($google_public_key, 64, "\n") . "-----END PUBLIC KEY-----";
    $public_key_handle = openssl_get_publickey($public_key);
    $result = openssl_verify($inapp_purchase_data, base64_decode($inapp_data_signature), $public_key_handle, OPENSSL_ALGO_SHA1);
    if (1 === $result) {
    // 支付验证成功！
        echo '1';
    }else{
        echo '-1';
    }
?>