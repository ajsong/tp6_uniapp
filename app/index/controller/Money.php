<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\model\Config;
use app\model\RechargeLog;
use app\model\MoneyLog;
use app\model\Withdraw;

class Money extends Core
{
	public function withdraw() {
		$money = \app\model\Member::whereId($this->memberId)->value('money');
		$fee = Config::get('withdraw_fee');
		$least = floatval(Config::get('withdraw_least'));
		if (IS_POST) {
			$type = $this->request->post('type/d', 0);
			$num = $this->request->post('num/f', 0); //含手续费
			$wallet = $this->request->post('wallet');
			if ($type <= 0 || !$wallet || $num <= 0) error(lang('data.error'));
			if ($num < $least) error(lang('member.withdraw.least').$least);
			if ($num > $money) error(lang('member.withdraw.most').$money);
			if (preg_match('/%$/', $fee)) $fee = bcmul(strval($num), strval(floatval(substr($fee, 0, strlen($fee)-1))/100), 8);
			$id = Withdraw::insertGetId([
				'member_id' => $this->memberId,
				'order_sn' => generate_sn(),
				'type' => $type,
				'withdraw_wallet' => $wallet,
				'withdraw_money' => $num,
				'withdraw_fee' => $fee,
				'ip' => $this->ip,
				'add_time' => time(),
			]);
			MoneyLog::withdraw($this->memberId, $num, '申请提现', 'money', 'USDT', $id, 'withdraw', $fee);
			\app\model\Member::whereId($this->memberId)->update(['money'=>bcsub(strval($money), strval($num), 8)]);
		}
		return success([
			'price' => floatval($money),
			'fee' => $fee,
			'least' => $least,
			'memo' => Config::get("withdraw_memo$this->lang"),
		]);
	}
	
	public function wallet() {
		$page = $this->request->get('page/d', 0);
		$pagesize = $this->request->get('pagesize/d', 15);
		$type = $this->request->get('type');
		$reward = $this->request->get('reward/d', 0);
		$where = [];
		$where[] = ['member_id', '=', $this->memberId];
		$where[] = ['type', 'in', $reward>0 ? [4, 5, 6, 7, 8] : ($type ?: [1, 2, 4, 5, 6, 7, 8, 9])];
		$list = MoneyLog::alias('wl')->field('id, remark, number, status, add_time')
			->where($where)->order('id', 'desc')->page($page, $pagesize)->select()->each(function($item) {
				$item->remark = lang($item->remark);
				$item->number = str_replace('-', '', $item->number);
				$item->add_time = date('Y-m-d H:i', $item->add_time);
			});
		return success($list);
	}
	
	public function memo() {
		return success(Config::get("integral_memo$this->lang"));
	}
	
	public function recharge() {
		if (IS_POST) {
			$num = $this->request->post('num/f', 0);
			$pic = $this->request->post('pic');
			if ($num <= 0 || !$pic) error(lang('member.recharge.error'));
			$member_id = $this->memberId;
			$add_time = time();
			RechargeLog::insert(compact('member_id', 'num', 'pic', 'add_time'));
			return success();
		}
		return success(Config::get("recharge_memo$this->lang"));
	}
	
	public function recharge_list() {
		$page = $this->request->get('page/d', 0);
		$pagesize = $this->request->get('pagesize/d', 8);
		$list = RechargeLog::where('member_id', $this->memberId)->order('id', 'desc')->page($page, $pagesize)->select()->each(function($item) {
			$item->pic = add_domain($item->pic);
			$item->add_time = date('Y-m-d H:i', $item->add_time);
		});
		return success($list);
	}
	
	public function upload() {
		$pic = upload_file('pic', 'recharge');
		return success($pic);
	}
}
