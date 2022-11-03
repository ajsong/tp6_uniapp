<?php
declare (strict_types = 1);

namespace Send;

class SendCode {
	private $account; //手机号码或邮箱
	private $type; //验证码类型
	private $country_num; //国家代码
	private $code; //验证码
	
	//以下参数根据短信服务商设置，没有的可以不设置
	public $key_id; //短信服务商提供的key_id
	public $key_secret; //短信服务商提供的key_secret
	public $app_id; //短信服务商提供的app_id
	
	public function __construct($account, $type, $country_num = 86) {
		$this->account = $account;
		$this->type = $type;
		$this->country_num = intval($country_num);
	}
	
	public function send() {
		$this->code = mt_rand(100000, 999999);
		//调用发生接口
		$send = $this->sendCode();
		if ($send === true) {
			session("{$this->type}_code", $this->code); //保存code值到session值
		}
		return $send;
	}
	
	public static function getCode($type) {
		return session("{$type}_code");
	}
	
	//发送验证码
	private function sendCode($options = []) {
		if (!$this->_maxip()) return false;
		if (is_email($this->account)) {
			return $this->_email();
		} else {
			return $this->_mobile($options);
		}
	}
	
	//每次发送检测指定ip在指定秒数内的限制
	private function _maxip() {
		$max_ip_time = 12;
		$filename = RUNTIME_PATH . '/max_ip_time.txt';
		$ip = request()->ip();
		$content = [];
		if (file_exists($filename)) {
			$data = file_get_contents($filename);
			if (strlen(trim($data))) {
				$content = unserialize(trim($data));
				if (!is_array($content)) $content = [];
				foreach ($content as $i => $t) {
					if (intval($t) < time() - 24*60*60) { //一天后删除
						unset($content[$i]);
					}
				}
				if (isset($content[$ip]) && intval($content[$ip]) > time() - $max_ip_time) return false;
			}
		}
		$content[$ip] = time();
		file_put_contents($filename, serialize($content));
		return true;
	}
	
	//发邮件
	private function _email() {
		$email = $this->account; //定义收件人的邮箱
		$code = $this->code;
		try {
			$mail = new \PHPMailer\PHPMailer();
			$mail->isSMTP(); //使用SMTP服务
			$mail->CharSet = 'utf8'; //编码格式为utf8，不设置编码的话，中文会出现乱码
			$mail->Host = 'smtp.163.com'; //发送方的SMTP服务器地址
			$mail->SMTPAuth = true; //是否使用身份验证
			$mail->Username = 'lwf_000001@163.com'; //发送方的163邮箱用户名，就是你申请163的SMTP服务使用的163邮箱
			$mail->Password = 'CWQEKFSLQGDPJPYJ'; //发送方的邮箱密码，注意用163邮箱这里填写的是“客户端授权密码”而不是邮箱的登录密码！
			$mail->SMTPSecure = 'ssl'; //使用ssl协议方式
			$mail->Port = 465; //163邮箱的ssl协议方式端口号是465/994
			
			$mail->setFrom('lwf_000001@163.com', 'Mailer'); //设置发件人信息，如邮件格式说明中的发件人，这里会显示为Mailer(xxxx@163.com)，Mailer是当做名字显示
			$mail->addAddress($email, $email); //设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)
			$mail->addReplyTo('lwf_000001@163.com', 'Reply'); //设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
			
			$mail->Subject = '邮箱验证码'; // 邮件标题
			$mail->Body = "您的验证码是：$code"; //邮件正文
			
			if (!$mail->send()) { //发送邮件
				echo 'Message could not be sent.';
				echo 'Mailer Error: ' . $mail->ErrorInfo; //输出错误信息
				return $mail->ErrorInfo;
			}
			return true;
		} catch (\PHPMailer\Exception $e) {
			return $e->getMessage();
		}
	}
	
	//发短信, options = [template_id|0, sign|'']
	private function _mobile($options) {
		if (!$options || !is_array($options) || !count($options)) return 'Missing send sms options';
		if (!isset($options['type']) || !$options['type']) return 'Missing send sms options type';
		if (!method_exists($this, $options['type'])) return 'Missing send sms type: ' . $options['type'];
		$options['mobile'] = $this->account; //手机号
		$options['content'] = $this->country_num == 86 ? "您的验证码是：$this->code" : "Your verification code is: $this->code";
		return $this->{$options['type']}($options['mobile'], $options['content'], $options['template_id'], $options['sign']);
	}
	
	//阿里云
	private function aliyun($mobile, $content, $template_id=0, $sign='') {
		if (!$this->key_id || !$this->key_secret) return 'Lost api key';
		include_once(dirname(__FILE__) . '/AliyunDysms/sendSms.php');
		if (!is_array($content)) $content = ['code'=>$content];
		$res = sendSms($this->key_id, $this->key_secret, $mobile, $sign, $template_id, $content);
		if ($res) {
			if (!isset($res['Code'])) return 'FAILED, NO ERROR';
			if ($res['Code'] != 'OK') return 'FAILED, CODE:'.$res['Code'].', MSG:'.$res['Message'];
		} else {
			return 'SMS SEND ERROR';
		}
		return true;
	}
	
	//腾讯云
	private function tencent($mobile, $content, $template_id=0, $sign='') {
		//composer require tencentcloud/sms
		/*use TencentCloud\Common\Credential;
		use TencentCloud\Common\Profile\ClientProfile;
		use TencentCloud\Common\Profile\HttpProfile;
		use TencentCloud\Common\Exception\TencentCloudSDKException;
		use TencentCloud\Sms\V20210111\SmsClient;
		use TencentCloud\Sms\V20210111\Models\SendSmsRequest;*/
		if (!$this->key_id || !$this->key_secret || !$this->app_id) return 'Lost api key';
		$cred = new Credential($this->key_id, $this->key_secret);
		$httpProfile = new HttpProfile();
		$httpProfile->setEndpoint('sms.tencentcloudapi.com');
		$clientProfile = new ClientProfile();
		$clientProfile->setHttpProfile($httpProfile);
		$client = new SmsClient($cred, 'ap-guangzhou', $clientProfile);
		$req = new SendSmsRequest();
		$params = [
			'PhoneNumberSet' => ["+86$mobile"],
			'SmsSdkAppId' => $this->app_id,
			'SignName' => $sign,
			'TemplateId' => $template_id,
			'TemplateParamSet' => [$content]
		];
		$req->fromJsonString(json_encode($params));
		$res = $client->SendSms($req);
		if (!$res) return 'FAILED, NO ERROR';
		return $res;
	}
	
	//螺丝帽
	private function luoshimao($mobile, $content, $template_id=0, $sign='铁壳测试') {
		if (!$this->key_id) return 'Lost api key';
		include_once(dirname(__FILE__) . '/luosimao/luosimao.php');
		$sms = new luosimao_api(array('api_key'=>$this->key_id, 'sign'=>"【{$sign}】", 'use_ssl'=>false));
		if (is_array($content)) $content = implode('', $content);
		//发送接口，签名需在后台报备
		$res = $sms->send($mobile, $content);
		if ($res) {
			if (!isset($res['error'])) return 'FAILED, NO ERROR';
			if (intval($res['error']) != 0) return 'FAILED, CODE:'.$res['error'].', MSG:'.$sms->get_error($res['error']).' ('.$res['msg'].')';
		} else {
			return 'SMS SEND ERROR: '.$sms->last_error();
		}
		//余额查询
		//$res = $sms->get_deposit();
		//$deposit = floatval($res['deposit']);
		return true;
	}
	
	//云通讯
	private function yuntongxun($mobile, $content, $template_id=0, $sign='') {
		if (!$this->key_id || !$this->key_secret || !$this->app_id) return 'Lost api key';
		include_once(dirname(__FILE__) . '/yuntongxun/CCPRestSmsSDK.php');
		//沙盒环境: sandboxapp.cloopen.com
		//生产环境: app.cloopen.com
		$serverIP = 'sandboxapp.cloopen.com';
		$serverPort = '8883'; //请求端口，生产环境和沙盒环境一致
		$softVersion = '2013-12-26'; //REST版本号，在官网文档REST介绍中获得
		$rest = new REST($serverIP, $serverPort, $softVersion);
		$rest->setAccount($this->key_id, $this->key_secret);
		$rest->setAppId($this->app_id);
		//发送模板短信
		if (!is_array($content)) $content = [$content];
		$res = $rest->sendTemplateSMS($mobile, $content, $template_id);
		if (!$res) return 'FAILED, NO ERROR';
		if ($res->statusCode != 0) return 'FAILED, CODE:'.$res->statusCode.', MSG:'.$res->statusMsg;
		return true;
		//返回信息
		//$smsmessage = $result->TemplateSMS;
		//echo 'dateCreated:'.$smsmessage->dateCreated;
		//echo 'smsMessageSid:'.$smsmessage->smsMessageSid;
	}
	
	//ipyy
	private function ipyy($mobile, $content, $template_id=0, $sign='') {
		if ($this->country_num != 86) {
			//英文
			if (!$this->key_id || !$this->key_secret) return 'Lost api key';
			$data = [
				'action' => 'send',
				'userid' => '',
				'account' => $this->key_id,
				'password' => $this->key_secret,
				'mobile' => $this->country_num . $mobile,
				'extno' => '',
				'code' => 8,
				'content' => strtoupper(bin2hex(iconv('utf-8', 'UCS-2BE', "{$content}【{$sign}】"))),
				'sendtime' => '',
			];
			$res = requestUrl('post', 'https://dx.ipyy.net/I18NSms.aspx', $data, true);
		} else {
			//中文
			if (!$this->key_id) return 'Lost api key';
			$content = urlencode("{$content}【{$sign}】");
			$account = $this->key_id;
			$password = strtoupper(md5($account));
			$url = "https://dx.ipyy.net/smsJson.aspx?action=send&userid=&account=$account&password=$password&mobile=$mobile&content=$content&sendTime=&extno=";
			$res = requestUrl('get', $url, null, true);
		}
		if (!$res) return 'FAILED, NO ERROR';
		return $res;
	}
	
	//ucpaas
	private function ucpaas($mobile, $content, $template_id=0, $sign='') {
		if (!$this->key_id) return 'Lost api key';
		$data = [
			'sid' => '',
			'token' => '',
			'appid' => $this->key_id,
			'templateid' => $template_id,
			'param' => 'code="'.$content.'"',
			'mobile' => $mobile,
		];
		$res = requestUrl('post', 'https://open.ucpaas.com/ol/sms/sendsms', json_encode($data), true);
		if (!$res) return 'FAILED, NO ERROR';
		return $res;
	}
}
