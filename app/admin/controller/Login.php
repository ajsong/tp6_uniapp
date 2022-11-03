<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\Base;
use think\facade\Db;

class Login extends Base
{
    public function index() {
		if (IS_POST) {
			$username = $this->request->post('username');
			$password = $this->request->post('password');
			$remember = $this->request->post('remember/d', 0);
			if (!strlen($username) || !strlen($password)) error('请输入账号与密码');
			$manage = \app\model\Manage::whereName($username)->find();
			if (!$manage) error('用户不存在');
			if (crypt_password($password, $manage->salt) != $manage->password) error('账号或密码错误');
			if ($manage->status == 0) error('当前账号已被冻结');
			unset($manage->password, $manage->salt);
			$token = generate_token();
			\app\model\Manage::whereId($manage->id)->update(['token'=>$token]);
			if (cookie('?manage_token')) cookie('manage_token', null);
			session('manage', null);
			session('manage', $manage);
			if ($remember) {
				cookie('manage_token', $token, 60*60*24*365);
			}
			$url = session('manage_gourl');
			if ($url) {
				session('manage_gourl', null);
				return success("tourl:$url");
			}
			return success('tourl:/'.MODULE_NAME.'/index');
		}
	    return success();
    }
	
	public function logout() {
		if (cookie('?manage_token')) cookie('manage_token', null);
		session('manage', null);
		$this->redirect('/'.MODULE_NAME.'/login');
	}
}
