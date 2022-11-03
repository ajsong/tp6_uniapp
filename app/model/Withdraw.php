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
	public static function getTypes($code = '', $flip = false) {
		$list = [
			//1 => 'money',
			//2 => 'commission',
			3 => 'TRC20',
			//4 => 'ERC20',
			//5 => 'BEP20',
		];
		if ($flip) $list = array_flip($list);
		if (!strlen(strval($code))) return $list;
		return $list[$code] ?? $code;
	}
	
	//获取U盾商户支持的币种
	public static function getCoinTypes($code = '') {
		$model = new \app\model\Udun();
		$coins = $model->supportCoins();
		$list = [];
		foreach ($coins[0] as $coin) {
			$name = str_replace('USDT-', '', $coin['name']);
			$list[$name] = $coin;
		}
		if (!strlen(strval($code))) return $list;
		return $list[$code] ?? $code;
	}
}
