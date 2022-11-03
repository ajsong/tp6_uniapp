<?php
declare (strict_types = 1);

namespace app\admin\controller;

use think\facade\Cache;

class Menu extends Core
{
	public function index() {
		$list = \app\model\Menu::where('parent_id', 0)->order(['sort', 'id'])->select();
		foreach ($list as $g) {
			$children = \app\model\Menu::where('parent_id', $g->id)->order(['sort', 'id'])->select();
			$g->children = $children;
		}
		$parent = \app\model\Menu::where('parent_id', 0)->order(['sort', 'id'])->select();
		return $this->render([
			'list' => $list,
			'parent' => $parent,
		]);
	}
	
	public function add() {
		return $this->edit();
	}
	public function edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$name = $this->request->post('name', '');
			$parent_id = $this->request->post('parent_id/d', 0);
			$sort = $this->request->post('sort/d', 999);
			$path = $this->request->post('path', '');
			$icon = $this->request->post('icon', '');
			$data = compact('name', 'sort', 'path', 'icon');
			if ($parent_id < 0) {
				$level = 0;
			} else if ($parent_id == 0) {
				$data['parent_id'] = $parent_id;
				$level = 1;
			} else {
				$data['parent_id'] = $parent_id;
				$level = 2;
			}
			$data['level'] = $level;
			if ($id > 0) {
				\app\model\Menu::where('id', $id)->update($data);
			} else {
				\app\model\Menu::create($data);
			}
			$manage = \app\model\Manage::where('status', 1)->field('id')->select();
			foreach ($manage as $g) {
				Cache::delete('manage:menu:' . $g->id);
			}
			return success('tourl:menu/index', '提交成功');
		} else if ($id > 0) {
			$row = \app\model\Menu::where('id', $id)->find();
		} else {
			$row = t('menu');
		}
		$parent = \app\model\Menu::where('parent_id', 0)->order(['sort', 'id'])->select();
		return $this->render([
			'row' => $row,
			'parent' => $parent,
		], 'edit');
	}
	
	public function edit_all() {
		$param = $this->request->post('param/a');
		if (!is_array($param)) error('数据错误');
		foreach ($param as $g) {
			$data = ['name'=>$g['name'], 'sort'=>$g['sort'], 'path'=> $g['path']??'', 'icon'=>$g['icon']??''];
			if ($g['parent_id'] < 0) {
				$level = 0;
			} else if ($g['parent_id'] == 0) {
				$data['parent_id'] = $g['parent_id'];
				$level = 1;
			} else {
				$data['parent_id'] = $g['parent_id'];
				$level = 2;
			}
			$data['level'] = $level;
			if (isset($g['id']) && $g['id'] > 0) {
				\app\model\Menu::where('id', $g['id'])->update($data);
			//} else {
				//\app\model\Menu::create($data);
			}
		}
		$manage = \app\model\Manage::where('status', 1)->field('id')->select();
		foreach ($manage as $g) {
			Cache::delete('manage:menu:' . $g->id);
		}
		return success(null, '提交成功');
	}
	
	public function delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Menu::where('id', $id)->delete();
		\app\model\Menu::where('parent_id', $id)->delete();
		$manage = \app\model\Manage::where('status', 1)->field('id')->select();
		foreach ($manage as $g) {
			Cache::delete('manage:menu:' . $g->id);
		}
		return success(null, '操作成功');
	}
}
