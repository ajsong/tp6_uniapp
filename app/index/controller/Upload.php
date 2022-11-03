<?php
declare (strict_types = 1);

namespace app\index\controller;

class Upload extends Core
{
	
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
}
