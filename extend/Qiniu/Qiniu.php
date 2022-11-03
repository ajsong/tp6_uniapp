<?php
//七牛
//https://www.qiniu.com
namespace Qiniu;

require_once('Qiniu/autoload.php');
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;

class Qiniu {
	private $bucketname = '';
	private $accessKey = '';
	private $secretKey = '';
	private $domain = '';
	
	public function __construct($bucketname, $accessKey, $secretKey, $domain) {
		$this->bucketname = $bucketname;
		$this->accessKey = $accessKey;
		$this->secretKey = $secretKey;
		$this->domain = $domain;
	}
	
	public function upload($obj, $name, $ext='jpg', $dir='') {
		if (!strlen($this->bucketname) || !strlen($this->accessKey) || !strlen($this->secretKey)) {
			exit('QINIU UPLOAD LOST API KEY');
		}
		if (strlen($dir)) {
			$dir = trim($dir, '/');
			$file = $dir . '/' . $name . '.' . $ext;
		} else {
			$file = $name . '.' . $ext;
		}
		try {
			$auth = new Auth($this->accessKey, $this->secretKey);
			$token = $auth->uploadToken($this->bucketname);
			$uploadMgr = new UploadManager();
			if (is_array($obj)) {
				list($ret, $err) = $uploadMgr->putFile($token, $file, isset($obj['tmp_name']) ? $obj['tmp_name'] : $obj[0]);
			} else {
				ini_set('memory_limit', '500M');
				list($ret, $err) = $uploadMgr->put($token, $file, $obj);
			}
			if ($err !== null) {
				exit($err->message());
			} else {
				$file = $ret['key'];
				if (substr($this->domain, -1) != '/') $this->domain .= '/';
				if (substr($file, 0, 1) == '/') $file = substr($file, 1);
				$file = $this->domain . $file;
			}
		}
		catch (Exception $e) {}
		return $file;
	}
	
	public function delete($url) {
		if (strpos($url, $this->domain) === false) return false;
		if (substr($this->domain, -1) != '/') $this->domain .= '/';
		$key = str_replace($this->domain, '', $url);
		$auth = new Auth($this->accessKey, $this->secretKey);
		$mgr = new BucketManager($auth);
		$mgr->delete($this->bucketname, $key);
		return true;
	}
}
