<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Reward extends Core
{
	//直推奖励名称
	public static function shareName($level = 0) {
		$list = [
			1 => '路人奖',
			2 => '骑尉奖',
			3 => '男爵奖',
			4 => '子爵奖',
			5 => '伯爵奖',
			6 => '侯爵奖',
			7 => '公爵奖',
			8 => '亲王奖',
		];
		if (!$level) return $list;
		return $list[$level] ?? $level;
	}
	
	//盲盒下单奖励
	public static function grant($from_id, $price, $fromid, $fromtable) {
		$member = Member::whereId($from_id)->field('parent_tree, grade_id')->find();
		if (!$member->parent_tree) return;
		$tree = array_reverse(explode(',', $member->parent_tree));
		
		$blind = \app\model\Blind::getBoxData();
		$shares = [];
		foreach ($blind['shares'] as $percent) {
			$shares[] = $percent;
		}
		
		//直推奖(分享收益)
		$memberMoneyData = [];
		$walletLogData = [];
		for ($i = 0; $i < count($tree); $i++) {
			if ($i >= count($shares)) break;
			$member_id = $tree[$i];
			$user = Member::alias('m')->leftJoin('blind_order bo', 'bo.member_id=m.id')
				->where(['m.id'=>$member_id, 'bo.status'=>1])
				->field('m.money')->find();
			if (!$user) continue; //当前循环会员不存在或没有进行中的盲盒订单，跳过
			if (floatval($shares[$i]) <= 0) continue;
			$money = floatval(bcmul(strval($price), strval($shares[$i]/100), 8));
			$memberMoneyData[] = ['id'=>$member_id, 'money'=>bcadd(strval($user->money), strval($money), 8)];
			$walletLogData[] = [
				'member_id' => $member_id,
				'number' => $money,
				'old' => $user->money,
				'new' => bcadd(strval($user->money), strval($money), 8),
				'remark' => self::shareName($i+1),
				'type' => 4,
				'status' => 1,
				'money_type' => 1,
				'fromid' => $fromid,
				'fromtable' => $fromtable,
				'add_time' => time(),
			];
		}
		if (count($memberMoneyData) > 0) {
			$memberModel = new Member();
			$memberModel->saveAll($memberMoneyData);
		}
		if (count($walletLogData) > 0) {
			$walletLogModel = new MoneyLog();
			$walletLogModel->saveAll($walletLogData);
		}
		
		$levels = [];
		$grade = MemberGrade::where([
			['level_percent', '>', 0],
			['status', '=', 1],
		])->order('id')->select();
		foreach ($grade as $g) {
			$levels['grade'.$g['id']] = ['id'=>$g['id'], 'level'=>$g['level_percent'], 'line'=>$g['line_percent'], 'recomment'=>$g['recomment']];
		}
		
		//团队奖(级别收益)
		$memberMoneyData = [];
		$walletLogData = [];
		$maxGradeId = 0; //最大等级
		$startGradeId = 0; //起始等级
		$maxGrade = MemberGrade::where([
			['level_percent', '>', 0],
			['status', '=', 1],
		])->order(['sort', 'id'])->field('id')->select();
		foreach ($maxGrade as $g) {
			if ($startGradeId == 0) $startGradeId = $g->id;
			$maxGradeId = $g->id;
		}
		$lastGradeId = -1; //循环的当前等级
		$skipMemberId = []; //跳过的会员
		$isLine = true; //上次循环的是否平级
		$lastLevelPercent = 0; //上次循环的级别奖励
		$lastMoney = 0; //上次循环的级别奖励价格
		for ($i = 0; $i < count($tree); $i++) {
			$member_id = $tree[$i];
			$user = Member::alias('m')->leftJoin('blind_order bo', 'bo.member_id=m.id')
				->where([
					['m.id', '=', $member_id],
					['bo.status', '=', 1],
					['m.grade_id', '>=', $startGradeId]
				])
				->field('m.money, m.grade_id')->find();
			if (!$user) continue; //当前循环会员不存在或没有进行中的盲盒订单，跳过
			if ($user->grade_id < $lastGradeId) { //上级比当前循环会员的等级低，跳过
				$skipMemberId[] = $member_id;
				continue;
			}
			//if (!isset($levels['grade'.$user->grade_id])) continue;
			$level = $levels['grade'.$user->grade_id];
			//平级收益
			if ($user->grade_id == $lastGradeId) {
				if ($isLine) { //当前循环是平级且上次循环是平级，跳过
					$skipMemberId[] = $member_id;
					continue;
				}
				if (floatval($level['line']) <= 0) continue;
				$money = floatval(bcmul(strval($lastMoney), strval($level['line']/100), 8));
				$memberMoneyData[] = ['id'=>$member_id, 'money'=>bcadd(strval($user->money), strval($money), 8)];
				$walletLogData[] = [
					'member_id' => $member_id,
					'number' => $money,
					'old' => $user->money,
					'new' => bcadd(strval($user->money), strval($money), 8),
					'remark' => '平级奖励'.$level['line'].'%',
					'type' => 6,
					'status' => 1,
					'money_type' => 1,
					'fromid' => $fromid,
					'fromtable' => $fromtable,
					'add_time' => time(),
				];
				if ($lastGradeId == $maxGradeId) break;
				$isLine = true;
				continue;
			}
			if (floatval($level['level']) <= 0) continue;
			$money = floatval(bcmul(strval($price), strval(($level['level']-$lastLevelPercent)/100), 8));
			$lastMoney = $money;
			$memberMoneyData[] = ['id'=>$member_id, 'money'=>bcadd(strval($user->money), strval($money), 8)];
			$walletLogData[] = [
				'member_id' => $member_id,
				'number' => $money,
				'old' => $user->money,
				'new' => bcadd(strval($user->money), strval($money), 8),
				'remark' => '团队奖励'.$level['level'].'%',
				'type' => 5,
				'status' => 1,
				'money_type' => 1,
				'fromid' => $fromid,
				'fromtable' => $fromtable,
				'add_time' => time(),
			];
			$lastGradeId = $user->grade_id;
			$lastLevelPercent = $level['level'];
			if (!$isLine) {
				if ($lastGradeId == $maxGradeId) break;
			}
			$isLine = false;
		}
		if (count($memberMoneyData) > 0) {
			$memberModel = new Member();
			$memberModel->saveAll($memberMoneyData);
		}
		if (count($walletLogData) > 0) {
			$walletLogModel = new MoneyLog();
			$walletLogModel->saveAll($walletLogData);
		}
	}
	
	//下线购买等级的上级奖励、祖辈升级
	public static function gradeLevelUp($from_id, $grade, $fromid = 0, $fromtable = '') {
		$price = $grade->price;
		$member = Member::whereId($from_id)->field('parent_id, parent_tree')->find();
		if ($member->parent_id <= 0) return; //没有上级，断开
		
		$parent = Member::alias('m')->leftJoin('blind_order bo', 'bo.member_id=m.id')
			->where(['m.id'=>$member->parent_id, 'bo.status'=>1])
			->field('m.money')->find();
		//if (!$parent) return; //上级不存在或没有进行中的盲盒订单，断开
		if ($parent && floatval($grade->recommend) > 0) {
			//上级奖励
			$money = floatval(bcmul(strval($price), strval($grade->recommend/100), 8));
			Member::whereId($member->parent_id)->update(['money'=>bcadd(strval($parent->money), strval($money), 8)]);
			MoneyLog::create([
				'member_id' => $member->parent_id,
				'number' => $money,
				'old' => $parent->money,
				'new' => bcadd(strval($parent->money), strval($money), 8),
				'remark' => '推荐奖励'.$grade->recommend.'%',
				'type' => 7,
				'status' => 1,
				'money_type' => 1,
				'fromid' => $fromid,
				'fromtable' => $fromtable,
				'add_time' => time(),
			]);
		}
		
		//祖辈升级
		//$memberData = [];
		$maxGradeId = 0; //最大等级
		$maxGrade = MemberGrade::where([
			['level_percent', '>', 0],
			['status', '=', 1],
		])->order(['sort', 'id'])->field('id')->select();
		foreach ($maxGrade as $g) $maxGradeId = $g->id;
		$gradeIds = [];
		$generalGradeId = 0; //普通等级
		$generalGrade = MemberGrade::where('status', 1)->order(['sort', 'id'])->field('id, level_percent')->select();
		foreach ($generalGrade as $g) {
			if ($g->level_percent <= 0) $generalGradeId = $g->id;
			$gradeIds[] = $g->id;
		}
		$person = [];
		$personGrade = MemberGrade::where([
			['level_percent', '>', 0],
			['status', '=', 1],
		])->order(['sort', 'id'])->field('id, person')->select();
		foreach ($personGrade as $g) {
			$person['grade'.$g->id] = $g->person;
		}
		$tree = array_reverse(explode(',', $member->parent_tree));
		for ($i = 0; $i < count($tree); $i++) {
			$member_id = $tree[$i];
			$user = Member::whereId($member_id)->field('grade_id')->find();
			if ($user->grade_id >= $maxGradeId) continue; //已最高等级，不能再升级
			$index = array_search($user->grade_id, $gradeIds); //当前循环会员的等级id所在索引
			$count = 0;
			$list = Member::where(['parent_id'=>$member_id, 'status'=>1])->field('id')->select();
			if (!$list) continue;
			foreach ($list as $g) {
				$quantity = Member::where([
					['status', '=', 1],
					['grade_id', '>=', $user->grade_id == $generalGradeId ? $gradeIds[$index+1] : $user->grade_id],
				])->whereRaw("id=$g->id OR CONCAT(',',parent_tree,',') LIKE '%,$g->id,%'")->count();
				if ($quantity > 0) $count++;
			}
			if ($count >= $person['grade'.($user->grade_id == $generalGradeId ? $gradeIds[$index+2] : $gradeIds[$index+1])]) {
				/*$memberData[] = [
					'id' => $member_id,
					'grade_id' => $user->grade_id == $generalGradeId ? $gradeIds[$index+2] : $gradeIds[$index+1],
				];*/
				$memberModel = new Member();
				$memberModel->saveAll([
					[
						'id' => $member_id,
						'grade_id' => $user->grade_id == $generalGradeId ? $gradeIds[$index+2] : $gradeIds[$index+1],
					]
				]);
			}
		}
		/*if (count($memberData) > 0) {
			$memberModel = new Member();
			$memberModel->saveAll($memberData);
		}*/
	}
}
