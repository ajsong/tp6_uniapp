<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\model\MemberGrade;
use app\model\Recharge;
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
		if ($keyword) $where[] = ['m.name|m.mobile|m.email|m.wallet', 'like', "%$keyword%"];
		if ($begin_time) $where[] = ['wl.add_time', '>=', strtotime($begin_time)];
		if ($end_time) $where[] = ['wl.add_time', '<=', strtotime($end_time.' 23:59:59')];
		$list = MoneyLog::alias('wl')
			->leftJoin('member m', 'm.id=wl.member_id')
			->field("wl.*, m.name as member_name, m.status as member_status, m.mobile as member_mobile, m.email as member_email, m.wallet, 0 as from_id, '' as from_wallet, 0 as from_price")
			->where($where)->order('wl.id desc')
			->paginate($this->paginateArr());
		return $this->render([
			'list' => $list,
			'types' => MoneyLog::getTypes(),
			'money_types' => MoneyLog::getMoneyType(),
		]);
	}
	
	public function template_get_types() {
		$list = MoneyLog::getTypes('', true);
		$list = array_flip(array_reverse($list, true));
		return $this->render([
			'list' => $list
		]);
	}
	public function template_get_money_type() {
		$list = MoneyLog::getMoneyType('', true);
		$list = array_flip(array_reverse($list, true));
		return $this->render([
			'list' => $list
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
		if ($keyword) $where[] = ['m.name|m.mobile|m.email|m.wallet|w.withdraw_wallet', 'like', "%$keyword%"];
		if ($begin_time) $where[] = ['w.add_time', '>=', strtotime($begin_time)];
		if ($end_time) $where[] = ['w.add_time', '<=', strtotime($end_time.' 23:59:59')];
		if (is_numeric($status)) $where[] = ['w.status', '=', $status];
		$list = Withdraw::where($where)
			->alias('w')
			->leftJoin('member m', 'w.member_id=m.id')
			->field('w.*, m.name as member_name, m.mobile as member_mobile, m.email as member_email, m.wallet')
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
		$audit_admin = $this->manageObj->name;
		$audit_time = time();
		$model = new \app\model\Udun();
		$res = Withdraw::alias('w')->leftJoin('member m', 'm.id=w.member_id')->where('w.id', $id)->field('order_sn, withdraw_wallet, withdraw_money')->find();
		$res = $model->withdraw($res->order_sn, 195, 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', $res->withdraw_wallet, $res->withdraw_money);
		if ($res != 200) error($res);
		Withdraw::update(compact('audit_adminid', 'audit_admin', 'audit_time', 'status'), ['id'=>$id]);
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
		$audit_admin = $this->manageObj->name;
		$audit_time = time();
		Withdraw::update(compact('status', 'audit_adminid', 'audit_admin', 'audit_time', 'audit_memo'), ['id'=>$id]);
		if ($return_money) {
			$res = Withdraw::where('id', $id)->field('member_id, withdraw_money, withdraw_fee')->find();
			$money = \app\model\Member::where('id', $res->member_id)->value('money');
			MoneyLog::recharge($res->member_id, $res->withdraw_money, '提现不通过退还金额', 'money', 'USDT', $id, 'withdraw', $res->withdraw_fee);
			\app\model\Member::update(['money'=>bcadd(strval($money), strval($res->withdraw_money), 8)], ['id'=>$res->member_id]);
		}
		return success('tourl:money/withdraw', '操作成功');
	}
	
	public function recharge() {
		$keyword = $this->request->get('keyword');
		$status = $this->request->get('status');
		$where = [];
		if ($keyword) $where[] = ['m.name|m.mobile|m.email|m.wallet', 'like', "%$keyword%"];
		if (is_numeric($status)) $where[] = ['r.status', '=', $status];
		$list = Recharge::where($where)
			->alias('r')
			->leftJoin('member m', 'r.member_id=m.id')
			->field('r.*, m.name as member_name, m.mobile as member_mobile, m.email as member_email, m.wallet')
			->order('id', 'desc')
			->paginate($this->paginateArr());
		return $this->render([
			'list' => $list
		]);
	}
	
	//@充值审核
	public function recharge_edit() {
		$id = $this->request->get('id/d', 0);
		$status = $this->request->get('status/d', 0);
		if ($id <= 0 || $status == 0) error('数据错误');
		$row = Recharge::where(['id'=>$id, 'status'=>0])->find();
		if (!$row) error('数据错误');
		Recharge::update(['status'=>$status], ['id'=>$id]);
		if ($status == 1) {
			\app\model\MoneyLog::recharge($row->member_id, $row->num);
			\app\model\Member::whereId($row->member_id)->inc('money', floatval($row->num))->update();
		}
		return success('tourl:money/recharge', '操作成功');
	}
	
	public function recharge_delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		Recharge::destroy(['id'=>$id]);
		return success('tourl:money/recharge', '操作成功');
	}
	
}
