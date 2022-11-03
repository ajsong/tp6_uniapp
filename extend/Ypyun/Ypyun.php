<?php
//又拍云
//https://www.upyun.com
namespace Ypyun;
use UpYun;

class Ypyun {
	private $bucketname, $operator_name, $operator_pwd, $domain;
	
	public function __construct($bucketname, $operator_name, $operator_pwd, $domain) {
		$this->bucketname = $bucketname;
		$this->operator_name = $operator_name;
		$this->operator_pwd = $operator_pwd;
		$this->domain = $domain;
	}
	
	public function upload($obj, $name, $ext='jpg', $dir='') {
		if (!strlen($this->bucketname) || !strlen($this->operator_name) || !strlen($this->operator_pwd)) {
			exit('UPLOAD LOST API KEY');
		}
		$file_array = array('file'=>'', 'width'=>0, 'height'=>0);
		if (is_array($obj) && isset($obj['type']) && stripos($obj['type'],'image/')!==false) {
			$size = getimagesize($obj['tmp_name']);
			$file_array['width'] = $size[0];
			$file_array['height'] = $size[1];
		}
		if (strlen($dir)) {
			$dir = trim($dir, '/');
			$file = $dir.'/'.$name.'.'.$ext;
		} else {
			$file = $name.'.'.$ext;
		}
		$upyun = new UpYun($this->bucketname, $this->operator_name, $this->operator_pwd);
		try {
			ini_set('memory_limit', '500M');
			if (is_array($obj)) {
				$local_file = isset($obj['tmp_name']) ? $obj['tmp_name'] : $obj[0];
				$fh = fopen($local_file, 'rb');
				$upyun->writeFile($file, $fh, true); //上传文件且自动创建目录
				fclose($fh);
			} else {
				$upyun->writeFile($file, $obj, true);
			}
			$upyun_domain = $this->domain;
			if (substr($upyun_domain,-1)!='/') $upyun_domain .= '/';
			if (substr($file, 0, 1)=='/') $file = substr($file, 1);
			$file_array['file'] = $upyun_domain . $file;
		}
		catch (Exception $e) {}
		return $file_array;
	}
	
	public function delete($url) {
		return true;
	}
}
