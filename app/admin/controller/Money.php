<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\model\BlindOrder;
use app\model\MemberGrade;
use app\model\RechargeLog;
use app\model\Reward;
use app\model\MoneyLog;
use app\model\Withdraw;

//@money
class Money extends Core {
 
	public function wallet() {
		$type = $this->request->get('type/d', 0);
		$money_type = $this->request->get('money_type/d', 0);
		$keyword = $this->request->get('keyword');
		$begin_time = $this->request->get('begin_time');
		$end_time = $this->request->get('end_time');
		$where = [];
		if ($type) $where[] = ['wl.type', '=', $type];
		if ($money_type) $where[] = ['wl.money_type', '=', $money_type];
		if ($keyword) $where[] = ['m.name|m.mobile|m.email|mw.wallet', 'like', "%$keyword%"];
		if ($begin_time) $where[] = ['wl.add_time', '>=', strtotime($begin_time)];
		if ($end_time) $where[] = ['wl.add_time', '<=', strtotime($end_time.' 23:59:59')];
		$list = MoneyLog::alias('wl')
			->leftJoin('member m', 'm.id=wl.member_id')
			->leftJoin('member_wallet mw', 'm.id=mw.member_id')
			->field("wl.*, m.name as member_name, m.status as member_status, m.mobile as member_mobile, m.email as member_email, mw.wallet, 0 as from_id, '' as from_wallet, 0 as from_price")
			->where($where)->order('wl.id desc')
			->paginate($this->paginateArr())->each(function($item) {
				if ($item->fromtable && $item->fromtable == 'blind_order') {
					$res = BlindOrder::alias('bo')
						->leftJoin('member_wallet mw', 'mw.member_id=bo.member_id')
						->where('bo.id', $item->fromid)->field('mw.member_id, mw.wallet, bo.price')->find();
					if ($res) {
						$item->from_id = $res->member_id;
						$item->from_wallet = $res->wallet;
						$item->from_price = $res->price;
					}
				}
			});
		return $this->render([
			'list' => $list,
			'types' => MoneyLog::getTypes(),
			'money_types' => MoneyLog::getMoneyType(),
		]);
	}
 
	public function withdraw() {
		$keyword = $this->request->get('keyword');
		$begin_time = $this->request->get('begin_time');
		$end_time = $this->request->get('end_time');
		$status = $this->request->get('status');
		$sortby = $this->request->get('sortby');
		$order = ['id'=>'desc'];
		if ($sortby) {
			$sort = explode(',', $sortby);
			$order = [$sort[0] => $sort[1]] + $order;
		}
		$where = [];
		if ($keyword) $where[] = ['m.name|m.mobile|m.email|mw.wallet|w.withdraw_wallet', 'like', "%$keyword%"];
		if ($begin_time) $where[] = ['w.add_time', '>=', strtotime($begin_time)];
		if ($end_time) $where[] = ['w.add_time', '<=', strtotime($end_time.' 23:59:59')];
		if ($status) $where[] = ['w.status', '=', $status];
		$list = Withdraw::where($where)
			->alias('w')
			->leftJoin('member m', 'w.member_id=m.id')
			->leftJoin('member_wallet mw', 'w.member_id=mw.member_id')
			->field('w.*, m.name as member_name, m.mobile as member_mobile, m.email as member_email, mw.wallet')
			->order($order)
			->paginate($this->paginateArr());
		return $this->render([
			'list' => $list
		]);
	}
	
	//@提现审核通过
	public function withdraw_resolve() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		$status = 1;
		$audit_adminid = $this->manageId;
		$audit_admin = $this->manageObj['name'];
		$audit_time = time();
		Withdraw::update(compact('status', 'audit_adminid', 'audit_admin', 'audit_time'), ['id'=>$id]);
		return success('tourl:money/withdraw', '操作成功');
	}
	
	//@提现审核拒绝
	public function withdraw_reject() {
		$id = $this->request->get('id/d', 0);
		$return_money = $this->request->get('return_money/d', 1); //返还金额
		if ($id <= 0) error('数据错误');
		$status = -1;
		$audit_memo = $this->request->post('audit_memo', '');
		$audit_adminid = $this->manageId;
		$audit_admin = $this->manageObj['name'];
		$audit_time = time();
		Withdraw::update(compact('status', 'audit_adminid', 'audit_admin', 'audit_time', 'audit_memo'), ['id'=>$id]);
		if ($return_money) {
			$withdraw = Withdraw::where('id', $id)->field('member_id, withdraw_money, withdraw_fee')->find();
			$money = \app\model\Member::where('id', $withdraw->member_id)->value('money');
			MoneyLog::recharge($withdraw->member_id, $withdraw->withdraw_money, '提现不通过退还金额', 'money', 'USDT', $id, 'withdraw', $withdraw->withdraw_fee);
			\app\model\Member::update(['money'=>bcadd(strval($money), strval($withdraw->withdraw_money), 8)], ['id'=>$withdraw->member_id]);
		}
		return success('tourl:money/withdraw', '操作成功');
	}
	
	public function recharge() {
		$keyword = $this->request->get('keyword');
		$status = $this->request->get('status');
		$where = [];
		if ($keyword) $where[] = ['m.name|m.mobile|m.email|mw.wallet|w.withdraw_wallet', 'like', "%$keyword%"];
		if ($status) $where[] = ['r.status', '=', $status];
		$list = RechargeLog::where($where)
			->alias('r')
			->leftJoin('member m', 'r.member_id=m.id')
			->leftJoin('member_wallet mw', 'r.member_id=mw.member_id')
			->field('r.*, m.name as member_name, m.mobile as member_mobile, m.email as member_email, mw.wallet')
			->order('id', 'desc')
			->paginate($this->paginateArr());
		return $this->render([
			'list' => $list
		]);
	}
	
	public function recharge_edit() {
		$id = $this->request->get('id/d', 0);
		$status = $this->request->get('status/d', 0);
		if ($id <= 0 || $status == 0) error('数据错误');
		$row = RechargeLog::where(['id'=>$id, 'status'=>0])->field('member_id, status')->find();
		if (!$row) error('数据错误');
		RechargeLog::update(['status'=>$status], ['id'=>$id]);
		if ($status == 1) {
			\app\model\Member::update(['grade_id'=>2], ['id'=>$row->member_id]);
			//下线购买等级的上级奖励、祖辈升级
			$grade = MemberGrade::whereId(2)->find();
			Reward::gradeLevelUp($row->member_id, $grade);
		}
		return success('tourl:money/recharge', '操作成功');
	}
	
	public function recharge_delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		RechargeLog::destroy(['id'=>$id]);
		return success('tourl:money/recharge', '操作成功');
	}
	
}
