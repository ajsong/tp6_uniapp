<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Order extends Core
{
	//状态
	public static function getStatus($status = '') {
		$list = [
			'0' => '未支付',
			'1' => '已支付',
			'2' => '已发货',
			'3' => '交易成功',
			'4' => '已评价',
			'-1' => '已取消',
			'-2' => '已退款',
			'-3' => '已退货',
		];
		if (is_string($status) && !$status) return $list;
		return $list[strval($status)] ?? $status;
	}
	
	//退货/款订单审核
	//1:退款, 2:退货
	public function returnGoods($order_id = 0, $reason = '', $status = 1) {
		if ($order_id <= 0) error('缺少订单ID');
		//判断是退款还是退货
		$type = OrderRefund::where('order_id', $order_id)->order('id', 'DESC')->value('type');
		$order = self::whereId($order_id)->find();
		
		if ($status == 1) {
			$refund = OrderRefund::where('order_id', $order_id)->order('id', 'DESC')->field('id, out_refund_no')->find();
			$this->_refund($order, $refund);
			if ($type == 1) {
				if (!strlen($reason)) $reason = '已退款';
			} else {
				self::whereId($order_id)->update(['status'=>-3]);
				$shop_id = self::whereId($order_id)->value('shop_id');
				if ($shop_id) {
					$row = Shop::whereId($shop_id)->field('return_province, return_city, return_district, return_address, return_name, return_mobile')->find();
					if (!strlen($reason)) $reason = '退货地址：'.$row->return_province.$row->return_city.$row->return_district.$row->return_address.'，联系人：'.$row->return_name.'，电话：'.$row->return_mobile;
				} else {
					if (!strlen($reason)) $reason = '退货地址：'.Config::get('order_refund_address');
				}
			}
			//扣减退货退款后会员的佣金
			$this->_reduce_order_commission($order_id);
		}
		OrderRefund::where('order_id', $order_id)->update(['status'=>$status, 'audit_memo'=>$reason, 'audit_time'=>time()]);
		
		/*//发送短信、推送
		$sms = [];
		$member_id = $order->member_id;
		if ($status == 1) {
			if ($type == 1) {
				$notify = "您的订单{$order->order_sn}退款成功，退款金额为{$order->total_price}元";
				$sms = [$order->order_sn, "{$order->total_price}元"];
				$template_id = 221218;
			} else {
				$notify = "您的订单{$order->order_sn}退货成功";
				$sms = [$order->order_sn];
				$template_id = 221222;
			}
		} else {
			if ($type==1) {
				$notify = '您申请的退款请求失败，请联系卖家';
				$template_id = 221225;
			} else {
				$notify = '您申请的退货请求失败，请联系卖家';
				$template_id = 221226;
			}
		}
		$this->notification(array(
			'message' => $notify,
			'members' => $member_id,
			'sms' => $sms,
			'template_id' => $template_id
		));*/
		
		/*if ($status == 1 && $type == 1) {
			switch ($order->pay_method) {
				case 'wxpay':
				case 'wxpay_h5':
				case 'wxpay_mini':
				case 'alipay':
					//header('Location:?app=order&act=return_goods');
					break;
				case 'yue':
					//header('Location:?app=order&act=return_goods');
					//success('订单已退款，请在会员中心查看您的余额是否已到账');
					break;
			}
		}*/
	}
	
	//第三方退款
	private function _refund($order, $refund) {
		if ($order->pay_method == 'wxpay' || $order->pay_method == 'wxpay_h5' || $order->pay_method == 'wxpay_mini') {
			$pay_method = $order->pay_method;
			if ($order->is_mini) $pay_method = 'wxpay_mini';
			//$pay = p('pay');
			//$pay->refund($order->order_sn, $order->total_price, $refund->out_refund_no, $pay_method, $order->trade_no);
		} else if ($order->pay_method == 'alipay') {
			//$pay = p('pay', 'alipay');
			//$pay->refund($order->order_sn, $order->total_price, $refund->out_refund_no, $order->pay_method, $order->trade_no);
		} else if ($order->pay_method == 'yue') {
			$member = Member::whereId($order->member_id)->field('money')->find();
			if ($member) {
				$money = bcadd(strval($member->money), strval($order->total_price), 8);
				Member::whereId($order->member_id)->update(['money'=>$money]);
			}
			OrderRefund::where($refund->id)->update(['status'=>1]);
			Order::where($order->id)->update(['status'=>-2]);
		}
	}
	
	//退货退款成功后，扣减会员的佣金
	private function _reduce_order_commission($order_id) {
		MemberCommission::where(['parent_id'=>$order_id, 'type'=>1, 'status'=>1])->select()->each(function($item) {
			$money = $item->commission;
			if ($money) {
				//处理店铺佣金
				$order = Order::whereId($item->parent_id)->field('shop_id')->find();
				if ($order) {
					$shop = Shop::whereId($order->shop_id)->field('total_income, can_withdraw_money, member_id')->find();
					if ($shop) {
						if ($shop->total_income >= $money) {
							$shop->total_income = $shop->total_income - $money;
						} else {
							$shop->total_income = 0;
						}
						if ($shop->can_withdraw_money >= $money) {
							$shop->can_withdraw_money = $shop->can_withdraw_money - $money;
						} else {
							$shop->can_withdraw_money = 0;
						}
						Shop::whereId($order->shop_id)->update(['total_income'=>$shop->total_income, 'can_withdraw_money'=>$shop->can_withdraw_money]);
						//处理会员佣金
						$member = Member::where($shop->member_id)->field('commission')->find();
						if ($member) {
							if ($member->commission >= $money) {
								$member->commission = bcsub(strval($member->commission), strval($member->commission), 8);
							} else {
								$member->commission = 0;
							}
							Member::whereId($shop->member_id)->update(['commission'=>$member->commission]);
						}
					}
				}
			}
		});
		MemberCommission::where(['parent_id'=>$order_id, 'type'=>1])->update(['status'=>-1]);
	}
}
