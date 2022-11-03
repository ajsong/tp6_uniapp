<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\Base;

class Core extends Base {
	
	public $manageId = 0;
	public $manageObj;
	
	public function __construct() {
		parent::__construct();
		
		//判断登录
		$this->check_login();
		
		//判断权限
		if (!$this->check_permission($this->app, $this->act)) {
			//error('没有操作权限，请联系超级管理员');
			$this->redirect('/' . MODULE_NAME);
		}
		
		if ($this->app != 'index' && $this->app != 'upload' && $this->manageObj->super != 1) {
			if (\app\model\Config::get('manage_log') == 1) {
				\app\model\ManageLog::whereTime('add_time', '<', date('Y-m-d', strtotime('-7 day')))->delete();
				//if (!IS_POST) \app\model\ManageLog::add();
			}
		}
	}
	
	private function _check_login(): bool {
		$manage = session('manage');
		if ( $manage ) {
			$this->manageId = $manage->id;
			$this->manageObj = $manage;
			return true;
		} else if ( cookie('?manage_token') ) {
			$manage = \app\model\Manage::whereToken(cookie('manage_token'))->find();
			if ($manage) {
				unset($manage->password, $manage->salt);
				$this->manageId = $manage->id;
				$this->manageObj = $manage;
				session('manage', $manage);
				return true;
			}
		}
		return false;
	}
	
	//判断是否登录
	public function check_login(): bool {
		if (!$this->_check_login()) {
			session('manage_gourl', $this->request->url());
			if (IS_AJAX) {
				error('登录失效', -2);
			} else {
				$this->redirect('/' . MODULE_NAME . '/login');
			}
			return false;
		}
		return true;
	}
	
	private function menu() {
		$cacheKey = 'manage:menu:' . $this->manageId;
		return \think\facade\Cache::remember($cacheKey, function() {
			$menuIds = $this->getMenuIds();
			if (in_array('all', $menuIds)) {
				$menu = $this->getAllMenus();
			} else {
				$menu = $this->getAllMenus(0, $menuIds);
			}
			return $menu;
		});
	}
	
	public function getMenuIds(): array {
		$group = \app\model\ManageGroup::whereId($this->manageObj->group_id)->field('menu')->find();
		if (!$group) return [0];
		if ($group->menu == 'all') {
			$menuIds = ['all'];
		} else {
			$menuIds = explode(',', $group->menu);
		}
		//去重
		//$menuIds = array_unique($menuIds); //效率较低
		$menuIds = array_flip($menuIds);
		return array_flip($menuIds); //要array_flip两次才正确
	}
	
	public function getAllMenus($parent_id = 0, $menuIds = []) {
		$menu = \app\model\Menu::where([
			['parent_id', '=', $parent_id],
			['level', '>', 0],
		]);
		if ($menuIds) $menu->whereIn('id', $menuIds);
		return $menu->order(['sort', 'id'])->select()->each(function($item) use ($menuIds) {
			$children = \app\model\Menu::where([
				['parent_id', '=', $item->id],
				['level', '>', 0],
			]);
			if ($menuIds) $children->whereIn('id', $menuIds);
			$count = $children->count();
			if ($count > 0) {
				$item->children = $this->getAllMenus($item->id, $menuIds);
			}
		});
	}
	
	//检查权限
	public function check_permission($app, $act): bool {
		if ($this->manageObj->super == 1) return true;
		if ($app == 'index') return true;
		if ($app == 'upload') return true;
		if ($app == 'menu' || ($app == 'config' && $act == 'log')) return false;
		if (preg_match('/^template_/', $act)) return true;
		$menuIds = $this->getMenuIds();
		if (in_array('all', $menuIds)) return true;
		$path = \app\model\Menu::getRoutePath();
		$power = \app\model\Menu::wherePath('/'.$path)->where('level', '>', 0)->field('id')->find();
		if ($power && in_array($power->id, $menuIds)) return true;
		$power = \app\model\ManageGroup::whereId($this->manageObj->group_id)->whereRaw("CONCAT('|',permission,'|') LIKE '%|$app:$act|%'")->find();
		if ($power) return true;
		return false;
	}
	
	//检查权限，模板用
	public static function permission($app, $act): bool {
		$manage = session('manage');
		if ($manage->super == 1) return true;
		if (preg_match('/^template_/', $act)) return true;
		$group = \app\model\ManageGroup::whereId($manage->group_id)->field('menu')->find();
		if (!$group) return false;
		$menuIds = $group->menu == 'all' ? ['all'] : explode(',', $group->menu);
		if (in_array('all', $menuIds)) return true;
		$power = \app\model\ManageGroup::whereId($manage->group_id)->whereRaw("CONCAT('|',permission,'|') LIKE '%|$app:$act|%'")->find();
		if ($power) return true;
		return false;
	}
	
	//输出模板
	public function render($data = [], $template_file = '') {
		if (IS_AJAX || $this->request->get('output') == 'view') {
			$param = [];
			$full = $this->request->get('full');
			if ($full || $this->request->get('output') == 'view') {
				$param['manage'] = $this->manageObj;
				$param['leftMenu'] = $this->menu();
			}
			foreach ($data as $key => $obj) {
				if ($obj instanceof \think\paginator\driver\Bootstrap) {
					$arr = $obj->toArray();
					$param[$key] = $arr['data'];
					$paginate = $this->request->get('paginate');
					if ($paginate) {
						$param[$paginate] = [
							'total' => $arr['total'] ?: 0,
							'per_page' => $arr['per_page'] ?: 0,
							'current_page' => $arr['current_page'] ?: 0,
							'last_page' => $arr['last_page'] ?: 0
						];
					}
				}
				else if ($obj instanceof \think\model\Collection) $param[$key] = $obj->toArray();
				else $param[$key] = $obj;
			}
			return success($param);
		}
		if (is_null($data)) $data = [];
		$data['manage'] = $this->manageObj;
		$data['leftMenu'] = $this->menu();
		if (strlen($template_file)) {
			if (preg_match('/\.html$/', $template_file)) { //.html结尾自动获取完整路径
				if (strpos(str_replace('\\', '/', $template_file), '/') === false) {
					$template_file = dirname(__FILE__, 2) . '/view/' . CONTROLLER_NAME . '/' . $template_file;
				}
			} else if (!preg_match('/<[^>]+>/', $template_file)) {
				$template_file .= '.view'; //.view结尾自动在前面追加当前Controller
			}
		} else {
			$template_file = 'success';
		}
		return success(null, $template_file, $data);
	}
	
}
