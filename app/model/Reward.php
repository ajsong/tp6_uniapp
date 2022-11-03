<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Reward
{
	
	//奖励
	public static function grant($from_id, $price, $fromid = 0, $fromtable = '') {
		if (floatval($price) <= 0) return;
		$member = \app\model\Member::where($from_id)->field('parent_tree')->find();
		if (!$member || !$member->parent_tree) return;
		$tree = array_reverse(explode(',', $member->parent_tree));
		
		$conData[] = ['id'=>$fromid, 'returned'=>1];
		$memberData = [];
		$logData = [];
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
			$money = floatval(bcmul(strval($price), strval(($user->level_percent-$lastLevelPercent)/100), 8));
			$memberData[] = ['id'=>$member_id, 'money'=>bcadd(strval($user->money), strval($money), 8)];
			$logData[] = [
				'member_id' => $member_id,
				'number' => $money,
				'old' => $user->money,
				'new' => bcadd(strval($user->money), strval($money), 8),
				'remark' => '手续费收益'.$user->level_percent.'%',
				'type' => 10,
				'status' => 1,
				'money_type' => 0,
				'fromid' => $fromid,
				'fromtable' => $fromtable,
				'add_time' => time(),
			];
			$lastGradeId = $user->grade_id;
			$lastLevelPercent = $user->level_percent;
		}
		if (count($conData) > 0) {
			$model = new \app\model\ConStrategy();
			$model->saveAll($conData);
		}
		if (count($memberData) > 0) {
			$model = new \app\model\Member();
			$model->saveAll($memberData);
		}
		if (count($logData) > 0) {
			$model = new \app\model\MoneyLog();
			$model->saveAll($logData);
		}
	}
	
	//下线购买等级的上级奖励、祖辈升级
	public static function gradeLevelUp($from_id, $fromid = 0, $fromtable = '') {
		$member = Member::whereId($from_id)->field('grade_id, parent_id, parent_tree')->find();
		if (!$member || !$member->parent_id) return; //没有上级，断开
		
		//祖辈升级
		$memberData = [];
		$maxGradeId = 0; //最大等级
		$maxGrade = MemberGrade::where([
			['level_percent', '>', 0],
			['status', '=', 1],
		])->order(['sort', 'id'])->field('id')->select();
		foreach ($maxGrade as $g) $maxGradeId = $g->id;
		$gradeIds = [];
		$generalGradeId = 0; //普通等级
		$grades = MemberGrade::where('status', 1)->order(['sort', 'id'])->select();
		foreach ($grades as $g) {
			if ($g->level_percent <= 0) $generalGradeId = $g->id;
			$gradeIds[] = $g->id;
		}
		
		$tree = array_reverse(explode(',', $member->parent_tree));
		for ($i = 0; $i < count($tree); $i++) {
			$member_id = $tree[$i];
			$user = Member::whereId($member_id)->field('grade_id')->find();
			if ($user->grade_id >= $maxGradeId) continue; //已最高等级，不能再升级
			$index = array_search($user->grade_id, $gradeIds); //当前循环会员的等级id所在索引
			
			$count = \app\model\ConStrategy::alias('cs')
					->leftJoin('member m', 'm.id=cs.uid')
					->leftJoin(['con_transaction'=>'ct'], 'cs.id=ct.strategy_id')
					->whereRaw("CONCAT(',',m.parent_tree,',') LIKE '%,$member_id,%'")->where('side', 'BUY')->value('SUM(ct.entryPrice * cs.quantity)') / Config::get('leverage');
			if ($count >= $grades[$index+1]->team_total) {
				$memberData[] = [
					'id' => $member_id,
					'grade_id' => $gradeIds[$index+1],
				];
			}
		}
		if (count($memberData) > 0) {
			$memberModel = new Member();
			$memberModel->saveAll($memberData);
		}
	}
}
