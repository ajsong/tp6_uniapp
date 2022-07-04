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
}
