<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Banner extends Core
{
	//获取广告, channel: 0全渠道，1苹果+安卓，2苹果，3安卓，4微信，5web
	public static function get($position, $language = 1, $channel = 0) {
		if ($language == 1) $language = '';
		$where = [
			['position', '=', $position],
			['status', '=', 1],
		];
		if ($channel != 0) $where[] = ['channel', '=', $channel];
		$banner = self::where($where)->where(function($query) {
			$query->whereOr([
				['begin_time', '=', 0],
				['begin_time', '<=', time()],
			]);
		})->where(function($query) {
			$query->whereOr([
				['end_time', '=', 0],
				['end_time', '>=', time()],
			]);
		})->order(['sort', 'id'])->select()->toArray();
		$list = add_domain_deep($banner, "pic$language");
		if ($language != 1) {
			foreach ($list as &$item) $item['pic'] = $item["pic$language"];
		}
		return $list;
	}
	
	public function getType($code = '') {
		$list = [
			['code' => 'html5', 'memo' => '网页(html5)', 'tip' => '广告内容请填写完整的网页链接，需带有http://或https://'],
			//['code' => 'goods', 'memo' => '商品(goods)', 'tip' => '广告内容请填写商品ID，<a href=\"'.url('goods/index').'\" target=\"_blank\">查找ID</a>'],
			['code' => 'article', 'memo' => '文章(article)', 'tip' => '广告内容请填写文章ID，<a href=\"'.url('article/index').'\" target=\"_blank\">查找ID</a>'],
			//['code' => 'type', 'memo' => '一级分类(type)', 'tip' => '广告内容请填写一级分类ID，<a href=\"'.url('article/category').'\" target=\"_blank\">查找ID</a>'],
			//['code' => 'subtype', 'memo' => '二级分类(subtype)', 'tip' => '广告内容请填写二级分类ID，<a href=\"'.url('article/category').'\" target=\"_blank\">查找ID</a>'],
			//['code' => 'brand', 'memo' => '品牌(brand)', 'tip' => '广告内容请填写品牌ID，<a href=\"'.url('band/index').'\" target=\"_blank\">查找ID</a>'],
			//['code' => 'coupon', 'memo' => '优惠劵(coupon)', 'tip' => '广告内容请填写优惠劵ID，<a href=\"'.url('coupon/index').'\" target=\"_blank\">查找ID</a>'],
			//['code' => 'recharge', 'memo' => '充值(recharge)', 'tip' => '广告内容请填写充值ID，<a href=\"'.url('recharge/index').'\" target=\"_blank\">查找ID</a>'],
			//['code' => 'register', 'memo' => '注册有礼(register)', 'tip' => '广告内容请填写 /index/passport/register'],
		];
		if (!$code) return $list;
		$memo = '';
		foreach ($list as $row) {
			if ($row['code'] == $code) {
				$memo = $row['memo'];
				break;
			}
		}
		return $memo;
	}
	
	public function getPosition($code = '') {
		$list = [
			['code' => 'flash', 'memo' => '首页轮播图(flash)'],
			//['code' => 'index', 'memo' => '精选专题(index)'],
			//['code' => 'brand', 'memo' => '品牌(brand)'],
			//['code' => 'article', 'memo' => '文章(article)'],
			//['code' => 'integral', 'memo' => '积分商城(integral)'],
		];
		if (!$code) return $list;
		$memo = '';
		foreach ($list as $row) {
			if ($row['code'] == $code) {
				$memo = $row['memo'];
				break;
			}
		}
		return $memo;
	}

}
