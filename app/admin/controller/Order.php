<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\model\GoodsSpec;
use app\model\OrderGoods;
use app\model\OrderRefund;

//@order
class Order extends Core
{
	public function index() {
		$id = $this->request->get('id/d', 0);
		$export = $this->request->get('export/d', 0);
		$type = $this->request->get('type');
		$status = $this->request->get('status');
		$keyword = $this->request->get('keyword');
		$shop_id = $this->request->get('shop_id/d', 0);
		$pay_method = $this->request->get('pay_method');
		$integral_order = $this->request->get('integral_order/d', 0);
		$where = [
			['o.shown', '=', 1],
			['o.integral_order', '=', $integral_order],
		];
		if ($id > 0) $where[] = ['o.id', '=', $id];
		if ($keyword) $where[] = ['order_sn|o.name|o.mobile|o.address', 'like', "%$keyword%"];
		if ($type) $where[] = ['o.type', '=', $type];
		if (!is_null($status)) $where[] = ['o.status', 'in', $status];
		if ($shop_id > 0) $where[] = ['o.shop_id', '=', $shop_id];
		if ($pay_method) $where[] = ['o.pay_method', '=', $pay_method];
		$list = \app\model\Order::alias('o')
			->leftJoin('shop s', 'o.shop_id=s.id')
			->leftJoin('member m', 'o.member_id=m.id')
			->where($where)->order('o.id', 'DESC')->field("o.*, s.name as shop_name, m.name as member_name, '' as status_name")
			->paginate($this->paginateArr())->each(function($item) {
				$item->goods = OrderGoods::where('order_id', $item->id)->field('goods_id, goods_name, quantity, price, goods_pic')->select();
			});
		if ($export > 0) {
			set_time_limit(0);
			export_excel($list, [
				'id'=>'ID',
				'order_sn'=>'订单号',
				'member_name'=>'下单人',
				'shop_name'=>'门店',
				'name'=>'收货人',
				'mobile'=>'电话',
				'province'=>'省',
				'city'=>'市',
				'district'=>'区',
				'address'=>'地址',
				'add_time'=>'下单时间',
				'pay_time'=>'支付时间',
				'total_price'=>'订单总价',
				'pay_method'=>'支付方式',
				'status_name'=>'订单状态'
			]);
			exit;
		}
		$shops = \app\model\Shop::order('id')->field('id, name')->select();
		$status = \app\model\Order::getStatus();
		return $this->render([
			'list' => $list,
			'shops' => $shops,
			'status' => $status,
		]);
	}
	
	//@订单修改
	public function edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$total_price = $this->request->post('total_price/f', 0);
			$address = $this->request->post('address');
			$shipping_company = $this->request->post('shipping_company');
			$shipping_number = $this->request->post('shipping_number');
			$shipping_price = $this->request->post('shipping_price');
			$print = $this->request->post('print/d', 0);
			$status = $this->request->post('status/d', -1);
			$origin_status = $this->request->post('origin_status/d', -1);
			$price = $this->request->post('price');
			$goods_id = $this->request->post('goods_id');
			//获取发货时间，用来判断之前是否已经发货
			$order = \app\model\Order::whereId($id)->field('member_id, shipping_time, order_sn')->find();
			if (!$order) error('数据错误');
			//更改是否阅读
			$readed = 0;
			if ($status >= 0 && $status < 4 && $status != $origin_status) {
				$readed = 1;
			}
			$data = compact('address', 'total_price', 'status', 'shipping_company', 'shipping_number', 'shipping_price', 'readed');
			//发货时间
			if ($status == 2) {
				$data['shipping_time'] = time();
			} else if ($status == 3) {
				$data['shouhuo_time'] = time();
			}
			//更新订单信息
			\app\model\Order::update($data, ['id'=>$id]);
			if (is_array($price)) {
				foreach ($price as $i=>$v) {
					foreach ($goods_id as $k=>$g) {
						if ($i == $k) {
							OrderGoods::where(['order_id'=>$id, 'id'=>$g])->update(['price'=>$v]);
						}
					}
				}
			}
			//订单佣金转账到会员的佣金
			if ($status >= 3 && $id > 0) {
				//print_r($status);exit;
				//$this->commission_mod->turn_commission($id);
			}
			//退回佣金
			if ($status < 0) {
				//$this->commission_mod->cancel($id);
			}
			//发货状态发送物流消息通知
			if ($order->shipping_time == 0 && $shipping_company && $shipping_number) {
				//$this->send_message("您的订单{$shipping->order_sn}已经发货，物流信息请从订单详情查看。", $shipping->member_id, 'logistics');
				//$weixin_mod = m('weixin');
				//$weixin_mod->send_template($shipping->member_id, $id, 2);
			}
			if ($print > 0) {
				return success("tourl:order/print_order?id=$id");
			}
			return success('tourl:order/index', '提交成功');
		} else {
			$row = \app\model\Order::alias('o')
				->leftJoin('member m', 'o.member_id=m.id')
				->where('o.id', $id)->field('o.*, m.name as member_name, m.id as member_id')->find();
			if (!$row) error('订单不存在');
			if ($row->coupon_sn) $row->coupon_id = intval(\app\model\Coupon::where('sn', $row->coupon_sn)->value('coupon_id'));
			$goods = OrderGoods::alias('og')->leftJoin('shop s', 'og.shop_id=s.id')->where('og.order_id', $id)->field('og.*, s.name as shop_name')->select()->each(function($item) {
				if (is_numeric($item->spec)) {
					$item->spec = GoodsSpec::whereId($item->spec)->value('spec');
				}
			});
			return $this->render([
				'row' => $row,
				'goods' => $goods,
			]);
		}
	}
	
	//@订单删除
	public function delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Order::destroy(['id'=>$id]);
		return success('tourl:order/index', '操作成功');
	}
	
	//@发货单
	public function print_order() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		$row = \app\model\Order::alias('o')->leftJoin('member m', 'o.member_id=m.id')->where('o.id', $id)->order('o.id', 'DESC')->field('o.*, m.name as member_name')->find();
		$total = 0;
		$goods = OrderGoods::alias('og')
			->leftJoin('shop s', 'og.shop_id=s.id')
			->where('og.order_id', $id)->field('og.*, s.name as shop_name, 0 as sort')
			->select()->each(function($item, $index) use (&$total) {
				$item->sort = $index + 1;
				$total += $item->quantity;
			});
		return $this->render([
			'row' => $row,
			'goods' => $goods,
			'total' => $total,
		]);
	}
	
	//退货/款订单
	public function return_goods() {
		$id = $this->request->get('id/d', 0);
		$keyword = $this->request->get('keyword');
		$where = [];
		if ($id > 0) $where[] = ['r.id', '=', $id];
		if ($keyword) $where[] = ['m.name|m.mobile|o.order_sn', 'like', "%$keyword%"];
		$list = OrderRefund::alias('r')
			->leftJoin('order o', 'r.order_id=o.id')
			->leftJoin('member m', 'r.member_id=m.id')
			->where($where)
			->order(['r.id'=>'desc', 'r.status'=>'asc'])->field('r.*, o.order_sn, m.name, m.nick_name, m.mobile')
			->paginate($this->paginateArr())->each(function($item) {
				$name = $item->nick_name;
				if (!$name) $name = $item->name;
				if (!$name) $name = $item->mobile;
				$item->name = $name;
			});
		return $this->render([
			'list' => $list
		]);
	}
	
	//@退货/款订单审核
	public function return_edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$order_id = $this->request->post('order_id/d', 0);
			$reason = $this->request->post('audit_memo');
			$status = $this->request->post('status/d', 0);
			m('order')->returnGoods($order_id, $reason, $status);
			return success('tourl:order/return_goods', '提交成功');
		}
		$row = OrderRefund::alias('r')
			->leftJoin('order o', 'r.order_id=o.id')
			->leftJoin('member m', 'r.member_id=m.id')
			->where('r.id', $id)->field('r.*, o.order_sn, m.name, m.nick_name, m.mobile')->find();
		if ($row) {
			$name = $row->nick_name;
			if (!$name) $name = $row->name;
			if (!$name) $name = $row->mobile;
			$row->name = $name;
		}
		return $this->render([
			'row' => $row
		]);
	}
	
	//@退货/款订单删除
	public function return_delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		$order_id = $this->request->get('order_id/d', 0);
		OrderRefund::whereOr(['id'=>$id, 'order_id'=>$order_id])->delete();
		return success('tourl:order/return_goods', '操作成功');
	}
}
