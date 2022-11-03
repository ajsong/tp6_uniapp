<?php
declare (strict_types = 1);

namespace app\model;

use think\facade\Cache;
use think\Model;

/**
 * @mixin \think\Model
 */
class Config extends Core
{
	public static function get($key = '') {
		$res = Cache::remember('config:data', function() {
			$ret = [];
			$list = self::order('id')->field('key, value')->select();
			if ($list) {
				foreach ($list as $g) {
					$ret[$g['key']] = $g['value'];
				}
			}
			return json_encode($ret);
		});
		$res = json_decode($res, true);
		return strlen($key) ? ($res[$key]??'') : $res;
	}
	
	public static function setValue($key, $value, $step = 0) {
		if (!strlen($key)) error('MISSING KEY');
		$res = self::where('key', $key)->find();
		if (!$res) error("MISSING $key");
		if ($step != 0) $value = $value + $step;
		self::where('key', $key)->update(compact('value'));
		Cache::delete('config:data');
		return self::get();
	}
}
