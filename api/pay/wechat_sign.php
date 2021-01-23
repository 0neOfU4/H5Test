<?php
class Wxpayandroid {
	//��������
	public $config = array(
		'appid' => "wx4109c37f7d4d8ca8", /*΢�ſ���ƽ̨�ϵ�Ӧ��id*/
		'mch_id' => "1490807392", /*΢������ɹ�֮���ʼ��е��̻�id*/
		'api_key' => "P5A023TOK22IFEV5RJGVX49F45B6XI0I", /*��ʽ��Կ*/
		//'api_key' => "489046dee5423612ac587077de88b682", /*ɳ����Կ*/
	);
	//�������첽֪ͨҳ��·��(����)
	public $notify_url = '';
	//�̻�������(����̻���վ����ϵͳ��Ψһ������)
	public $out_trade_no = '';
	//��Ʒ����(���������Ϊ��Ʒ����)
	public $body = '';
	//������(����)
	public $total_fee = 0;
	//�Զ��峬ʱ(ѡ�֧��dhmc)
	public $time_expire = '';
	private $WxPayHelper;
	public function Weixinpayandroid($total_fee, $tade_no, $notify_url, $subject) {
		$this->total_fee = intval($total_fee * 100); //�����Ľ�� 1Ԫ
		$this->out_trade_no = $tade_no; // date('YmdHis') . substr(time(), - 5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));//������
		$this->body = $subject; //֧��������Ϣ
		$this->time_expire = date('YmdHis', time() + 86400); //����֧���Ĺ���ʱ��(eg:һ�����)
		$this->notify_url = $notify_url; //�첽֪ͨURL(����֧��״̬)
		//������JSON����ʽ���ظ�APP
		$app_response = $this->doPay();
		if (isset($app_response['return_code']) && $app_response['return_code'] == 'FAIL') {
			$errorCode = 100;
			$errorMsg = $app_response['return_msg'];
			$this->echoResult($errorCode, $errorMsg);
		} else {
			$errorCode = 0;
			$errorMsg = 'success';
			$responseData = array(
				'notify_url' => $this->notify_url,
				'app_response' => $app_response,
			);
			$this->echoResult($errorCode, $errorMsg, $responseData);
		}
	}
	//�ӿ����
	function echoResult($errorCode = 0, $errorMsg = 'success', $responseData = array()) {
		$arr = array(
			'errorCode' => $errorCode,
			'errorMsg' => $errorMsg,
			'responseData' => $responseData,
		);
		exit(json_encode($arr)); //exit�����������͸�APP json����
		// return json_encode($arr); //��TP5��return���json���ݣ�APP���յ�����null,�޷���������΢��֧��
		
	}
	function formatParameters($paraMap, $urlencode) {
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v) {
			if ($k == "sign") {
				continue;
			}
			if ($urlencode) {
				$v = urlencode($v);
			}
			$buff.= $k . "=" . $v . "&";
		}
		$reqPar;
		if (strlen($buff) > 0) {
			$reqPar = substr($buff, 0, strlen($buff) - 1);
		}
		return $reqPar;
	}
	/**
	 * �õ�ǩ��
	 * @param object $obj
	 * @param string $api_key
	 * @return string
	 */
	function getSign($obj, $api_key) {
		foreach ($obj as $k => $v) {
			$Parameters[strtolower($k) ] = $v;
		}
		//ǩ������һ�����ֵ����������
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters, false);
		//ǩ�����������string�����KEY
		$String = $String . "&key=" . $api_key;
		//ǩ����������MD5����
		$result = strtoupper(md5($String));
		return $result;
	}
	/**
	 * ��ȡָ�����ȵ�����ַ���
	 * @param int $length
	 * @return Ambigous <NULL, string>
	 */
	function getRandChar($length) {
		$str = null;
		$strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
		$max = strlen($strPol) - 1;
		for ($i = 0; $i < $length; $i++) {
			$str.= $strPol[rand(0, $max) ]; //rand($min,$max)���ɽ���min��max������֮���һ���������
			
		}
		return $str;
	}
	/**
	 * ����תxml
	 * @param array $arr
	 * @return string
	 */
	function arrayToXml($arr) {
		$xml = "<xml>";
		foreach ($arr as $key => $val) {
			if (is_numeric($val)) {
				$xml.= "<" . $key . ">" . $val . "</" . $key . ">";
			} else $xml.= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
		}
		$xml.= "</xml>";
		return $xml;
	}
	/**
	 * ��post��ʽ�ύxml����Ӧ�Ľӿ�url
	 *
	 * @param string $xml ��Ҫpost��xml����
	 * @param string $url url
	 * @param bool $useCert �Ƿ���Ҫ֤�飬Ĭ�ϲ���Ҫ
	 * @param int $second urlִ�г�ʱʱ�䣬Ĭ��30s
	 * @throws WxPayException
	 */
	function postXmlCurl($xml, $url, $second = 30, $useCert = false, $sslcert_path = '', $sslkey_path = '') {
		$ch = curl_init();
		//���ó�ʱ
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
		curl_setopt($ch, CURLOPT_URL, $url);
		//����header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//Ҫ����Ϊ�ַ������������Ļ��
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		if ($useCert == true) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //�ϸ�У��
			//����֤��
			//ʹ��֤�飺cert �� key �ֱ���������.pem�ļ�
			curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
			curl_setopt($ch, CURLOPT_SSLCERT, $sslcert_path);
			curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
			curl_setopt($ch, CURLOPT_SSLKEY, $sslkey_path);
		}
		//post�ύ��ʽ
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//����curl
		$data = curl_exec($ch);
		//���ؽ��
		if ($data) {
			curl_close($ch);
			return $data;
		} else {
			$error = curl_errno($ch);
			curl_close($ch);
			return false;
		}
	}
	/**
	 * ��ȡ��ǰ��������IP
	 * @return Ambigous <string, unknown>
	 */
	function get_client_ip() {
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$cip = $_SERVER['REMOTE_ADDR'];
		} elseif (getenv("REMOTE_ADDR")) {
			$cip = getenv("REMOTE_ADDR");
		} elseif (getenv("HTTP_CLIENT_IP")) {
			$cip = getenv("HTTP_CLIENT_IP");
		} else {
			$cip = "127.0.0.1";
		}
		return $cip;
	}
	/**
	 * ������ת��uri�ַ���
	 * @param array $paraMap
	 * @param bool $urlencode
	 * @return string
	 */
	function formatBizQueryParaMap($paraMap, $urlencode) {
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v) {
			if ($urlencode) {
				$v = urlencode($v);
			}
			$buff.= strtolower($k) . "=" . $v . "&";
		}
		$reqPar;
		if (strlen($buff) > 0) {
			$reqPar = substr($buff, 0, strlen($buff) - 1);
		}
		return $reqPar;
	}
	/**
	 * XMLת����
	 * @param unknown $xml
	 * @return mixed
	 */
	function xmlToArray($xml) {
		//��XMLתΪarray
		$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)) , true);
		return $array_data;
	}
	public function chkParam() {
		//�û���վ������
		if (empty($this->out_trade_no)) {
			die('out_trade_no error');
		}
		//��Ʒ����
		if (empty($this->body)) {
			die('body error');
		}
		if (empty($this->time_expire)) {
			die('time_expire error');
		}
		//���֧�����
		if (empty($this->total_fee) || !is_numeric($this->total_fee)) {
			die('total_fee error');
		}
		//�첽֪ͨURL
		if (empty($this->notify_url)) {
			die('notify_url error');
		}
		if (!preg_match("#^http:\/\/#i", $this->notify_url)) {
			$this->notify_url = "http://" . $_SERVER['HTTP_HOST'] . $this->notify_url;
		}
		return true;
	}
	/**
	 * ����֧��(���ظ�APP)
	 * @return boolean|mixed
	 */
	public function doPay() {
		//��⹹�����
		$this->chkParam();
		return $this->createAppPara();
	}
	/**
	 * APPͳһ�µ�
	 */
	private function createAppPara() {
		$url = "https://api.mch.weixin.qq.com/pay/unifiedorder";					//��ʽurl
		//$url = "https://api.mch.weixin.qq.com/sandboxnew/pay/unifiedorder";			//ɳ��url
		$data["appid"] = $this->config['appid']; //΢�ſ���ƽ̨���ͨ����Ӧ��APPID
		$data["body"] = $this->body; //��Ʒ��֧������Ҫ����
		$data["mch_id"] = $this->config['mch_id']; //�̻���
		$data["nonce_str"] = $this->getRandChar(32); //����ַ���
		$data["notify_url"] = $this->notify_url; //֪ͨ��ַ
		$data["out_trade_no"] = $this->out_trade_no; //�̻�������
		$data["spbill_create_ip"] = $this->get_client_ip(); //�ն�IP
		$data["total_fee"] = $this->total_fee; //�ܽ��
		$data["time_expire"] = $this->time_expire; //���׽���ʱ��
		$data["trade_type"] = "APP"; //��������
		$data["sign"] = $this->getSign($data, $this->config['api_key']); //ǩ��
		$xml = $this->arrayToXml($data);
		$response = $this->postXmlCurl($xml, $url);
		//��΢�ŷ��صĽ��xmlת������
		$responseArr = $this->xmlToArray($response);
		if (isset($responseArr["return_code"]) && $responseArr["return_code"] == 'SUCCESS') {
			return $this->getOrder($responseArr['prepay_id']);
		}
		return $responseArr;
	}
	/**
	 * ִ�еڶ���ǩ�������ܷ��ظ��ͻ���ʹ��
	 * @param int $prepayId:Ԥ֧�����׻Ự��ʶ
	 * @return array
	 */
	public function getOrder($prepayId) {
		$data["appid"] = $this->config['appid'];
		$data["noncestr"] = $this->getRandChar(32);
		$data["package"] = "Sign=WXPay";
		$data["partnerid"] = $this->config['mch_id'];
		$data["prepayid"] = $prepayId;
		$data["timestamp"] = time();
		$data["sign"] = $this->getSign($data, $this->config['api_key']);
		$data["packagestr"] = "Sign=WXPay";
		return $data;
	}

	function getVerifySign($data, $key) {
		$String = $this->formatParameters($data, false);
		//ǩ�����������string�����KEY
		$String = $String . "&key=" . $key;
		//ǩ����������MD5����
		$String = md5($String);
		//ǩ�������ģ������ַ�תΪ��д
		$result = strtoupper($String);
		return $result;
	}
	/**
	 * �첽֪ͨ��Ϣ��֤
	 * @return boolean|mixed
	 */
	public function verifyNotify($xml) {
		$wx_back = $this->xmlToArray($xml);
		if (empty($wx_back)) {
			return "false";
		}
		$checkSign = $this->getVerifySign($wx_back, $this->config['api_key']);
		if ($checkSign = $wx_back['sign']) {
			return json_encode($wx_back);
		} else {
			return "false";
		}
	}
}

// wechat_sign.php?subject=�¿�&orderid=20200103&price=0.01&notify_url=xxx

$type = $_GET["type"];		//1=get 2=verify
$wxpayandroid = new \Wxpayandroid;  //ʵ����΢��֧����
if($type == 1)
{
	$subject = $_GET["subject"];		//��������
	$price = $_GET["price"];			//�۸�
	$orderid = $_GET["orderid"];		//������
	$notify_url = $_GET["notify_url"];	//�ص���ַ
	$res = $wxpayandroid->Weixinpayandroid($price, $orderid, $notify_url, $subject); //����weixinpay����
}
else
{
	$xml = $_GET["xml"];		//xml
	$res = $wxpayandroid->verifyNotify($xml);
	echo($res);
}