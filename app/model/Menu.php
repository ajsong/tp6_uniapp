<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Menu extends Core
{
	//获取下级总数
	public function getChildTotal() {
		if ($this->level == 1) {
			return self::where(['parent_id'=>$this->id, 'level'=>2])->count() + 1;
		}
		return 1;
	}
	
	//获取当前路径
	public function checkActive() {
		if (!strlen($this->path)) return false;
		$trueUrl = self::getRoutePath();
		$trueUrl = explode('/', trim($trueUrl, '/'));
		$controller = $trueUrl[1];
		$trueUrl = $trueUrl[0] . '/' . $controller;
		$url = explode('/', trim($this->path, '/'));
		$url = $url[0] . '/' . $url[1];
		$isActive = $trueUrl == strtolower($url);
		if ($isActive) return true;
		$paths = [];
		self::where('parent_id', $this->parent_id)->field('path')->select()->each(function($item) use (&$paths) {
			$url = explode('/', trim($item->path, '/'));
			$paths[] = $url[1];
		});
		return in_array($controller, $paths);
	}
	
	public static function getRoutePath() {
		$actions = trim(request()->url(), '/');
		$arr = explode('/', $actions);
		if (count($arr) == 1) {
			$path = $actions . '/index/index';
		} else if (count($arr) == 2) {
			$path = $actions . '/index';
		} else {
			$path = $actions;
		}
		return strtolower($path);
	}
}
