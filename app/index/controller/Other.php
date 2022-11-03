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
	
	//生成二维码, logo_width 小于 1 即按百分比计算
	public function qrcode($data='', $logo='', $dir='', $show=true, $logo_width=0.33) {
		if (!strlen($data)) $data = $this->request->get('data', ''); //生成二维码的字符串
		if (!strlen($data)) error('MISSING QRCODE DATA');
		if (!strlen($logo)) $logo = $this->request->get('logo', ''); //LOGO图片
		if (strlen($logo) && strpos($logo, 'http')===false && strpos($logo, ROOT_PATH)===false) $logo = ROOT_PATH . $logo;
		if (!strlen($dir)) $dir = $this->request->get('dir', ''); //二维码存放的文件夹名称
		$width = $this->request->get('logo_width/f', 0);
		if ($width <= 0) $width = $logo_width; //0.33=1/3
		$errorCorrectionLevel = 'Q'; //容错级别
		$matrixPointSize = 300; //生成图片大小
		$dir = UPLOAD_PATH . (strlen($dir) ? '/' . $dir : '') . '/' . date('Y') . '/' . date('m') . '/' . date('d');
		$filename = PUBLIC_PATH . $dir . '/' . generate_sn() . '.png';
		$qrcode = (new \PHPQrcode\QRcode())->png(urldecode($data), $filename, $errorCorrectionLevel, $matrixPointSize);
		if (strlen($logo)) $qrcode = $qrcode->logo($logo, $width);
		if ($show) {
			$qrcode->show();
		} else {
			$qrcode->getPath();
		}
	}
	
	//显示前端接口文档
	public function showApi($is_create = false) {
		if ( !(in_array($this->ip, ['127.0.0.1', '::1']) || $this->request->get('force/d', 0) == 4891) ) error404();
		$api_key = config('app.showdoc_api_key');
		$api_token = config('app.showdoc_api_token');
		if ($is_create && (!$api_key || !$api_token)) error404();
		set_time_limit(0);
		$path = $this->request->get('path');
		if ($path) {
			$path = preg_replace('/^\/index\//', '', $path);
			$path = explode('/', trim($path, '/'));
			$app = $path[0];
			$act = count($path) > 1 ? $path[1] : 'index';
		} else {
			$app = $this->request->get('app');
			$act = $this->request->get('act');
		}
		$files = $app ? [app_path() . 'controller/'.camelize('_'.$app).'.php'] : glob(app_path() . 'controller/*.php');
		$token = \app\model\Member::order('id')->value('token');
		if (!$token) $token = '';
		$domain = https() . $this->request->server('HTTP_HOST');
		$not_check_login = config('app.not_check_login');
		$annotations = [];
		foreach ($files as $file) {
			$content = file_get_contents($file);
			preg_match('/class\s+(\w+)/', $content, $matcher);
			if (!$matcher) continue;
			$clazz = uncamelize($matcher[1]);
			preg_match_all('/(\/\*\*[\n\t\s]+\*\sshowdoc\n[\s\S]+?\*\/)[\n\t\s]*(public\s+function\s+(\w+)|})/', $content, $matcher);
			if (!count($matcher[0])) continue;
			foreach ($matcher[1] as $i => $ann) {
				if (!isset($matcher[3][$i])) continue;
				$methods = $matcher[3][$i];
				if ($act && strtolower($act) !== strtolower($methods)) continue;
				preg_match_all('/(\/\*\*[\n\t\s]+\*\sshowdoc\n[\s\S]+?\*\/)/', $ann, $m);
				foreach ($m[1] as $annotation) {
					$annotation = str_replace('@url HTTP_HOST', '@url -#-', $annotation);
					if (strpos($annotation, '@url #') !== false) $annotation = str_replace('@url #', '@url /' . MODULE_NAME . '/' . $clazz . '/' . $methods, $annotation);
					if (strpos($annotation, '@return {}') !== false) {
						$method = 'get';
						preg_match('/@method (\w+)\n/', $annotation, $matches);
						if ($matches) $method = strtolower($matches[1]);
						preg_match('/@url (.+?)\n/', $annotation, $matches);
						if (!$matches) continue;
						$url = $domain.$matches[1];
						if (strpos($annotation, '@param pagesize ') !== false) $url .= (strpos($url, '?') === false ? '?' : '&') . 'pagesize=1';
						if (strpos($annotation, '@param id ') !== false) $url .= (strpos($url, '?') === false ? '?' : '&') . 'id=1';
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
									if ($matches[2][$k] == '必选') $params[] = $matches[1][$k] . '=' . ($matches[3][$k] == 'string' ? '' : 0);
								}
								$param = implode('&', $params);
							}
						}
						$headers = ['X-Requested-With: XMLHttpRequest'];
						if (is_array($not_check_login)) {
							if ( !isset($not_check_login[$clazz]) ||
								(!in_array('*', $not_check_login[$clazz]) && !in_array($methods, array_map('strtolower', $not_check_login[$clazz]))) ) {
								//$trims = array('/some/', '/link');
								//$ret = array_map('trim', $trims, array_fill(0, count($trims), '/'));
								$headers[] = 'Token: ' . $token;
							}
						}
						$res = requestUrl($method, $url, $param, false, false, $headers);
						if (!json_decode($res, true)) {
							dump($url);echo $res;exit;
						}
						if (isset(json_decode($res, true)['toUrl'])) {
							$res = json_decode($res, true);
							unset($res['toUrl']);
							$res = json_encode($res, JSON_UNESCAPED_UNICODE);
						}
						$annotation = str_replace('@return {}', '@return '.$res, $annotation);
					}
					$annotation = str_replace('@url -#-', '@url #', $annotation);
					preg_match('/\n([\t\s] \* )@return_param (\w+)\[(.+?)]\n/', $annotation, $matches);
					//@return_param article[*] 或者 @return_param article[id, title, add_time]
					if ($matches) {
						if ($matches[3] == '*') {
							$fields =  '*';
						} else {
							$fields = [];
							$arr = explode(',', $matches[3]);
							foreach ($arr as $item) $fields[] = trim($item);
						}
						$res = t(m($matches[2]), $fields, true);
						$return_param = [];
						foreach ($res as $field => $item) {
							$return_param[] = $matches[1] . '@return_param ' . $field . ' ' . (in_array($item['type'], ['datetime', 'date', 'time'])?'string':$item['type']) . ' ' . urlencode($item['comment'] ?: $field);
						}
						$annotation = str_replace($matches[0], PHP_EOL . implode(PHP_EOL, $return_param) . PHP_EOL, $annotation);
					}
					$annotations[] = $annotation;
				}
			}
		}
		$res = [];
		foreach ($annotations as $item) {
			preg_match('/@catalog( (.+?))?\n/', $item, $matcher);
			$catalog = isset($matcher[1]) ? $matcher[2] : '-';
			preg_match('/@title (.+?)\n/', $item, $matcher);
			$title = $matcher[1];
			$method = 'GET';
			preg_match('/@method (\w+)\n/', $item, $matcher);
			if ($matcher) $method = strtoupper($matcher[1]);
			preg_match('/@url (.+?)\n/', $item, $matcher);
			$url = $matcher ? $matcher[1] : '';
			$result = '';
			if ($is_create) {
				$ret = requestUrl('post', 'https://www.showdoc.com.cn/server/?s=/api/open/fromComments', "from=shell&api_key=$api_key&api_token=$api_token&content=$item");
				$ret = str_replace("\n", '', $ret);
				$ret = str_replace(PHP_EOL, '', $ret);
				$ret = preg_replace('/\s+/', '', $ret);
				$result = $ret;
			}
			$res[] = compact('catalog', 'title', 'method', 'url', 'result');
		}
		$anns = [];
		foreach ($res as $item) {
			if ($item['title'] == '全局参数') array_unshift($anns, $item);
			else $anns[] = $item;
		}
		$annotations = [];
		foreach ($anns as $item) {
			if ($item['result'] != '成功') array_unshift($annotations, $item);
			else $annotations[] = $item;
		}
		$ret = '<meta charset="UTF-8">' . PHP_EOL;
		$ret .= '<style>@font-face{font-family:"DINPro"; src:url("/static/css/DINPro-Bold.otf"); font-weight:normal; font-style:normal;}*{font-size:14px;font-family:"PingFang SC","Microsoft YaHei",serif;}body{-webkit-font-smoothing:antialiased;}div{width:100%;}table{border-collapse:collapse;width:100%;}thead tr{position:sticky;top:0;background:#fff;}th,td{text-align:left;padding:8px;font-weight:normal;}td{color:#768b29;border:1px solid #ccc;}tbody tr td:nth-child(1){font-family:"DINPro",serif;color:#333;font-weight:700;}tbody tr td:nth-child(2){font-weight:700;}tbody tr td:nth-child(4){color:#999;font-family:"DINPro",serif;}tbody tr td:nth-child(5){color:#398bfc;font-family:"DINPro",serif;}td.error{color:#d11400;}tbody tr:nth-child(even){background:#fafafc;}a,a:hover{color:#398bfc;text-decoration:none;font-family:"DINPro",serif;}a[href*="showdoc"]{display:block;margin:15px 0;}</style>' . PHP_EOL;
		$ret .= '<div><table><thead><tr><th width="90">序号</th><th width="200">目录</th><th width="200">类型</th><th width="90">请求</th><th>接口</th>'.($is_create ? '<th>结果</th>' : '').'</tr></thead><tbody>' . PHP_EOL;
		foreach ($annotations as $index => $item) {
			$resultRow = '';
			if ($item['result']) {
				$errorClass = $item['result'] !== '成功' ? ' class="error"' : '';
				$resultRow = "<td$errorClass>".$item['result']."</td>";
			}
			$ret .= "<tr><td>".($index+1)."</td><td>".$item['catalog']."</td><td>".$item['title']."</td><td>".$item['method']."</td><td><a href='".$item['url']."' target='_blank'>".$item['url']."</a></td>$resultRow</tr>" . PHP_EOL;
		}
		$ret .= '</tbody></table>' . PHP_EOL;
		$ret .= '<a href="https://www.showdoc.com.cn/item/index" target="_blank">https://www.showdoc.com.cn/item/index<a></div>';
		exit($ret);
		//https://www.showdoc.com.cn/page/741656402509783
		/**
		 * showdoc
		 * @catalog
		 * @title 全局参数
		 * @method get
		 * @url HTTP_HOST
		 * @header X-Requested-With 必选 string 值使用XMLHttpRequest
		 * @header Accept-Language 可选 string 用于设置后端语言，[zh-Hans,zh-CN]:简体，[zh-Hant,zh-TW]:繁体，en:英文
		 * @header Token 可选 string 会员token，设置实现自动登录，可从登录或会员首页获取
		 * @return {"code":0, "msg":"success", "data":null}
		 * @return_param code int 0成功，!=0错误
		 * @return_param msg string 成功或错误的说明信息
		 * @return_param data object 数据体
		 */
	}
	//生成前端接口文档
	public function createApi() {
		$this->showApi(true);
	}
}
