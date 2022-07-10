<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\model\MemberGrade;
use app\model\MemberWallet;
use app\model\Reward;
use app\model\MoneyLog;
use function GuzzleHttp\Psr7\str;

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
		if ($keyword) $where[] = ['m.name|m.mobile|m.email|mw.wallet', 'like', "%$keyword%"];
	    if (!is_null($status)) $where[] = ['m.status', '=', $status];
		if ($grade_id > 0) $where[] = ['m.grade_id', '=', $grade_id];
		if ($parent_id > 0) $where[] = ['m.parent_id', '=', $parent_id];
		$children = \app\model\Member::alias('c')->whereColumn('c.parent_id', 'm.id')->fetchSql()->count();
	    $list = \app\model\Member::alias('m')
		    ->leftJoin('member_grade mg', 'm.grade_id=mg.id')
		    ->leftJoin('member_wallet mw', 'mw.member_id=m.id')
		    ->leftJoin('member p', 'p.id=m.parent_id')
		    ->leftJoin('member_wallet mwp', 'mwp.member_id=m.parent_id')
		    ->where($where)->order('m.id', 'desc')
		    ->field('m.*, p.name as parent_name, p.mobile as parent_mobile, p.email as parent_email, mg.name as grade_name, mw.wallet, mwp.wallet as parent_wallet, ('.$children.') as children')
	        ->paginate($this->paginateArr());
		$grade = MemberGrade::where('status', 1)->select();
		return $this->render(compact('list', 'grade'));
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
				if (!$wallet) error('请输入会员钱包地址');
				//if (!$password) error('请填写密码');
				if (MemberWallet::where('wallet', strtolower($wallet))->count()) error('该会员钱包地址已存在');
				$password = random_str(8);
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
				MemberWallet::create(['member_id'=>$id, 'wallet'=>strtolower($wallet)]);
				if ($grade_id > 1) $is_change_grade = true;
			}
			if ($parent_wallet) {
				$inviter = MemberWallet::alias('mw')
					->leftJoin('member m', 'm.id=mw.member_id')
					->where('mw.wallet', strtolower($parent_wallet))->field('m.id, m.parent_tree')->find();
				if ($inviter) {
					\app\model\Member::whereId($id)->update([
						'parent_id' => $inviter->id,
						'parent_tree' => trim($inviter->parent_tree.','.$inviter->id, ','),
					]);
				}
			}
			if ($is_change_grade) {
				//下线购买等级的上级奖励、祖辈升级
				$grade = MemberGrade::whereId($grade_id)->find();
				Reward::gradeLevelUp($id, $grade);
			}
			return success('tourl:member/index', '提交成功');
		} else if ($id > 0) {
			$row = \app\model\Member::alias('m')
				->leftJoin('member_wallet mw', 'm.id=mw.member_id')
				->leftJoin('member_wallet mwp', 'mwp.member_id=m.parent_id')
				->where('m.id', $id)->field('m.*, mw.wallet, mwp.wallet as parent_wallet')->find();
		} else {
			$row = t('member');
			$row['wallet'] = '';
			$row['parent_wallet'] = '';
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
		\app\model\MemberWallet::where('member_id', $id)->delete();
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
			$level = $this->request->post('level/f', 0);
			$line = $this->request->post('line/f', 0);
			$single_total = $this->request->post('single_total/f', 0);
			$team_total = $this->request->post('team_total/f', 0);
			$person = $this->request->post('person/d', 0);
			$status = $this->request->post('status/d', 0);
			$sort = $this->request->post('sort/d', 0);
			if (!$name) error('缺少数据');
			$data = compact('name', 'level', 'line', 'single_total', 'team_total', 'person', 'status', 'sort');
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
			$data = ['name'=>$g['name'], 'level'=>$g['level'], 'line'=>$g['line'], 'single_total'=>$g['single_total'], 'team_total'=>$g['team_total'], 'person'=>$g['person'], 'status'=>$g['status'], 'sort'=>$g['sort']];
			if (isset($g['id']) && $g['id'] > 0) {
				MemberGrade::update($data, ['id'=>$g['id']]);
			} else {
				MemberGrade::create($data);
			}
		}
		return success(null, '提交成功');
	}
	
	//@会员等级删除
	public function grade_delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		MemberGrade::destroy(['id'=>$id]);
		return success('stay:', '操作成功');
	}
	
	//@会员充值
	public function recharge() {
		if (IS_POST) {
			$member_id = $this->request->post('member_id/d', 0);
			$field = $this->request->post('field');
			$number = $this->request->post('number/f', 0);
			$remark = $this->request->post('remark');
			if ($member_id <= 0) error('数据错误');
			$money = \app\model\Member::whereId($member_id)->value($field);
			\app\model\Member::update(["$field"=>bcadd(strval($money), strval($number), 8)], ['id'=>$member_id]);
			MoneyLog::recharge($member_id, $number, $remark, $field);
			return success('tourl:member/index', '充值成功');
		}
		return $this->render([
			'currency' => MoneyLog::getMoneyCurrency()
		]);
	}
}
