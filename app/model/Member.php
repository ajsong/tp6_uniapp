<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Member extends Core
{
	//获取会员信息
	public static function getMemberInfo($id) {
		return self::whereId($id)->find();
	}
	
	//通过钱包地址获取会员信息
	public static function getMemberFromWallet($wallet) {
		return self::alias('m')->leftJoin('member_wallet mw', 'm.id=mw.member_id')->where('wallet', strtolower($wallet))->field('m.*, mw.wallet')->group('wallet')->find();
	}
	
	//获取所有子孙辈id
	public static function getChildren($id) {
		$children = [];
		$list = self::whereParentId($id)->field('id')->select();
		if ($list) {
			foreach ($list as $member) {
				$children[] = $member->id;
				$children = array_merge($children, self::getChildren($member->id));
			}
		}
		return $children;
	}
}
