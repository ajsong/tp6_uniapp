<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Withdraw extends Core
{
	//获取所有类型
	public static function getTypes($code = 0) {
		$list = [
			1 => 'USDT-TRC20',
			2 => 'USDT-ERC20',
			3 => 'USDT-BEP20',
		];
		if (!$code) return $list;
		return $list[$code] ?? '';
	}
}
