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
	public static function getTypes($code = 0) {
		$list = [
			1 => '充值',
			2 => '提现',
			3 => '积分',
			
			4 => '直推奖',
			5 => '团队奖',
			6 => '平级奖',
			7 => '推荐奖',
			
			8 => '盲盒收益',
			9 => '余额支付',
		];
		if (!$code) return $list;
		return $list[$code] ?? $code;
	}
	
	//币种标识
	public static function getMoneyType($code = 0) {
		$list = [
			1 => 'USDT',
			//2 => 'BTC',
			//3 => 'EXP',
			4 => '积分',
		];
		if (!$code) return $list;
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
	
	//充值币种
	public static function getMoneyCurrency($code = '') {
		$list = [
			'money' => '余额',
			//'usdt' => 'USDT',
		];
		if (!$code) return $list;
		return $list[$code] ?? $code;
	}
	
	//充值
	public static function recharge($member_id, $number, $remark='会员充值', $field='money', $money_type='USDT', $fromid=0, $fromtable='', $fee=0) {
		$member = Member::whereId($member_id)->field(['id', $field])->find();
		if (!$member) return false;
		return self::balance($member_id, $member[$field], $number, $remark, 1, $number>0?1:0, $money_type, $fromid, $fromtable, $fee);
	}
	
	//提现
	public static function withdraw($member_id, $number, $remark='申请提现', $field='money', $money_type='USDT', $fromid=0, $fromtable='', $fee=0) {
		$member = Member::whereId($member_id)->field(['id', $field])->find();
		if (!$member) return false;
		return self::balance($member_id, $member[$field], $number, $remark, 2, 0, $money_type, $fromid, $fromtable, $fee);
	}
	
	//余额支付
	public static function payment($member_id, $number, $remark='余额支付', $field='money', $money_type='USDT', $fromid=0, $fromtable='') {
		$member = Member::whereId($member_id)->field(['id', $field])->find();
		if (!$member) return false;
		return self::balance($member_id, $member[$field], $number, $remark, 9, 0, $money_type, $fromid, $fromtable);
	}
	
	//余额变动，status:0支出,1收入
	public static function balance($member_id, $old, $number, $remark='余额变动', $type=1, $status=1, $money_type='USDT', $fromid=0, $fromtable='', $fee=0) {
		$bc = $status == 1 ? 'bcadd' : 'bcsub';
		$new = $bc(strval($old), strval($number), 8);
		if ($new < 0) return false; //已超过余额
		$moneyTypes = self::getMoneyType();
		$moneyTypes = array_flip($moneyTypes);
		return self::add($member_id, $number, $old, $new, $remark, $type, $status, $moneyTypes[strtoupper($money_type)], $fromid, $fromtable, $fee);
	}
	
	//插入记录
	/*
	member_id	int(11) NULL [0]	来源会员的id
	number	decimal(20,8) NULL [0.00000000]	变化的数量
	old	decimal(20,8) NULL [0.00000000]	原来的数值
	new	decimal(20,8) NULL [0.00000000]	变化后的数值
	remark	varchar(255) NULL	备注
	type	int(11) NULL [0]	类型 $this->getTypes
	status	int(11) NULL [0]	1：收入 0：支出
	money_type	int(11) NULL [0]	钱包类型 1usdt 2btc 3exp
	fromid	int(11) NULL [0]	记录id
	fromtable	varchar(255) NULL	所属表
	fee	decimal(20,8) NULL [0.00000000]	手续费
	*/
	public static function add($member_id, $number, $old, $new, $remark='', $type=1, $status=1, $money_type=1, $fromid=0, $fromtable='', $fee=0) {
		return self::insertGetId([
			'member_id'=>$member_id, 'number'=>$number, 'old'=>floatval($old), 'new'=>$new, 'remark'=>$remark, 'type'=>$type, 'status'=>$status, 'money_type'=>$money_type,
			'fromid'=>$fromid, 'fromtable'=>$fromtable, 'fee'=>$fee, 'add_time'=>time()
		]);
	}
}
