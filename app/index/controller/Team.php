<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\model\BlindOrder;
use app\model\Config;
use app\model\MemberGrade;
use app\model\MemberGradeOrder;
use app\model\Member;
use app\model\Reward;
use app\model\MoneyLog;

class Team extends Core
{
	public function index() {
		$grade = MemberGrade::whereId($this->memberGradeId)->field('id, name')->find();
		$children = Member::getChildren($this->memberId);
		$total = BlindOrder::whereIn('member_id', $children)->where('status', '>', 0)->count();
		$price = BlindOrder::whereIn('member_id', $children)->where('status', '>', 0)->sum('price');
		$buy = MemberGrade::where([
			['status', '=', 1],
			['price', '>', 0],
		])->field('id, name, price')->order(['sort', 'id'])->find();
		return success([
			'wallet' => config('app.client_wallet'),
			'grade_id' => $grade->id,
			'grade_name' => $grade->name,
			'children' => count($children),
			'total' => $total,
			'price' => $price,
			'debug' => intval(Config::get('app_debug')),
			'buy' => [
				'id' => $buy->id,
				'name' => $buy->name,
				'price' => $buy->price,
			],
		]);
	}
	
	public function memo() {
		return success(Config::get("grade_memo$this->lang"));
	}
	
	public function team_list() {
		$page = $this->request->get('page/d', 0);
		$pagesize = $this->request->get('pagesize/d', 8);
		$children = Member::getChildren($this->memberId);
		$sql = BlindOrder::alias('bo')->whereColumn('m.id', 'bo.member_id')->where('status', '>', 0)->fetchSql()->count();
		$list = Member::alias('m')
			->leftJoin('member_wallet mw', 'm.id=mw.member_id')
			->leftJoin('member_grade mg', 'm.grade_id=mg.id')
			->whereIn('m.id', $children)
			->field('m.id, m.reg_time, mg.name as grade_name, mw.wallet, ('.$sql.') as count')->order('m.reg_time', 'desc')
			->page($page, $pagesize)->select()->each(function($item) {
				$item->reg_time = date('Y-m-d H:i', $item->reg_time);
			});
		return success($list);
	}
	
	public function buy() {
		$grade_id = $this->request->post('grade_id/d', 0);
		$hash = $this->request->post('hash');
		$balance = $this->request->post('balance/d', 0); //余额支付
		$verify = $this->request->post('verify/d', 0); //验证
		//if (!$hash) error(lang('index.submit.hash'));
		if ($grade_id <= 0) error(lang('data.error'));
		if ($grade_id <= $this->memberGradeId) error(lang('team.buy.grade.low'));
		
		$grade = MemberGrade::where(['id'=>$grade_id, 'status'=>1])->find();
		if (!$grade) error(lang('data.error'));
		if ($grade->price <= 0) error(lang('data.error'));
		if ($grade->num <= 0) error(lang('team.buy.num'));
		
		$member_id = $this->memberId;
		
		if ($verify == 0) {
			//预订单
			if ($balance == 1) {
				$member = Member::whereId($member_id)->field('money')->find();
				if ($member->money < $grade->price) error(lang('team.buy.balance'));
			}
			
			$order_id = MemberGradeOrder::insertGetId([
				'member_id' => $member_id,
				'grade_id' => $grade_id,
				'hash' => $hash,
				'price' => $grade->price,
				'is_balance' => $balance,
				'status' => 0,
				'add_time' => time()
			]);
			
			if ($balance == 1) {
				$member = Member::whereId($member_id)->field('money')->find();
				MoneyLog::payment($member_id, $grade->price, '余额支付等级提升', 'money', 'USDT', $order_id, 'member_grade_order');
				Member::whereId($member_id)->update(['money' => bcsub(strval($member->money), strval($grade->price), 8)]);
			}
			
			return success($order_id);
		}
		
		//验证订单
		$order_id = $verify;
		$where = [
			'id' => $order_id,
			'member_id' => $member_id,
			'grade_id' => $grade_id,
			'hash' => $hash,
			'price' => $grade->price,
			'status' => 0,
		];
		$res = MemberGradeOrder::where($where)->field('id')->find();
		if (!$res) error(lang('data.error'));
		$num = $grade->num - 1;
		if ($num < 0) $num = 0;
		MemberGrade::whereId($grade_id)->update(['num'=>$num]);
		MemberGradeOrder::where($where)->update(['status'=>1]);
		
		Member::whereId($member_id)->update(['grade_id'=>$grade_id]);
		
		//下线购买等级的上级奖励、祖辈升级
		Reward::gradeLevelUp($member_id, $grade, $order_id, 'member_grade_order');
		
		return success(null, lang('team.buy.success'));
	}
}
