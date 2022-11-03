<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\model\ManageLog;

class Index extends Core {
    public function index() {
	    //会员数量
	    $user['total'] = \app\model\Member::count();
	    $user['today'] = \app\model\Member::whereTime('reg_time', 'today')->count();
		return $this->render([
			'user' => $user
		]);
    }
	
	//修改密码
	public function update() {
		if (IS_POST) {
			$old_password = $this->request->post('old_password');
			$password = $this->request->post('password');
			$password_confirmation = $this->request->post('password_confirmation');
			if (!$old_password || !$password || !$password_confirmation) error('请填写所有项');
			if ($password != $password_confirmation) error('两次密码不一致');
			$manage = \app\model\Manage::whereId($this->manageId)->field('password, salt')->find();
			if ($manage->password != crypt_password($old_password, $manage->salt)) error('原密码错误');
			list($password, $salt) = generate_password($password);
			\app\model\Manage::whereId($this->manageId)->update(compact('password', 'salt'));
			if (cookie('?manage_token')) cookie('manage_name', null);
			session('manage', null);
			$this->redirect('/'.MODULE_NAME.'/login');
		}
		return $this->render();
	}
	
	//操作日志
	public function log() {
		$type = $this->request->get('type');
		$keyword = $this->request->get('keyword');
		$where = [];
		if ($type) $where[] = ['type', '=', $type];
		if ($keyword) $where[] = ['manage_name|remark|ip', 'like', "%$keyword%"];
		$list = ManageLog::where($where)->order('id', 'desc')->paginate($this->paginateArr());
		return $this->render([
			'list' => $list,
			'types' => ManageLog::getTypes(),
		]);
	}
	
	//修改数据库前缀
	public function changePrefix() {
		if ($this->manageObj->super != 1) error404();
		$old_prefix = $this->request->post('old_prefix', '');
		$prefix = $this->request->post('prefix', '');
		if (!strlen($old_prefix) || !strlen($prefix)) error('请输入旧/新表前缀');
		$res = changeTablePrefix($old_prefix, $prefix);
		return success(null, '修改成功，表新前缀为 '.$res);
	}
	
	//修改后台路径
	public function changePath() {
		if ($this->manageObj->super != 1) error404();
		$path = https().$this->request->host().'/'.changeGmPath();
		echo '<meta charset="UTF-8">修改成功，后台新路径为 <a href="'.$path.'">'.$path.'</a>';
		exit;
	}
}
