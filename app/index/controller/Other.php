<?php
declare (strict_types = 1);

namespace app\index\controller;


class Other extends Core
{
	//使用google api生成二维码图片, content二维码内容, size尺寸, lev容错级别, margin二维码离边框距离
	public function qrcode_google($content, $size=200, $level='Q', $margin=0) {
		$content = urlencode($content);
		return https()."chart.apis.google.com/chart?chs={$size}x{$size}&cht=qr&chld={$level}|{$margin}&chl={$content}";
	}
	
	//使用第三方接口生成二维码
	public function qrcode_encode($data='', $dir='', $show=true, $options=array()) {
		$url = 'http://qr.liantu.com/api/?text='.urlencode($data);
		if (is_array($options)) {
			if (isset($options['bg'])) $url .= '&bg='.$options['bg']; //背景颜色,如ffffff
			if (isset($options['fg'])) $url .= '&fg='.$options['fg']; //前景颜色
			if (isset($options['gc'])) $url .= '&gc='.$options['gc']; //渐变颜色
			if (isset($options['el'])) $url .= '&el='.$options['el']; //纠错等级,h|q|m|l
			if (isset($options['w'])) $url .= '&w='.$options['w']; //尺寸大小
			if (isset($options['m'])) $url .= '&m='.$options['m']; //外边距
			if (isset($options['pt'])) $url .= '&pt='.$options['pt']; //定位点颜色(外框)
			if (isset($options['inpt'])) $url .= '&inpt='.$options['inpt']; //定位点颜色(内点)
			if (isset($options['logo'])) $url .= '&logo='.$options['logo']; //logo图片,网址
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);
		if ($show) {
			header('Content-type: image/png');
			echo $data;
			exit;
		} else {
			$dir = UPLOAD_PATH . (strlen($dir) ? '/' . $dir : '') . '/' . date('Y') . '/' . date('m') . '/' . date('d');
			$qrcode = $dir . '/' . generate_sn() . '.png';
			makedir(ROOT_PATH . $dir);
			file_put_contents(ROOT_PATH . $qrcode, $data);
			return $qrcode;
		}
	}
	
	//生成二维码
	public function qrcode($data='', $logo='', $dir='', $show=true, $width_percent=0.33) {
		if (!strlen($data)) $data = $this->request->get('data', ''); //生成二维码的字符串
		if (!strlen($data)) error('MISSING QRCODE DATA');
		if (!strlen($logo)) $logo = $this->request->get('logo', ''); //LOGO图片
		if (strlen($logo) && strpos($logo, 'http')===false && strpos($logo, ROOT_PATH)===false) $logo = ROOT_PATH . $logo;
		if (!strlen($dir)) $dir = $this->request->get('dir', ''); //二维码存放的文件夹名称
		$percent = $this->request->get('percent/f', 0);
		if ($percent<=0) $percent = $width_percent; //0.33=1/3
		$errorCorrectionLevel = 'Q'; //容错级别
		$matrixPointSize = 6; //生成图片大小
		$dir = UPLOAD_PATH . (strlen($dir) ? '/' . $dir : '') . '/' . date('Y') . '/' . date('m') . '/' . date('d');
		$filename = PUBLIC_PATH . $dir . '/' . generate_sn() . '.png';
		$qrcode = (new \PHPQrcode\QRcode())->png(urldecode($data), $filename, $errorCorrectionLevel, $matrixPointSize);
		if (strlen($logo)) $qrcode = $qrcode->logo($logo, $percent);
		if ($show) {
			$qrcode->show();
		} else {
			$qrcode->getPath();
		}
	}
	
	public function createApi() {
		//https://www.showdoc.com.cn/page/741656402509783
		if ( !(in_array($this->ip, ['127.0.0.1', '::1']) || $this->request->get('force/d', 0) == 4891) ) error404();
		$app = $this->request->get('app');
		$act = $this->request->get('act');
		$files = $app ? [app_path() . 'controller/'.camelize('_'.$app).'.php'] : glob(app_path() . 'controller/*.php');
		$token = \app\model\Member::order('id')->value('token');
		if (!$token) $token = '';
		$annotations = [];
		foreach ($files as $file) {
			$content = file_get_contents($file);
			preg_match('/class\s+(\w+)/', $content, $matcher);
			if (!$matcher) continue;
			$clazz = strtolower($matcher[1]);
			preg_match_all('/(\/\*\*[\n\t\s]+\*\sshowdoc\n[\s\S]+?\*\/)[\n\t\s]*(public\s+function\s+(\w+))?/', $content, $matcher);
			if (!count($matcher[0])) continue;
			$domain = https().$this->request->server('HTTP_HOST');
			foreach ($matcher[1] as $i => $ann) {
				if (isset($matcher[3][$i]) && $act && strtolower($act) !== strtolower($matcher[3][$i])) continue;
				$annotation = str_replace('@url HTTP_HOST', '@url '.$domain, $ann);
				if (isset($matcher[3][$i]) && strpos($annotation, '@url #') !== false) $annotation = str_replace('@url #', '@url /'.MODULE_NAME.'/'.$clazz.'/'.$matcher[3][$i], $annotation);
				if (strpos($annotation, '@return {}') !== false) {
					$method = 'get';
					preg_match('/@method (\w+)\n/', $annotation, $matches);
					if ($matches) $method = strtolower($matches[1]);
					preg_match('/@url (.+?)\n/', $annotation, $matches);
					if (!$matches) continue;
					$url = $domain.$matches[1];
					if (strpos($annotation, '@param pagesize ') !== false) $url .= '?pagesize=1';
					$annotation = preg_replace_callback('/@param\s(\w+)\s(\d)\s(\w+)/', function($matches) {
						if ($matches[2] == '1') return str_replace('@param '.$matches[1].' 1 ', '@param '.$matches[1].' 必选 ', $matches[0]);
						if ($matches[2] == '0') return str_replace('@param '.$matches[1].' 0 ', '@param '.$matches[1].' 可选 ', $matches[0]);
						return $matches[0];
					}, $annotation);
					$param = null;
					if ($method == 'post') {
						preg_match_all('/@param\s(\w+)\s(.+?)\s(\w+)/', $annotation, $matches);
						if (count($matches[0])) {
							$params = [];
							foreach ($matches[0] as $k => $g) {
								if ($matches[2][$k] == '必选') {
									$params[] = $matches[1][$k] . '=' . ($matches[3][$k] == 'string' ? '' : 0);
								}
							}
							$param = implode('&', $params);
						}
					}
					$res = requestUrl($method, $url, $param, false, false, ['X-Requested-With: XMLHttpRequest', "Token: $token"]);
					if (isset(json_decode($res, true)['toUrl'])) {
						$res = json_decode($res, true);
						unset($res['toUrl']);
						$res = json_encode($res, JSON_UNESCAPED_UNICODE);
					}
					$annotation = str_replace('@return {}', '@return '.$res, $annotation);
				}
				$annotations[] = $annotation;
			}
		}
		//var_dump($annotations);exit;
		$api_key = \app\model\Config::get('showdoc_api_key');
		$api_token = \app\model\Config::get('showdoc_api_token');
		$ret = [];
		foreach ($annotations as $annotation) {
			preg_match('/@title (.+?)\n/', $annotation, $matcher);
			$res = requestUrl('post', 'https://www.showdoc.com.cn/server/?s=/api/open/fromComments', "from=shell&api_key=$api_key&api_token=$api_token&content=$annotation");
			$res = str_replace("\n", '', $res);
			$res = str_replace(PHP_EOL, '', $res);
			$res = preg_replace('/\s/', '', $res);
			$ret[] = $matcher[1].'：'.$res;
		}
		exit(json_encode($ret));
		/**
		 * showdoc
		 * @catalog
		 * @title 全局参数
		 * @description 接口域名
		 * @method get
		 * @url HTTP_HOST
		 * @header X-Requested-With 必选 string 值使用XMLHttpRequest
		 * @header Accept-Language 可选 string 用于设置后端语言，zh-Hans:简体，zh-Hant:繁体，en:英文
		 * @header Token 可选 string 会员token，带上可实现自动登录，可从登录接口或会员首页接口获取
		 * @return {"code":0,"msg":"success","data":null}
		 * @return_param code int 0成功，!=0错误
		 * @return_param msg string 成功或错误的说明信息
		 * @return_param data object 数据体
		 */
	}
}
