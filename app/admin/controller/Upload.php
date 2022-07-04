<?php
declare (strict_types = 1);

namespace app\admin\controller;

class Upload extends Core
{
	public function editor() {
		$dir = $this->request->request('dir/s', 'editor');
		$file = upload_file('upload', $dir);
		if (!isset($file['error'])) {
			$file = add_domain($file);//uploaded
			return json(['uploaded'=>1, 'url'=>$file]);
		}
		return json(['uploaded'=>0, 'fail'=>$file['error']]);
	}
	
	public function image() {
		$suffix = $this->request->request('suffix/s', '');
		if (strlen($suffix)) return $this->file();
		$filename = $this->request->request('filename/s', 'file');
		$dir = $this->request->request('dir/s', 'pic');
		$file = upload_file($filename, $dir);
		if (isset($file['error'])) return json(['code'=>1, 'msg'=>$file['error']]);
		return json(['code'=>0, 'data'=>$file]);
	}
	
	public function video() {
		$filename = $this->request->request('filename/s', 'file');
		$dir = $this->request->request('dir/s', 'video');
		$file = upload_file($filename, $dir, false, false, ['mp4']);
		if (isset($file['error'])) return json(['code'=>1, 'msg'=>$file['error']]);
		return json(['code'=>0, 'data'=>$file]);
	}
	
	public function file() {
		$filename = $this->request->request('filename/s', 'file');
		$dir = $this->request->request('dir/s', 'file');
		$suffix = $this->request->request('suffix/s', '');
		if (!strlen($suffix)) return json(['code'=>1, 'msg'=>'DATA ERROR']);
		$file = upload_file($filename, $dir, 2, false, explode(',', $suffix));
		if (isset($file['error'])) return json(['code'=>1, 'msg'=>$file['error']]);
		return json(['code'=>0, 'data'=>$file]);
	}
	
	//ckediter微信文章内容采集
	/*
	public function wechat_collect() {
		$url = $this->request->post('url');
		$dir = $this->request->get('dir', 'content');
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
		success(compact('title', 'content'));
	}
	private function _getFile($url, $type='') {
		global $upload_type;
		if (!strlen($url)) return '';
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$suffix = '';
		$timeout = 60*60;
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
			$file = upload_obj_file($content, 'article', NULL, UPLOAD_LOCAL, false, ['jpg', 'jpeg', 'png', 'gif', 'bmp'], ".{$suffix}");
		}
		$file = add_domain($file);
		return $file;
	}
	*/
}
