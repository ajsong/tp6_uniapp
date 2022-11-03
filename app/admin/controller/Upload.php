<?php
declare (strict_types = 1);

namespace app\admin\controller;

class Upload extends Core
{
	public function editor() {
		$filename = $this->request->request('filename/s', 'upload');
		$dir = $this->request->request('dir/s', 'editor');
		$file = upload_file($filename, $dir);
		if (isset($file['error'])) return json(['uploaded'=>1, 'fail'=>$file['error']]);
		return json(['uploaded'=>1, 'url'=>add_domain($file)]);
	}
	
	public function image($filename='', $dir='', $suffix='') {
		$return_file = false;
		if (strlen($filename)) $return_file = true;
		if (!strlen($suffix)) $suffix = $this->request->request('suffix/s', '');
		if (strlen($suffix) && $suffix != 'jpg,jpeg,png,gif,bmp') return $this->file();
		if (!strlen($filename)) $filename = $this->request->request('filename/s', 'file');
		if (!strlen($dir)) $dir = $this->request->request('dir/s', 'pic');
		$file = upload_file($filename, $dir);
		$res = isset($file['error']) ? ['code'=>1, 'msg'=>$file['error']] : ['code'=>0, 'data'=>$file];
		if ($return_file) return $res['code'] == 0 ? $res['data'] : error($res['msg']);
		return json($res);
	}
	
	public function video($filename='', $dir='', $suffix='') {
		$return_file = false;
		if (strlen($filename)) $return_file = true;
		if (!strlen($suffix)) $suffix = $this->request->request('suffix/s', '');
		if (strlen($suffix) && $suffix != 'mp4') return $this->file();
		if (!strlen($filename)) $filename = $this->request->request('filename/s', 'file');
		if (!strlen($dir)) $dir = $this->request->request('dir/s', 'video');
		$file = upload_file($filename, $dir, 2, false, ['mp4']);
		$res = isset($file['error']) ? ['code'=>1, 'msg'=>$file['error']] : ['code'=>0, 'data'=>$file];
		if ($return_file) return $res['code'] == 0 ? $res['data'] : error($res['msg']);
		return json($res);
	}
	
	public function file($filename='', $dir='', $suffix='') {
		$return_file = false;
		if (strlen($filename)) $return_file = true;
		if (!strlen($suffix)) $suffix = $this->request->request('suffix/s', '');
		if (!strlen($suffix)) return json(['code'=>1, 'msg'=>'DATA ERROR']);
		if (!strlen($filename)) $filename = $this->request->request('filename/s', 'file');
		if (!strlen($dir)) $dir = $this->request->request('dir/s', 'file');
		$file = upload_file($filename, $dir, 2, false, explode(',', $suffix));
		$res = isset($file['error']) ? ['code'=>1, 'msg'=>$file['error']] : ['code'=>0, 'data'=>$file];
		if ($return_file) return $res['code'] == 0 ? $res['data'] : error($res['msg']);
		return json($res);
	}
	
	//ckediter微信文章内容采集
	public function wechat_collect() {
		$url = $this->request->post('url');
		$dir = $this->request->get('dir', 'editor');
		if ($url) error('缺少文章链接');
		$html = requestUrl('get', $url);
		preg_match('/<meta property="og:title" content="(.+?)" \/>/', $html, $matcher);
		if (!$matcher) error('文章缺少指定采集标识: og:title');
		$title = $matcher[1];
		preg_match('/<div class="rich_media_content " id="js_content" style="visibility: hidden;">([\s\S]+?)<\/div>/', $html, $matcher);
		if (!$matcher) error('文章缺少指定采集标识: js_content');
		$content = str_replace('data-src=', 'src=', trim($matcher[1]));
		$content = str_replace('iframe/preview.html', 'iframe/player.html', $content);
		$content = preg_replace('/width=\d+&amp;height=\d+&amp;/', '', $content);
		preg_match_all('/<img .*?src="([^"]+)"/', $content, $matcher);
		if ($matcher) {
			foreach ($matcher[1] as $n) {
				$u = $this->_getFile($n);
				$content = str_replace($n, $u, $content);
			}
		}
		preg_match_all('/background-image:\s*url\(([^)]+)\)/', $content, $matcher);
		if ($matcher) {
			foreach ($matcher[1] as $n) {
				$n = str_replace('"', '', str_replace('&quot;', '', $n));
				$u = $this->_getFile($n);
				$content = str_replace($n, $u, $content);
			}
		}
		return success(compact('title', 'content'));
	}
	private function _getFile($url, $type='') {
		global $upload_type;
		if (!strlen($url)) return '';
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$suffix = '';
		$timeout = 60 * 60;
		switch ($type) {
			case 'video':
				$url = explode('.mp4', $url);
				$url = $url[0].'.mp4';
				$suffix = 'mp4';
				break;
			default:
				if (stripos($url, 'image/svg+xml') !== false || stripos($url, 'wx_fmt=svg') !== false) return $url;
				if (strpos($url, 'wx_fmt=') !== false) $suffix = substr($url, strrpos($url, 'wx_fmt=')+7);
				if (!strlen($suffix) && preg_match('/\bfmt=\w+\b/', $url)) {
					preg_match('/\bfmt=(\w+)\b/', $url, $matcher);
					$suffix = $matcher[1];
				}
				if (!strlen($suffix)) $suffix = substr($url, strrpos($url, '.')+1);
				if (!preg_match('/^(jpe?g|png|gif|bmp)$/', $suffix)) $suffix = 'jpg';
				if ($suffix == 'jpeg') $suffix = 'jpg';
				//$timeout = (preg_match('/^(jpe?g|png)$/', $suffix) ? 5 : 60*5);
				break;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		if (substr($url, 0, 8) == 'https://') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		$content = curl_exec($ch);
		$header_info = curl_getinfo($ch);
		if (intval($header_info['http_code']) != 200) return $url;
		curl_close($ch);
		if ($type == 'video') {
			$name = generate_sn();
			$dir = UPLOAD_PATH.'/video/'.date('Y').'/'.date('m').'/'.date('d');
			$upload = p('upload', $upload_type);
			$result = $upload->upload($content, NULL, str_replace('/public/', '/', $dir), $name, $suffix);
			$file = $result['file'];
		} else {
			$file = upload_file($content, 'article');
		}
		return add_domain($file);
	}
}
