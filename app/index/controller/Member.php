<?php
declare (strict_types = 1);

namespace app\index\controller;


use app\model\RechargeLog;

class Member extends Core
{
    public function index() {
		$level = $this->request->get('level/d', 0);
	    $member = $this->get_member_from_token($this->token, true);
		if ($level == 1) {
			if ($member->grade_id == 2) error(lang('team.buy.grade.low'));
			$res = RechargeLog::where(['member_id'=>$this->memberId])->field('status')->find();
			if ($res) {
				if ($res->status == 0) error(lang('team.buy.grade.recharge'));
				if ($res->status == 1) error(lang('team.buy.grade.low'));
			}
		}
	    unset($member->password, $member->salt, $member->withdraw_password, $member->withdraw_salt, $member->pay_password, $member->pay_salt);
		session('member', $member);
	    unset($member->id);
	    return success($member);
    }
}
