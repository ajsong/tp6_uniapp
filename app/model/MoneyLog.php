<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class MoneyLog extends Core
{
	//获取所有类型
	public static function getTypes($code = '', $flip = false) {
		$list = [
			1 => '充值',
			2 => '提现',
			//3 => '积分',
			
			//4 => '直推奖',
			5 => '团队奖',
			//6 => '平级奖',
			//7 => '推荐奖',
			
			8 => '止盈收益',
			9 => '止损赔付',
			10 => '手续费收益',
			11 => '合约交易',
			12 => '现货交易',
			13 => '余额划转',
		];
		if ($flip) $list = array_flip($list);
		if (!strlen(strval($code))) return $list;
		return $list[$code] ?? $code;
	}
	
	//币种标识
	public static function getMoneyType($code = '', $flip = false) {
		$list = [
			0 => 'USDT',
			//1 => 'BTC',
			//2 => 'ETH',
			//3 => 'EXP',
			//4 => '积分',
		];
		Symbol::where('status', 1)->order(['sort', 'id'])->select()->each(function($item) use (&$list) {
			$list[intval($item->id)] = $item->getAttr('name');
		});
		if ($flip) $list = array_flip($list);
		if (!strlen(strval($code))) return $list;
		return $list[$code] ?? $code;
	}
	
	//字段单位
	public static function getFieldUnit($code = '', $flip = false) {
		$list = [
			'money' => 'USDT',
			//'integral' => '分',
		];
		if ($flip) $list = array_flip($list);
		if (!strlen(strval($code))) return $list;
		return $list[$code] ?? $code;
	}
	
	//充值币种
	public static function getCurrency($code = '', $flip = false) {
		$list = [
			'money' => '余额',
			'hy_money' => '合约余额',
			'xh_money' => '现货余额',
			//'usdt' => 'USDT',
		];
		if ($flip) $list = array_flip($list);
		if (!strlen(strval($code))) return $list;
		return $list[$code] ?? $code;
	}
	
	//金额正负标识
	public static function getStatus($status) {
		switch ($status) {
			case 0:
				//return '-';
				return '';
			case 1:
				return '+';
			default:
				return '';
		}
	}
	
	//充值
	public static function recharge($member_id, $number, $remark='会员充值', $field='money', $money_type='USDT', $fromid=0, $fromtable='recharge', $fee=0) {
		$member = Member::whereId($member_id)->field(['id', $field])->find();
		if (!$member) return false;
		return self::balance($member_id, $number, $member[$field], $remark, 1, $number>=0?1:0, $money_type, $fromid, $fromtable, $fee);
	}
	
	//提现
	public static function withdraw($member_id, $number, $remark='申请提现', $field='money', $money_type='USDT', $fromid=0, $fromtable='withdraw', $fee=0) {
		$member = Member::whereId($member_id)->field(['id', $field])->find();
		if (!$member) return false;
		return self::balance($member_id, $number, $member[$field], $remark, 2, 0, $money_type, $fromid, $fromtable, $fee);
	}
	
	//余额支付
	public static function payment($member_id, $number, $remark='余额支付', $field='money', $money_type='USDT', $fromid=0, $fromtable='') {
		$member = Member::whereId($member_id)->field(['id', $field])->find();
		if (!$member) return false;
		return self::balance($member_id, $number, $member[$field], $remark, 9, 0, $money_type, $fromid, $fromtable);
	}
	
	//余额变动，status:0支出,1收入
	public static function balance($member_id, $number, $old, $remark='余额变动', $type=1, $status=1, $money_type='USDT', $fromid=0, $fromtable='', $fee=0) {
		$bc = $status == 1 ? 'bcadd' : 'bcsub';
		$new = $bc(strval($old), strval(abs($number)), 8);
		if ($new < 0) return false; //已超过余额
		return self::add($member_id, $number, $old, $new, $remark, $type, $status, self::getMoneyType($money_type, true), $fromid, $fromtable, $fee);
	}
	
	//插入记录
	/*
	member_id	int(11) NULL [0]	会员id
	number	decimal(20,8) NULL [0.00000000]	变化的数量
	old	decimal(20,8) NULL [0.00000000]	原来的数值
	new	decimal(20,8) NULL [0.00000000]	变化后的数值
	remark	varchar(255) NULL	备注
	type	int(11) NULL [0]	类型，1充值，2提现，getTypes
	status	int(11) NULL [0]	1：收入 0：支出
	money_type	int(11) NULL [0]	钱包类型，1USDT，2BTC，3EXP，getMoneyType
	fromtable	varchar(255) NULL	所属表
	fromid	int(11) NULL [0]	所属表id
	fee	decimal(20,8) NULL [0.00000000]	手续费
	add_time	int(11) NULL [0]	创建时间
	*/
	public static function add($member_id, $number, $old, $new, $remark='', $type=1, $status=1, $money_type=0, $fromid=0, $fromtable='', $fee=0) {
		return self::insertGetId([
			'member_id'=>$member_id, 'number'=>$number, 'old'=>floatval($old), 'new'=>$new, 'remark'=>$remark, 'type'=>$type, 'status'=>$status, 'money_type'=>$money_type,
			'fromid'=>$fromid, 'fromtable'=>$fromtable, 'fee'=>$fee, 'add_time'=>time()
		]);
	}
}
