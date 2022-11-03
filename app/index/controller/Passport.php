<?php
declare (strict_types = 1);

namespace app\index\controller;


use app\model\Config;
use app\model\MemberGrade;
use app\model\Member;
use Send\SendCode;
use think\facade\Session;

class Passport extends Core
{
	/**
	 *showdoc
	 * @catalog 登录注册
	 * @title 钱包登录
	 * @description 钱包存在即登录，否则自动注册
	 * @method post
	 * @url #
	 * @param wallet 必选 string 钱包地址
	 * @param invite_code 可选 string 邀请码
	 * @return {}
	 */
	public function wallet() {
		if (!IS_POST) return success();
		$wallet = $this->request->post('wallet');
		if (!$wallet) error(lang('passport.tips.wallet'));
		$wallet = strtolower($wallet);
		$member = Member::fromWallet($wallet);
		if ($member) {
			return $this->_passport($member);
		} else {
			return $this->_passport('user'.generate_code(6), null, true);
		}
	}
	
	/**
	 * showdoc
	 * @catalog 登录注册
	 * @title 登录
	 * @method post
	 * @url #
	 * @param name 必选 string 邮箱
	 * @param password 必选 string 密码
	 * @return {}
	 */
	public function login() {
		if (!IS_POST) return success();
		$name = $this->request->post('name');
		$password = $this->request->post('password');
		if (!$name || !$password) error(lang('passport.tips.name'));
		return $this->_passport($name, $password);
	}
	
	/**
	 * showdoc
	 * @catalog 登录注册
	 * @title 注册页面
	 * @method get
	 * @url #
	 * @return {}
	 * @return_param data string 用户协议
	 */
	/**
	 * showdoc
	 * @catalog 登录注册
	 * @title 注册
	 * @method post
	 * @url #
	 * @param name 必选 string 邮箱
	 * @param password 必选 string 密码
	 * @param code 必选 string 邮箱验证码
	 * @param invite_code 可选 string 邀请码
	 * @return {}
	 */
	public function register() {
		if (!IS_POST) return success(Config::get('agreement'));
		$name = $this->request->post('name');
		$password = $this->request->post('password');
		$code = $this->request->post('code');
		if (!$name || !$password) error(lang('passport.tips.name'));
		if (!$code) error(lang('passport.tips.code'));
		if ($code != SendCode::getCode('register')) error(lang('passport.tips.code'));
		return $this->_passport($name, $password, true);
	}
	
	private function _passport($name, $password='', $is_register=false, $return_obj=false) {
		if (!$is_register) {
			//登录
			if ($name instanceof Member) {
				$member = $name;
			} else {
				$where = [];
				if (is_mobile($name)) $where[] = ['mobile', '=', $name];
				else if (is_email($name)) $where[] = ['email', '=', $name];
				else $where[] = ['name', '=', $name];
				$member = Member::where($where)->find();
				if (!$member) error(lang('passport.tips.not.exist'));
				if (crypt_password($password, $member->salt) != $member->password) error(lang('passport.tips.password.error'));
			}
			if ($member->status == 0) error(lang('passport.tips.status'));
			$data = [
				'session_id' => Session::getId(),
				'last_ip' => $this->ip,
				'last_time' => time(),
				'logins' => $member->logins + 1
			];
			if (!$member->getAttr('name') || !strlen($member->getAttr('name'))) {
				$data['name'] = 'user' . generate_code(6);
			}
			if (config('app.multi_terminal', 0) == 0) {
				$token = generate_token();
				$data['token'] = $token;
			} else {
				$token = Member::where('id', $member->id)->value('token');
				if (!$token) {
					$token = generate_token();
					$data['token'] = $token;
				}
			}
			Member::where('id', $member->id)->update($data);
		} else {
			//注册
			$where = [];
			if (is_mobile($name)) $where[] = ['mobile', '=', $name];
			else if (is_email($name)) $where[] = ['email', '=', $name];
			else $where[] = ['name', '=', $name];
			$member = Member::where($where)->find();
			if ($member) error(lang('passport.tips.exist'));
			if (!$password || !strlen($password)) $password = generate_code(8);
			list($password, $salt) = generate_password($password);
			while (true) {
				$invite = random_str(10);
				if (Member::where(['invite_code'=>$invite])->count() == 0) break;
			}
			$token = generate_token();
			$wallet = $this->request->post('wallet', '');
			$data = [
				'password' => $password,
				'salt' => $salt,
				'token' => $token,
				'wallet' => $wallet,
				'session_id' => Session::getId(),
				'invite_code' => $invite,
				'status' => 1,
				'last_ip' => $this->ip,
				'last_time' => time(),
				'logins' => 1,
				'reg_ip' => $this->ip,
				'reg_time' => time()
			];
			if (is_mobile($name)) $data['mobile'] = $name;
			else if (is_email($name)) $data['email'] = $name;
			else $data['name'] = $name;
			$invite_code = $this->request->post('invite_code');
			if ($invite_code) {
				$inviter = Member::where('invite_code', $invite_code)->field('id, parent_tree')->find();
				if ($inviter) {
					$data['parent_id'] = $inviter->id;
					$data['parent_tree'] = trim($inviter->parent_tree.','.$inviter->id, ',');
				}
			}
			//设置最低等级
			$grade = MemberGrade::where('status', 1)->order(['sort', 'id'])->field('id')->find();
			if ($grade) {
				$data['grade_id'] = $grade->id;
			}
			Member::insert($data);
		}
		
		if (cookie('?member_token')) cookie('member_token', null);
		session('member', null);
		$member = $this->get_member_from_token($token, true);
		
		//头像
		if ($member->avatar && strlen($member->avatar)) {
			$member->avatar = add_domain($member->avatar);
		} else {
			$member->avatar = add_domain('/static/images/avatar.png');
		}
		
		//记住登录
		$remember = $this->request->post('remember/d', 0);
		if ($remember) {
			cookie('member_token', $token, 60*60*24*365);
		}
		
		if ($return_obj) return $member;
		
		$RETURN_TYPE = defined('RETURN_TYPE') ? strtolower(RETURN_TYPE) : '';
		$IS_AJAX = IS_AJAX || $RETURN_TYPE === 'json' || $RETURN_TYPE === 'xml';
		if (!$IS_AJAX) {
			$url = session('api_gourl');
			if ($url) {
				session('api_gourl', null);
				return success("tourl:$url");
			}
		}
		
		//unset($member->id);
		return success($member);
	}
	
	/**
	 * showdoc
	 * @catalog 登录注册
	 * @title 忘记密码
	 * @method post
	 * @url #
	 * @param name 必选 string 账号
	 * @param code 必选 string 验证码
	 * @param password 必选 string 新密码
	 * @return {}
	 */
	public function forget() {
		if (IS_POST) {
			$name = $this->request->post('name');
			$password = $this->request->post('password');
			$code = $this->request->post('code');
			if (!$name || !$password) error(lang('passport.tips.forget.name'));
			if (!$code) error(lang('passport.tips.code'));
			$session = SendCode::getCode('forget');
			if (!$session || $code != $session) error(lang('passport.tips.code'));
			$where = [];
			if (is_mobile($name)) $where[] = ['mobile', '=', $name];
			else $where[] = ['email', '=', $name];
			$member = Member::where($where)->field('id')->find();
			if (!$member) error(lang('passport.tips.not.exist'));
			list($password, $salt) = generate_password($password);
			Member::whereId($member->id)->update(['password'=>$password, 'salt'=>$salt]);
			return success(null, lang('passport.tips.forget.success'));
		}
		return success();
	}
	
	/**
	 * showdoc
	 * @catalog 登录注册
	 * @title 发送验证码
	 * @method post
	 * @url #
	 * @param name 必选 string 接收验证码的手机或邮箱
	 * @param type 必选 string 接收验证码的接口名称，例如register，forget
	 * @return {}
	 */
	public function send() {
		if (IS_POST) {
			$name = $this->request->post('name');
			$type = $this->request->post('type', 'register');
			if (!$name) error(lang('passport.tips.send'));
			$model = new SendCode($name, $type);
			if ($model->send()) {
				return success(null, lang('passport.tips.send.success'));
			}
			return error(lang('data.error'));
		}
		return success();
	}
	
	/**
	 * showdoc
	 * @catalog 登录注册
	 * @title 退出登录
	 * @method get
	 * @url #
	 * @return {}
	 */
	public function logout() {
		if (cookie('?member_token')) cookie('member_token', null);
		session('member', null);
		return success('tourl:/index/passport/login', lang('passport.logout'));
	}
}
