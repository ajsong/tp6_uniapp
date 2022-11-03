<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\model\ManageGroup;
use think\facade\Cache;

//@system
class Manage extends Core
{
    public function index() {
		$keyword = $this->request->get('keyword');
		$where = [
			['m.super', '=', 0],
		];
		if ($keyword) $where[] = ['m.name', 'like', "%$keyword%"];
		$list = \app\model\Manage::alias('m')->where($where);
		if ($this->manageObj['super'] == 0) $list = $list->whereRaw("m.id=$this->manageId OR CONCAT(',',parent_tree,',') LIKE '%,$this->manageId,%'");
	    $list = $list->leftJoin('manage_group mg', 'mg.id=m.group_id')
			->field('m.*, mg.name as group_name, mg.power')
			->paginate($this->paginateArr());
		return $this->render([
			'list' => $list
		]);
    }
	
	//@管理员添加
	public function add() {
		return $this->edit();
	}
	//@管理员修改
	public function edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$name = $this->request->post('name');
			$password = $this->request->post('password');
			$group_id = $this->request->post('group_id/d', 0);
			if (!$name) error('请输入账号');
			$data = compact('name');
			if ($this->check_permission('manage', 'group_edit')) $data['group_id'] = $group_id;
			if ($id > 0) {
				if ($password) {
					list($password, $salt) = generate_password($password);
					$data['password'] = $password;
					$data['salt'] = $salt;
				}
				\app\model\Manage::update($data, ['id'=>$id]);
			} else {
				if (!$password) error('请输入密码');
				if (\app\model\Manage::whereName($name)->count > 0) error('该账号已存在');
				list($password, $salt) = generate_password($password);
				$data['password'] = $password;
				$data['salt'] = $salt;
				$data['parent_id'] = $this->manageId;
				$data['parent_tree'] = trim($this->manageParentTree.','.$this->manageId, ',');
				$data['add_time'] = time();
				\app\model\Manage::create($data);
			}
			return success('tourl:manage/index', '提交成功');
		} else if ($id > 0) {
			$row = \app\model\Manage::where('m.id', $id)->alias('m')->leftJoin('manage_group mg', 'mg.id=m.group_id')->field('m.*, mg.power')->find();
			if (!$row) error('记录不存在');
			if ($row->power == 1 && $this->manageObj['super'] != 1) error('系统默认用户，不能修改', '/'.MODULE_NAME.'/manage/index');
		} else {
			$row = t('manage');
		}
		$group = ManageGroup::order('id')->select();
		return $this->render([
			'row' => $row,
			'group' => $group
		], 'edit');
	}
	
	//@管理员删除
	public function delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Manage::destroy(['id'=>$id]);
		return $this->render();
	}
	
	//@管理员冻结
	public function freeze() {
		$id = $this->request->get('id/d', 0);
		$status = $this->request->get('status/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Manage::update(['status' => $status], ['id'=>$id]);
		return $this->render();
	}
	
	public function group() {
		$list = ManageGroup::order('id')->select();
		foreach ($list as $g) {
			$g['add_time'] = date('Y-m-d H:i', $g['add_time']);
		}
		return $this->render([
			'list' => $list
		]);
	}
	
	//@管理员分组添加
	public function group_add() {
		return $this->group_edit();
	}
	//@管理员分组修改
	public function group_edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$name = $this->request->post('name');
			$menu = $this->request->post('menu/a', []);
			$permission = $this->request->post('permission/a', []);
			if (!$name) error('请输入名称');
			$menu = implode(',', $menu);
			$permission = implode('|', $permission);
			$data = compact('name', 'menu', 'permission');
			if ($id > 0) {
				\app\model\ManageGroup::update($data, ['id'=>$id]);
			} else {
				\app\model\ManageGroup::create($data);
			}
			$manage = \app\model\Manage::where('status', 1)->field('id')->select();
			foreach ($manage as $g) {
				Cache::delete('manage:menu:' . $g->id);
			}
			return success('tourl:manage/group', '提交成功');
		} else if ($id > 0) {
			$row = \app\model\ManageGroup::where('id', $id)->find();
			if (!$row) error('记录不存在');
			if ($row->power == 1 && $this->manageObj['super'] != 1) error('系统默认分组，不能修改', '/'.MODULE_NAME.'/manage/group');
			$permission = $this->_get_file_action($row->permission);
		} else {
			$row = t('manage_group');
			$permission = $this->_get_file_action();
		}
		$menus = explode(',', $row['menu']);
		$menu = $this->getAllMenus();
		return $this->render([
			'row' => $row,
			'menus' => $menus,
			'menu' => $menu,
			'permission' => $permission,
		], 'group_edit');
	}
	private function _get_file_action($permission = '') {
		$permission =  $permission ? explode('|', $permission) : [];
		$files = glob(app_path() . 'controller/*.php');
		$list = [];
		foreach ($files as $file) {
			$content = file_get_contents($file);
			preg_match('/(\/\/@|#)(.+)[\n\t\s]*class\s+(\w+)/', $content, $matcher);
			if (!$matcher) continue;
			$mark = strtolower($matcher[2]);
			$app = strtolower($matcher[3]);
			preg_match_all('/(\/\/@|#)(.+)[\n\t\s]*public function (\w+)/', $content, $matcher);
			if (!count($matcher[2])) continue;
			$titles = $matcher[2];
			$acts = $matcher[3];
			$items = [];
			foreach ($titles as $i => $title) {
				$act = $acts[$i];
				$key = $app.':'.$acts[$i];
				$items[] = [
					'title' => $title,
					'action' => $act,
					'key' => $key,
					'checked' => in_array($key, $permission) ? 1 : 0,
				];
			}
			if (isset($list[$mark])) $items = array_merge($list[$mark], $items);
			$list[$mark] = $items;
		}
		return $list;
	}
	
	//@管理员分组删除
	public function group_delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		ManageGroup::destroy(['id'=>$id]);
		$manage = \app\model\Manage::where('status', 1)->field('id')->select();
		foreach ($manage as $g) {
			Cache::delete('manage:menu:' . $g->id);
		}
		return success(null, '操作成功');
	}
	
	//@管理员分组强制删除
	public function group_force() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		ManageGroup::destroy(['id'=>$id]);
		\app\model\Manage::destroy(['group_id'=>$id]);
		return success(null, '操作成功');
	}
}
