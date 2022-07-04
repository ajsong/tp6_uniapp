<?php
declare (strict_types = 1);

namespace app\admin\controller;


use app\model\CouponGoods;
use app\model\CouponSn;
use app\model\GoodsCategory;
use app\model\Shop;

//@promote
class Coupon extends Core {
	public function index() {
		$id = $this->request->get('id/d', 0);
		$keyword = $this->request->get('keyword');
		$begin_time = $this->request->get('begin_time');
		$end_time = $this->request->get('end_time');
		$where = [];
		if ($id > 0) $where[] = ['id', '=', $id];
		if ($keyword) $where[] = ['name|coupon_money', 'like', "%$keyword%"];
		if ($begin_time) $where[] = ['begin_time', '>=', strtotime($begin_time)];
		if ($end_time) $where[] = ['end_time', '<=', strtotime($end_time)];
		$list = \app\model\Coupon::where($where)->order('id', 'desc')->field('*, 0 as sn')->paginate($this->paginateArr())->each(function($item) {
			$item->sn = CouponSn::where('coupon_id', $item->id)->count();
		});
		return $this->render([
			'list' => $list
		]);
	}
	
	//@优惠券添加
	public function add() {
		return $this->edit();
	}
	//@优惠券删除
	public function edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$name = $this->request->post('name');
			$shop_id = $this->request->post('shop_id/d', 0);
			$coupon_money = $this->request->post('coupon_money/f', 0);
			$coupon_discount = $this->request->post('coupon_discount/f', 0);
			$all_price = $this->request->post('all_price/d', 0);
			$position = $this->request->post('position/d', 1);
			$min_price = $this->request->post('min_price');
			$begin_time = $this->request->post('begin_time');
			$end_time = $this->request->post('end_time');
			$handy_time = $this->request->post('handy_time/d', 0);
			$quantity = $this->request->post('quantity');
			$auto_add = $this->request->post('auto_add/d', 0);
			$num_per_person = $this->request->post('num_per_person');
			$offline_use = $this->request->post('offline_use');
			$permit_goods = $this->request->post('permit_goods');
			$goods = $this->request->post('goods/a');
			$times = $this->request->post('times/d', 1);
			$day_times_handle = $this->request->post('day_times_handle/d', 1);
			$day_times = $this->request->post('day_times/d', 0);
			$status = $this->request->post('status/d', 0);
			$type = $this->request->post('type/d', 0);
			$unlimited = $this->request->post('unlimited/d', 0);
			$begin_time = strtotime($begin_time);
			$end_time = strtotime($end_time);
			if (!$begin_time) $begin_time = 0;
			if (!$end_time) $end_time = 0;
			if ($day_times_handle == 0) $day_times = 1;
			if ($permit_goods == 0) {
				$goods = '';
			} else if (!$goods) {
				$permit_goods = 0;
			}
			if ($begin_time < 0) $begin_time = 0;
			if ($end_time < 0) $end_time = 0;
			if ($end_time != 0 && $end_time <= time()) $status = -2;
			if ($all_price == 1) $coupon_money = $coupon_discount = -1;
			if ($coupon_money > 0) $coupon_discount = 0;
			if ($coupon_discount > 0) $coupon_money = 0;
			if ($times == 0) $times = 1;
			if ($times < -1) $times = -1;
			if ($unlimited == 1) $times = -1;
			$data = compact('name', 'shop_id', 'coupon_money', 'coupon_discount', 'position', 'min_price', 'begin_time', 'end_time', 'handy_time', 'quantity', 'auto_add',
				'num_per_person', 'status', 'offline_use', 'permit_goods', 'type', 'times', 'day_times');
			if ($id > 0) {
				\app\model\Coupon::update($data, ['id'=>$id]);
			} else {
				$data['add_time'] = time();
				$row = \app\model\Coupon::create($data);
				$id = $row->id;
			}
			//增加发行
			if ($auto_add == 0) {
				if (strlen($quantity)) {
					$quantity = intval($quantity);
					if ($status == 0) $status = 1;
					$data = [];
					for ($i = 0; $i < $quantity; $i++) {
						$data[] = [
							'coupon_id'=>$id, 'coupon_money'=>$coupon_money, 'add_time'=>time(), 'get_time'=>0, 'times'=>$times, 'status'=>$status,
							'sn'=>date('m') . date('d') . substr((string)time(), -3) . substr((string)microtime(), 2, 6) . rand(100, 999)
						];
					}
					CouponSn::insertAll($data);
				}
			}
			//相关商品
			CouponGoods::where('coupon_id', $id)->delete();
			if (is_array($goods)) {
				$data = [];
				foreach ($goods as $g) {
					$data[] = ['coupon_id'=>$id, 'goods_id'=>$g];
				}
				CouponGoods::insertAll($data);
			}
			return success('tourl:coupon/index', '提交成功');
		} else if ($id > 0) {
			$row = \app\model\Coupon::whereId($id)->find();
			$sn = CouponSn::where('coupon_id', $id)->count();
			if (intval(\app\model\Config::get('coupon_permit_category'))==1) {
				$goods = CouponGoods::alias('c')->leftJoin('goods_category g', 'c.goods_id=g.id')->where('c.coupon_id', $id)->field('g.id, g.name')->select()->each(function($item){
					$ids = GoodsCategory::get_category_parents_tree($item->id);
					$categories = [];
					$rs = GoodsCategory::whereIn('id', $ids)->field('id, name')->select();
					foreach ($rs as $r) {
						$categories[] = '<a href="'.url('category/edit', ['id'=>$r->id]).'" target="_blank">'.$r->name.'</a>';
					}
					$item->name = $categories;
				});
			} else {
				$goods = CouponGoods::alias('cg')->leftJoin('goods g', 'cg.goods_id=g.id')->where('cg.coupon_id', $id)->field('g.id, g.name, g.price, g.pic, cg.id as cid')->select();
			}
		} else {
			$row = t('coupon');
			$sn = 0;
			$goods = [];
		}
		$shop = Shop::whereStatus(1)->order('id', 'DESC')->field('id, name')->select();
		$coupon_permit_category = intval(\app\model\Config::get('coupon_permit_category'));
		return $this->render([
			'row' => $row,
			'sn' => $sn,
			'goods' => $goods,
			'shop' => $shop,
			'coupon_permit_category' => $coupon_permit_category,
		], 'edit');
	}
	
	//@优惠券删除
	public function delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Coupon::destroy(['id'=>$id]);
		CouponGoods::where('coupon_id', $id)->delete();
		CouponSn::where('coupon_id', $id)->delete();
		return success('tourl:coupon/index', '操作成功');
	}
	
	//sn
	public function sn() {
		$coupon_id = $this->request->get('coupon_id/d', 0);
		$id = $this->request->get('id/d', 0);
		$status = $this->request->get('status');
		$keyword = $this->request->get('keyword');
		$get_time1 = $this->request->get('get_time1');
		$get_time2 = $this->request->get('get_time2');
		$use_time1 = $this->request->get('use_time1');
		$use_time2 = $this->request->get('use_time2');
		$where = [];
		if ($id > 0) $where[] = ['s.id|m.id', '=', $id];
		if ($get_time1) $where[] = ['get_time', '>=', strtotime($get_time1)];
		if ($get_time2) $where[] = ['get_time', '<=', strtotime($get_time2)];
		if ($use_time1) $where[] = ['s.use_time', '>=', strtotime($use_time1)];
		if ($use_time2) $where[] = ['s.use_time', '<=', strtotime($use_time2)];
		if ($status) $where[] = ['s.status', '=', $status];
		if ($keyword) $where[] = ['s.sn|s.id|s.member_id|m.name', 'like', "%$keyword%"];
		$where[] = ['coupon_id', '=', $coupon_id];
		$list = CouponSn::alias('s')->leftJoin('member m', 's.member_id=m.id')->where($where)->order('s.id', 'DESC')->field('s.*, m.name, m.nick_name')->paginate($this->paginateArr());
		return $this->render([
			'list' => $list
		]);
	}
	
	//delete
	public function delete_sn() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		CouponSn::whereId($id)->delete();
		return success('tourl:coupon/sn', '操作成功');
	}
	
	//@优惠券发放
	public function grant() {
		if (IS_POST) {
			$all = $this->request->post('all/d', 1);
			$member_id = $this->request->post('member_id/d', 0);
			$coupon_id = $this->request->post('coupon_id/d', 0);
			$result = false;
			$model = new \app\model\Coupon();
			if ($all == 2) {
				$member = \app\model\Member::whereStatus(1)->field('id')->select();
				if ($member) {
					foreach ($member as $g) {
						$result = $model->send($coupon_id, $g->id);
					}
				}
			} else {
				$result = $model->send($coupon_id, $member_id);
			}
			$msg = '';
			switch ($result) {
				case 1:$msg = '发放成功';break;
				case 0:$msg = '发放失败';break;
				case -1:$msg = '已领取';break;
				case -2:$msg = '优惠券不存在';break;
			}
			return success('tourl:coupon/index', $msg);
		}
		$coupon = \app\model\Coupon::where([
			['status', '=', 1],
			['begin_time', '<=', time()],
		])->where(function($query) {
			$query->whereOr([
				['end_time', '>=', time()],
				['end_time', '=', 0],
				['handy_time', '>', 0],
			]);
		})->field('id, name, coupon_money')->select();
		$member = \app\model\Member::order('id')->field('id, name, nick_name')->select();
		return $this->render([
			'coupon' => $coupon,
			'member' => $member,
		]);
	}
}
