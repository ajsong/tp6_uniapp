<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\Base;

define('RETURN_TYPE', 'json');

class Core extends Base {
	
	public $memberId = 0;
	public $memberObj;
	public $token = '';
	public $lang = 1;
	
	public function __construct() {
		parent::__construct();
		
		$locale_var = config('app.language');
		$lang = $this->request->header('Accept-Language');
		if (!$lang || !isset($locale_var[$lang])) $lang = env('lang.default_lang', '');
		if ($lang && isset($locale_var[$lang])) {
			$this->lang = $locale_var[$lang]['code'];
			cookie(config('lang.cookie_var'), $locale_var[$lang]['file'], 60*60*24*365);
		}
		
		$access_allow = config('app.access_allow');
		if ( is_array($access_allow) && count($access_allow) ) {
			if ( $access_allow[0] == '*' ||
				(isset($access_allow[$this->app]) && count($access_allow[$this->app]) &&
					(in_array('*', $access_allow[$this->app]) || in_array($this->act, array_map('strtolower', $access_allow[$this->app])))) ) {
				$access_allow_host = config('app.access_allow_host');
				if ( is_array($access_allow_host) && count($access_allow_host) ) {
					if ($access_allow_host[0] == '*') $access_allow_host = '*';
					else {
						if (in_array($this->request->server('HTTP_ORIGIN'), $access_allow_host)) $access_allow_host = $this->request->server('HTTP_ORIGIN');
						else $access_allow_host = '*';
					}
				} else {
					$access_allow_host = '*';
				}
				header("Access-Control-Allow-Origin: $access_allow_host");
				//header('Access-Control-Allow-Origin: *'); //允许所有地址跨域请求
				header('Access-Control-Allow-Methods: *'); //设置允许的请求方法, *表示所有, POST,GET,OPTIONS,DELETE
				header('Access-Control-Allow-Credentials: true'); //设置允许请求携带cookie, 此时origin不能用*
				header('Access-Control-Allow-Headers: x-requested-with,content-type,authorization,token'); //设置头
			}
		}
		
		$this->token = $this->request->header('token', '');
		if (!$this->token) $this->token = $this->request->get('token', '');
		if ($this->token) $this->_check_login();
		
		$member = session('member');
		if ($member) {
			$this->token = $member->token;
			$this->memberId = $member->id;
			$this->memberObj = $member;
			$member = \app\model\Member::whereId($this->memberId)->field('status')->find();
			if (!$member) {
				session('member', null);
				$this->token = '';
				$this->memberId = 0;
				$this->memberObj = null;
			} else if ($member->status != 1) {
				$this->not_login(lang('account.frozen'), -1);
			}
		}
		if ($this->memberId <= 0) {
			$not_check_login = config('app.not_check_login');
			if (is_array($not_check_login)) {
				if ( !isset($not_check_login[$this->app]) ) {
					$this->check_login();
				} else {
					if ( !in_array('*', $not_check_login[$this->app]) && !in_array($this->act, array_map('strtolower', $not_check_login[$this->app])) ) {
						$this->check_login();
					} else if ( $this->request->header('Authorization') !== NULL && strlen($this->request->header('Authorization')) ) {
						$this->check_login();
					}
				}
			}
		}
	}
	
	//由token获取会员信息
	public function get_member_from_token($token, $is_force=false) {
		if (!$token || !is_string($token) || !strlen($token)) $this->not_login();
		if (!$this->memberObj || $is_force) {
			$member = \app\model\Member::fromToken($token);
			if (!$member) {
				session('member', null);
				$this->not_login(lang('login.in.another'), -9);
				return null;
			}
			unset($member->password, $member->salt, $member->withdraw_password, $member->withdraw_salt, $member->pay_password, $member->pay_salt);
			$member->grade_name = \app\model\MemberGrade::whereId($member->grade_id)->value('name');
			$member->avatar = add_domain($member->avatar);
			$this->memberId = $member->id;
			$this->token = $member->token;
			$this->memberObj = $member;
			session('member', $member);
		} else {
			$member = $this->memberObj;
			$this->memberId = $member->id;
			$this->token = $member->token;
		}
		return $this->memberObj;
	}
	
	//是否登录
	public function _check_login() {
		$member = session('member');
		if ( $member && !strlen($this->token) ) {
			return $this->get_member_from_token($member->token, true);
		} else if ( strlen($this->token) ) {
			return $this->get_member_from_token($this->token);
		} else if ( cookie('?member_token') ) {
			return $this->get_member_from_token(cookie('member_token'));
		/*} else if ( defined('WX_LOGIN') && defined('WX_APPID') && defined('WX_SECRET') && WX_LOGIN && strlen(WX_APPID) && strlen(WX_SECRET) && IS_WAP && $this->is_wx && !$this->is_mini ) {
			if ($this->weixin_authed()) return true;
			$this->weixin_auth();*/
		} else if ( $this->request->header('Authorization') !== NULL && strlen($this->request->header('Authorization')) ) {
			$auth = $this->request->header('Authorization');
			if (strpos(strtolower($auth), 'basic') !== false) {
				$token = base64_decode(substr($auth, 6));
				if (strlen($token)) return $this->get_member_from_token($token);
			}
		}
		return null;
	}
	
	//判断是否登录
	public function check_login(): bool {
		if (!$this->_check_login()) {
			if (!IS_AJAX) session('api_gourl', $this->request->url());
			$this->not_login(lang('please.login'), -2);
			return false;
		}
		return true;
	}
	
	//没有登录处理
	public function not_login($msg = '请登录', $code = -2) {
		if (IS_AJAX) {
			error($msg, $code);
		} else {
			$this->redirect('/' . MODULE_NAME . '/passport/login');
		}
	}
	
	//输出模板
	public function render($data = [], $template_file = '') {
		if (IS_AJAX) {
			return success($data);
		}
		if (is_null($data)) $data = [];
		$data['member'] = $this->memberObj;
		if (strlen($template_file)) {
			if (preg_match('/\.html$/', $template_file)) { //.html结尾自动获取完整路径
				if (strpos(str_replace('\\', '/', $template_file), '/') === false) {
					$template_file = dirname(__FILE__, 2) . '/view/' . CONTROLLER_NAME . '/' . $template_file;
				}
			} else if (!preg_match('/<[^>]+>/', $template_file)) {
				$template_file .= '.view'; //.view结尾自动在前面追加当前Controller
			}
		} else {
			$template_file = 'success';
		}
		return success(null, $template_file, $data);
	}
}
