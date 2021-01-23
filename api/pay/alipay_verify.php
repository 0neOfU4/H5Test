<?php
class AopClient {
	public function getSignContent($params) {
		ksort($params);
		$stringToBeSigned = "";
		$i = 0;
		foreach ($params as $k => $v) {
			if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

				// 转换成目标字符集
				$v = $this->characet($v, "UTF-8");

				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}
		unset ($k, $v);
		return $stringToBeSigned;
	}
	function characet($data, $targetCharset) {
		
		if (!empty($data)) {
			$fileType = "UTF-8";
			if (strcasecmp($fileType, $targetCharset) != 0) {
				$data = mb_convert_encoding($data, $targetCharset, $fileType);
				//				$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
			}
		}
		return $data;
	}


	protected function checkEmpty($value) {
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (trim($value) === "")
			return true;

		return false;
	}

	public function rsaCheckV1($params, $rsaPublicKeyFilePath,$signType='RSA') {
		$sign = $params['sign'];
		$params['sign_type'] = null;
		$params['sign'] = null;
		return $this->verify($this->getSignContent($params), $sign, $rsaPublicKeyFilePath,$signType);
	}

	function verify($data, $sign, $rsaPublicKeyFilePath, $signType = 'RSA') {
		$pubKey= $this->alipayrsaPublicKey;
		$res = "-----BEGIN PUBLIC KEY-----\n" .
			wordwrap($pubKey, 64, "\n", true) .
			"\n-----END PUBLIC KEY-----";
		if ("RSA2" == $signType) {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
		} else {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res);
		}

		if(!$this->checkEmpty($this->alipayPublicKey)) {
			//释放资源
			openssl_free_key($res);
		}
		return $result;
	}
}

$getData=$_POST;
$Client = new AopClient();
$alipayrsaPublicKey="MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAoSJo6la74og2gYw/xXH4bZviOjaRjdW1Q3p6CKQ+O1fZESRpkKsAgMDKOS790qp06c7MOmzfEWLXgK1/jqsTfFOSoHZFsWZH/rgTzFSQxRl9sIS9/tcG4tMAHIikVMjOL5uCl8DnPzTtLsnjXTnnssupLGdmcpt2elW/1YGknUcMHD1Wi+3/1AUOFJY/b/hFlCS1iaBiDEkaW7YXR6dwllmI7Pis08j/GgwiInqq9qiNKeBKtrejjAh/1hgu/w3J1VYWbK8mqlfiytPLbwPC3wVa+VF4aVkGo/33ocVmE8pXDNp9JwB75wxyH7SfrFe11yiGI1vss3r732/bjeamSwIDAQAB";
$Client->alipayrsaPublicKey=$alipayrsaPublicKey;
$getData['fund_bill_list'] = stripslashes($getData['fund_bill_list']); 
//验签
$result=$Client->rsaCheckV1($getData,  null,"RSA2");
echo $result;
