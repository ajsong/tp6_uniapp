<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\Base;

class Cron extends Base
{
	public function __construct() {
		parent::__construct();
		$cron_allow_ip = config('app.cron_allow_ip');
		if ($cron_allow_ip && !in_array('*', $cron_allow_ip) && !in_array($this->ip, $cron_allow_ip)) error404();
	}
	
	//每天返还盈利
	//00:01
	public function reward_profit_retore() {
		\app\model\SystemData::whereId(1)->update(['reward_profit'=>0]);
		\app\model\ConStrategy::where('status', 1)->whereColumn('profit_days', '<', 'profit_day')->update(['returned'=>0]);
		exit;
	}
	public function reward_profit() {
		sleep(3);
		$reward_profit = \app\model\SystemData::whereId(1)->value('reward_profit');
		if (intval($reward_profit) == 1) exit;
		$transData = [];
		$memberData = [];
		$logData = [];
		$list = \app\model\ConStrategy::where(['status'=>1, 'returned'=>0])->whereColumn('profit_days', '<', 'profit_day')
			->field('id, uid, rp, profit_day, profit_days')->limit(100)->select();
		if (!count($list)) {
			\app\model\SystemData::whereId(1)->update(['reward_profit'=>1]);
			exit;
		}
		foreach ($list as $item) {
			$transData[] = ['id'=>$item->id, 'profit_days'=>$item->profit_days+1, 'returned'=>1];
			if (floatval($item->rp) <= 0) continue;
			$money = bcdiv(strval($item->rp), strval($item->profit_day), 8);
			$member = \app\model\Member::where('id', $item->uid)->field('money')->find();
			if (!$member) continue;
			$new = bcadd(strval($member->money), strval($money), 8);
			$memberData[] = ['id'=>$item->uid, 'money'=>$new];
			$logData[] = [
				'member_id' => $item->uid,
				'number' => $money,
				'old' => $member->money,
				'new' => $new,
				'remark' => '止盈收益',
				'type' => 8,
				'status' => 1,
				'money_type' => 0,
				'fromid' => $item->id,
				'fromtable' => 'con_strategy',
				'add_time' => time(),
			];
		}
		if (count($transData) > 0) {
			$model = new \app\model\ConStrategy();
			$model->saveAll($transData);
		}
		if (count($memberData) > 0) {
			$model = new \app\model\Member();
			$model->saveAll($memberData);
		}
		if (count($logData) > 0) {
			$model = new \app\model\MoneyLog();
			$model->saveAll($logData);
		}
		exit;
	}
	
	//每天结算上级手续费返利
	public function reward_fee_retore() {
		\app\model\SystemData::whereId(1)->update(['reward_fee'=>0]);
		exit;
	}
	public function reward_fee() {
		$reward_fee = \app\model\SystemData::whereId(1)->value('reward_fee');
		if (intval($reward_fee) == 1) exit;
		$list = \app\model\ConStrategy::where('reward_fee', 0)->where('status', 'in', [1, 2, 3, 4, -1])->field('id, uid, fee')->limit(50)->select();
		if (count($list)) $this->_reward_fee($list, 'con_strategy');
		$list2 = \app\model\Transaction::where('reward_fee', 0)->where('status', 'in', [1, 3])->field('id, uid, fee')->limit(50)->select();
		if (count($list2)) $this->_reward_fee($list2, 'transaction');
		if (!count($list) && !count($list2)) {
			\app\model\SystemData::whereId(1)->update(['reward_fee'=>1]);
		}
		exit;
	}
	private function _reward_fee($list, $fromtable) {
		$transData = [];
		foreach ($list as $item) {
			$transData[] = ['id'=>$item->id, 'reward_fee'=>1];
			$member = \app\model\Member::where($item->uid)->field('parent_tree')->find();
			if (!$member || !$member->parent_tree) continue;
			$tree = array_reverse(explode(',', $member->parent_tree));
			
			$lastGradeId = -1; //循环的当前等级
			$lastLevelPercent = 0; //上次循环的级别奖励百分比
			for ($i = 0; $i < count($tree); $i++) {
				$member_id = $tree[$i];
				$user = \app\model\Member::alias('m')->leftJoin('member_grade mg', 'm.grade_id=mg.id')->where([
					['m.id', '=', $member_id],
					['m.status', '=', 1],
				])->field('m.money, m.grade_id, level_percent')->find();
				if (!$user) continue; //当前循环会员不存在，跳过
				if ($user->grade_id < $lastGradeId) { //上级比当前循环会员的等级低，跳过
					continue;
				}
				if ($user->grade_id == $lastGradeId) { //当前循环是平级且上次循环是平级，跳过
					continue;
				}
				if ($user->level_percent - $lastLevelPercent <= 0) continue;
				$money = floatval(bcmul(strval($item->fee), strval(($user->level_percent-$lastLevelPercent)/100), 8));
				$new = bcadd(strval($user->money), strval($money), 8);
				\app\model\Member::where('id', $member_id)->update(['money'=>$new]);
				\app\model\MoneyLog::insert([
					'member_id' => $member_id,
					'number' => $money,
					'old' => $user->money,
					'new' => $new,
					'remark' => '手续费收益'.$user->level_percent.'%',
					'type' => 10,
					'status' => 1,
					'money_type' => 0,
					'fromid' => $item->id,
					'fromtable' => $fromtable,
					'add_time' => time(),
				]);
				$lastGradeId = $user->grade_id;
				$lastLevelPercent = $user->level_percent;
			}
		}
		
		if (count($transData) > 0) {
			$model = m($fromtable);
			$model->saveAll($transData);
		}
	}
}
