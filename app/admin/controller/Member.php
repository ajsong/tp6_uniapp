<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\model\MemberGrade;
use app\model\MoneyLog;

//@member
class Member extends Core
{
	public function index() {
		$id = $this->request->get('id/d', 0);
		$keyword = $this->request->get('keyword');
		$status = $this->request->get('status');
		$grade_id = $this->request->get('grade_id/d', 0);
		$parent_id = $this->request->get('parent_id/d', 0);
		$where = [];
		if ($id > 0) $where[] = ['m.id', '=', $id];
		if ($keyword) $where[] = ['m.name|m.mobile|m.email|m.wallet', 'like', "%$keyword%"];
		if (is_numeric($status)) $where[] = ['m.status', '=', $status];
		if ($grade_id > 0) $where[] = ['m.grade_id', '=', $grade_id];
		if ($parent_id > 0) $where[] = ['m.parent_id', '=', $parent_id];
		$children = \app\model\Member::alias('c')->whereColumn('c.parent_id', 'm.id')->fetchSql()->count();
		$list = \app\model\Member::alias('m')
			->leftJoin('member_grade mg', 'm.grade_id=mg.id')
			->leftJoin('member p', 'p.id=m.parent_id')
			->where($where)->order('m.id', 'desc')
			->field('m.*, p.name as parent_name, p.mobile as parent_mobile, p.email as parent_email, p.wallet as parent_wallet, mg.name as grade_name, ('.$children.') as children, 0 as team_total')
			->paginate($this->paginateArr())->each(function($item) {
				$team_total = \app\model\ConStrategy::alias('cs')
						->leftJoin('member m', 'm.id=cs.uid')
						->leftJoin(['con_transaction'=>'ct'], 'cs.id=ct.strategy_id')
						->whereRaw("CONCAT(',',m.parent_tree,',') LIKE '%,$item->id,%'")->where('side', 'BUY')->value('SUM(ct.entryPrice * cs.quantity)');
				$item->team_total = bcdiv(strval($team_total), strval(\app\model\Config::get('leverage')), 8);
			});
		$grade = MemberGrade::where('status', 1)->select();
		return $this->render([
			'list' => $list,
			'grade' => $grade,
		]);
	}
	
	//@会员添加
	public function add() {
		return $this->edit();
	}
	//@会员修改
	public function edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$name = $this->request->post('name');
			$mobile = $this->request->post('mobile');
			$email = $this->request->post('email');
			$nick_name = $this->request->post('nick_name');
			$password = $this->request->post('password');
			$pay_password = $this->request->post('pay_password');
			$grade_id = $this->request->post('grade_id/d', 0);
			//if (!$name && !$mobile && !$email) error('账号、手机、邮箱至少填写一项');
			$wallet = $this->request->post('wallet');
			$parent_wallet = $this->request->post('parent_wallet');
			$parent_code = $this->request->post('parent_code');
			$is_change_grade = false;
			$data = compact('name', 'mobile', 'email', 'nick_name', 'grade_id');
			if ($id > 0) {
				if ($password) {
					list($password, $salt) = generate_password($password);
					$data['password'] = $password;
					$data['salt'] = $salt;
				}
				if ($pay_password) {
					list($pay_password, $pay_salt) = generate_password($pay_password);
					$data['pay_password'] = $pay_password;
					$data['pay_salt'] = $pay_salt;
				}
				$grade = \app\model\Member::whereId($id)->value('grade_id');
				if ($grade_id > $grade) $is_change_grade = true;
				\app\model\Member::update($data, ['id'=>$id]);
			} else {
				if (!$email) error('请输入邮箱地址');
				//if (!$password) error('请填写密码');
				if (\app\model\Member::where('email', $email)->count()) error('该邮箱地址已存在');
				if (!$password) $password = random_str(8);
				while (true) {
					$invite = random_str(10);
					if (\app\model\Member::where(['invite_code'=>$invite])->count() == 0) break;
				}
				list($password, $salt) = generate_password($password);
				$data['password'] = $password;
				$data['salt'] = $salt;
				if ($pay_password) {
					list($pay_password, $pay_salt) = generate_password($pay_password);
					$data['pay_password'] = $pay_password;
					$data['pay_salt'] = $pay_salt;
				}
				$data['invite_code'] = $invite;
				$data['status'] = 1;
				$data['reg_ip'] = $this->ip;
				$data['reg_time'] = time();
				$model = \app\model\Member::create($data);
				$id = $model->id;
				if ($grade_id > 1) $is_change_grade = true;
			}
			if ($parent_wallet || $parent_code) {
				$where = [];
				if ($parent_wallet) $where[] = ['wallet', '=', strtolower($parent_wallet)];
				if ($parent_code) $where[] = ['invite_code', '=', $parent_code];
				$inviter = \app\model\Member::where($where)->field('id, parent_tree')->find();
				if ($inviter) {
					\app\model\Member::whereId($id)->update([
						'parent_id' => $inviter->id,
						'parent_tree' => trim($inviter->parent_tree.','.$inviter->id, ','),
					]);
				}
			}
			if ($is_change_grade) {
				//下线购买等级的上级奖励、祖辈升级
				//$grade = MemberGrade::whereId($grade_id)->find();
				//Reward::gradeLevelUp($id, $grade);
			}
			return success('tourl:member/index', '提交成功');
		} else if ($id > 0) {
			$row = \app\model\Member::alias('m')
				->leftJoin('member p', 'p.id=m.parent_id')
				->where('m.id', $id)->field('m.*, p.wallet as parent_wallet, p.invite_code as parent_code')->find();
		} else {
			$row = t('member');
			$row['wallet'] = '';
			$row['parent_wallet'] = '';
			$row['parent_code'] = '';
		}
		$grade = MemberGrade::order(['sort', 'id'])->select();
		return $this->render([
			'row' => $row,
			'grade' => $grade
		], 'edit');
	}
	
	//@会员删除
	public function delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Member::destroy(['id'=>$id]);
		return success(null, '操作成功');
	}
	
	//@会员冻结
	public function freeze() {
		$id = $this->request->get('id/d', 0);
		$status = $this->request->get('status/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Member::update(['status' => $status], ['id'=>$id]);
		return success(null, '操作成功');
	}
	
	//结算奖励
	public function reward() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		$children = \app\model\Member::getChildren($id);
		$list = \app\model\ConStrategy::where('uid', 'in', $children)->where('reward_fee', 0)->where('status', 'in', [1, 2, 3, 4, -1])->field('id, uid, fee')->select();
		if (count($list)) $this->_reward($list, 'con_strategy');
		$list2 = \app\model\Transaction::where('uid', 'in', $children)->where('reward_fee', 0)->where('status', 'in', [1, 3])->field('id, uid, fee')->select();
		if (count($list2)) $this->_reward($list2, 'transaction');
		return success('tourl:member/index', '操作成功');
	}
	private function _reward($list, $fromtable) {
		$transData = [];
		foreach ($list as $item) {
			$transData[] = ['id'=>$item->id, 'reward_fee'=>1];
			$member = \app\model\Member::where('id', $item->uid)->field('parent_tree')->find();
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
	
	//@会员查看资产
	public function asset() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		$member = \app\model\Member::whereId($id)->field('email')->find();
		if (!$member) error('会员不存在');
		//define('CURL_GFW', '127.0.0.1:7890');
		$list = \app\model\Funds::alias('f')->where('uid', $id)
			->leftJoin('symbol s', 'f.symbol=s.name')
			->field('UPPER(f.symbol) as symbol, quantity, 0 as current, full_name, pic')->select()->each(function($item) {
				$res = getCoinRate($item->symbol, 'bian');
				$item->quantity = floatval($item->quantity);
				$item->current = floatval(bcmul(strval($res), strval($item->quantity), 8));
			});
		return $this->render([
			'list' => $list,
			'email' => $member->email
		]);
	}
	
	public function grade() {
		$list = MemberGrade::where('status', 1)->order(['sort', 'id'])->select();
		return $this->render([
			'list' => $list
		]);
	}
	
	//@会员等级修改
	public function grade_edit() {
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$name = $this->request->post('name');
			$level_percent = $this->request->post('level_percent/f', 0);
			$line_percent = $this->request->post('line_percent/f', 0);
			$team_total = $this->request->post('team_total/f', 0);
			$status = $this->request->post('status/d', 0);
			$sort = $this->request->post('sort/d', 0);
			if (!$name) error('缺少数据');
			$data = compact('name', 'level_percent', 'line_percent', 'team_total', 'status', 'sort');
			if ($id > 0) {
				MemberGrade::update($data, ['id'=>$id]);
			} else {
				MemberGrade::create($data);
			}
			return success(null, '提交成功');
		}
		return null;
	}
	
	//@会员等级全部修改
	public function grade_edit_all() {
		$param = $this->request->post('param/a');
		if (!is_array($param)) error('数据错误');
		foreach ($param as $g) {
			$data = ['name'=>$g['name'], 'level_percent'=>$g['level_percent'], 'team_total'=>$g['team_total'], 'status'=>intval($g['status']), 'sort'=>intval($g['sort'])];
			if (isset($g['id']) && $g['id'] > 0) {
				MemberGrade::update($data, ['id'=>$g['id']]);
			} else {
				MemberGrade::create($data);
			}
		}
		return success(null, '提交成功');
	}
	
	//会员等级删除
	public function grade_delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		MemberGrade::destroy(['id'=>$id]);
		return success('stay:', '操作成功');
	}
	
	//@会员充值
	public function recharge() {
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$field = $this->request->post('field');
			$number = $this->request->post('number/f', 0);
			$remark = $this->request->post('remark');
			if ($id <= 0 || $number == 0) error('数据错误');
			
			$api = new \app\model\PythonApi();
			if ($field == 'hy_money') {
				$money = floatval($number);
				$ret = $api->update($id, $money > 0 ? 1 : 2, abs($money));
				if ($ret != 1) error($ret);
			}
			
			MoneyLog::recharge($id, $number, $remark, $field);
			$money = \app\model\Member::whereId($id)->value($field);
			\app\model\Member::update(["$field"=>bcadd(strval($money), strval($number), 8)], ['id'=>$id]);
			return success('tourl:member/index', '充值成功');
		}
		return $this->render([
			'currency' => MoneyLog::getCurrency()
		]);
	}
}
