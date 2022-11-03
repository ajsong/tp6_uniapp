<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Member extends Core
{
	//通过ID获取会员信息
	public static function fromId($id) {
		return self::whereId($id)->find();
	}
	
	//通过Token获取会员信息
	public static function fromToken($token) {
		return self::whereToken($token)->field('id, name, token, nick_name, mobile, email, invite_code, money, hy_money, xh_money, avatar, grade_id')->find();
	}
	
	//通过Wallet获取会员信息
	public static function fromWallet($wallet) {
		return self::whereWallet(strtolower($wallet))->find();
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
