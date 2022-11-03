<?php
declare (strict_types = 1);

namespace app\index\controller;



use app\model\Config;

class Member extends Core
{
	
	/**
	 * showdoc
	 * @catalog 会员
	 * @title 会员首页
	 * @method get
	 * @url #
	 * @return {}
	 * @return_param data object 会员信息
	 */
    public function index() {
	    $member = $this->get_member_from_token($this->token, true);
	    //unset($member->id);
	    return success($member);
    }
	
	/**
	 * showdoc
	 * @catalog 会员
	 * @title 上传头像
	 * @method post
	 * @url #
	 * @param avatar 1 file 图片文件
	 * @return {}
	 */
	public function avatar() {
		$avatar = c('upload')->image('avatar', 'avatar');
		if (!$avatar) error('请选择图片');
		$avatar = add_domain($avatar);
		\app\model\Member::whereId($this->memberId)->update(['avatar'=>$avatar]);
		$this->get_member_from_token($this->token, true);
		return success($avatar);
	}
	
	/**
	 * showdoc
	 * @catalog 会员
	 * @title 修改资料
	 * @method post
	 * @url #
	 * @param nick_name 0 string 昵称
	 * @return {}
	 */
	public function edit() {
		$name = $this->request->post('name');
		if ($name) {
			$name = preg_replace('/\s+/', '', $name);
			if (strlen($name)<3) error('账号至少需要3个字符');
			if (is_en_cn($name)!=0) error('账号只能是英文、数字、下划线');
			if (strlen($name)) {
				if (\app\model\Member::where([['name', '=', $name], ['id', '<>', $this->memberId]])->count() > 0) error('该账号已被占用');
			}
		}
		$fields = ['name', 'real_name', 'nick_name', 'mobile', 'idcard', 'avatar', 'sex', 'wechat', 'qq', 'alipay', 'remark',
			'province', 'city', 'district', 'town', 'birth_year', 'birth_month', 'birth_day'];
		$data = [];
		foreach ($fields as $field) {
			$value = $this->request->post($field);
			if ($value) $data[$field] = $value;
		}
		if (count($data)) \app\model\Member::whereId($this->memberId)->update($data);
		return success(null, '修改成功');
	}
	
	/**
	 *showdoc
	 * @catalog 会员
	 * @title 判断密码
	 * @method get
	 * @url #
	 * @return {}
	 * @return_param password string 原密码
	 */
	public function check_password() {
		$password = $this->request->post('password');
		if (!$password) error('密码不能为空');
		$member = \app\model\Member::whereId($this->memberId)->field('password, salt')->find();
		if ($member) {
			if ($member->password == crypt_password($password, $member->salt)) return success();
		}
		return error('密码错误');
	}
	
	/**
	 * showdoc
	 * @catalog 会员
	 * @title 修改密码
	 * @description 使用 登录注册-发送验证码 接口发送验证码，type使用password
	 * @method post
	 * @url #
	 * @param code 1 string 验证码
	 * @param password 1 string 新密码
	 * @param re_password 1 string 确认密码
	 * @return {}
	 */
	public function password() {
		$code = $this->request->post('code');
		//$origin_password = $this->request->post('origin_password');
		$password = $this->request->post('password');
		$re_password = $this->request->post('re_password');
		if (!$code || !$password || !$re_password) error('请填写完整');
		if ($password != $re_password) error('两次密码不一致');
		if ($code != \Send\SendCode::getCode('password')) error(lang('passport.tips.code'));
		//$member = \app\model\Member::whereId($this->memberId)->field('password, salt')->find();
		//if (!$member) error('数据错误');
		//if ($member->password != crypt_password($origin_password, $member->salt)) error('原密码不正确');
		$salt = generate_salt();
		$crypt_password = crypt_password($password, $salt);
		\app\model\Member::whereId($this->memberId)->update(['password'=>$crypt_password, 'salt'=>$salt]);
		return success(null, '提交成功');
	}
	
	/**
	 *showdoc
	 * @catalog 会员
	 * @title 修改支付密码
	 * @description 使用 登录注册-发送验证码 接口发送验证码，type使用pay_password
	 * @method post
	 * @url #
	 * @param code 1 string 验证码
	 * @param password 1 string 新密码
	 * @param re_password 1 string 确认密码
	 * @return {}
	 */
	public function pay_password() {
		$code = $this->request->post('code');
		//$origin_password = $this->request->post('origin_password');
		$password = $this->request->post('password');
		$re_password = $this->request->post('re_password');
		$session = \Send\SendCode::getCode('pay_password');
		if (!$code || !$password || !$re_password) error('请填写完整');
		if ($password != $re_password) error('两次密码不一致');
		if ($code != $session) error('验证码不正确');
		//$member = \app\model\Member::whereId($this->memberId)->field('password, salt')->find();
		//if (!$member) error('数据错误');
		//if ($member->password != crypt_password($origin_password, $member->salt)) error('原密码不正确');
		$salt = generate_salt();
		$crypt_password = crypt_password($password, $salt);
		\app\model\Member::whereId($this->memberId)->update(['pay_password'=>$crypt_password, 'pay_salt'=>$salt]);
		return success(null, '提交成功');
	}
	
	/**
	 *showdoc
	 * @catalog 会员
	 * @title 修改提现密码
	 * @description 使用 登录注册-发送验证码 接口发送验证码，type使用withdraw_password
	 * @method post
	 * @url #
	 * @param code 1 string 验证码
	 * @param password 1 string 新密码
	 * @param re_password 1 string 确认密码
	 * @return {}
	 */
	public function withdraw_password() {
		$code = $this->request->post('code');
		//$origin_password = $this->request->post('origin_password');
		$password = $this->request->post('password');
		$re_password = $this->request->post('re_password');
		$session = \Send\SendCode::getCode('withdraw_password');
		if (!$code || !$password || !$re_password) error('请填写完整');
		if ($password != $re_password) error('两次密码不一致');
		if ($code != $session) error('验证码不正确');
		//$member = \app\model\Member::whereId($this->memberId)->field('password, salt')->find();
		//if (!$member) error('数据错误');
		//if ($member->password != crypt_password($origin_password, $member->salt)) error('原密码不正确');
		$salt = generate_salt();
		$crypt_password = crypt_password($password, $salt);
		\app\model\Member::whereId($this->memberId)->update(['withdraw_password'=>$crypt_password, 'withdraw_salt'=>$salt]);
		return success(null, '提交成功');
	}
	
	/**
	 * showdoc
	 * @catalog 会员
	 * @title 反馈页面
	 * @method get
	 * @url #
	 * @return {}
	 * @return_param data string 客服二维码图片地址
	 */
	/**
	 * showdoc
	 * @catalog 会员
	 * @title 反馈提交
	 * @method post
	 * @url #
	 * @param content 1 string 反馈内容
	 * @param account 0 string 外网账号
	 * @return {}
	 */
	public function feedback() {
		if (IS_POST) {
			$content = $this->request->post('content');
			$account = $this->request->post('account', '');
			if (!$content) error('请输入反馈内容');
			$member_id = $this->memberId;
			$add_time = time();
			\app\model\Feedback::insert(compact('member_id', 'content', 'account', 'add_time'));
			return success(null, '提交成功');
		}
		return success(add_domain(Config::get('service_qrcode')));
	}
	
	/**
	 * showdoc
	 * @catalog 会员
	 * @title 反馈列表
	 * @method get
	 * @url #
	 * @param page 可选 int 页数，0开始
	 * @param pagesize 可选 int 每页记录数
	 * @return {}
	 */
	public function feedback_listing() {
		$page = $this->request->get('page/d', 0);
		$pagesize = $this->request->get('pagesize/d', 15);
		$list = \app\model\Feedback::where('member_id', $this->memberId)->field("*, '' as status")->page($page, $pagesize)->select()->each(function($item) {
			$item->status = $item->replay ? '已处理' : '待处理';
			$item->add_time = date('Y-m-d H:i:s', $item->add_time);
		});
		return success($list);
	}
	
	/**
	 * showdoc
	 * @catalog 会员
	 * @title 提币地址页面
	 * @method get
	 * @url #
	 * @return {}
	 * @return_param data string 提币地址
	 */
	/**
	 * showdoc
	 * @catalog 会员
	 * @title 提币地址
	 * @method post
	 * @url #
	 * @param address 1 string 提币地址
	 * @return {}
	 */
	public function withdraw_address() {
		if (IS_POST) {
			$address = $this->request->post('address');
			if (!$address) error('请输入提币地址');
			\app\model\Member::whereId($this->memberId)->update(['withdraw_address'=>$address]);
			return success(null, '提交成功');
		}
		$member = \app\model\Member::whereId($this->memberId)->field('withdraw_address')->find();
		return success($member->withdraw_address);
	}
	
	/**
	 * showdoc
	 * @catalog 会员
	 * @title 我的团队
	 * @method get
	 * @url #
	 * @param page 可选 int 页数，0开始
	 * @param pagesize 可选 int 每页记录数
	 * @return {}
	 * @return_param count int 团队总人数
	 * @return_param price float 团队总业绩
	 * @return_param list int 直推列表
	 */
	public function team() {
		$page = $this->request->get('page/d', 0);
		$pagesize = $this->request->get('pagesize/d', 15);
		$hy = \app\model\ConStrategy::whereRaw('uid=m.id')->fieldRaw('SUM(quantity)')->buildSql();
		$xh = \app\model\ConStrategy::whereRaw('uid=m.id')->fieldRaw('SUM(quantity)')->buildSql();
		$list = \app\model\Member::alias('m')->where('parent_id', $this->memberId)
			->field("email, nick_name, reg_time, ($hy+$xh) as price")
			->order('id')->page($page, $pagesize)
			->select()->each(function($item) {
				$item->nick_name = $item->nick_name ?: '-';
				$item->reg_time = date('Y-m-d', $item->reg_time);
			});
		$children = \app\model\Member::getChildren($this->memberId);
		$count = count($children);
		$price = \app\model\ConStrategy::whereIn('uid', $children)->sum('quantity');
		$price = bcadd(strval($price), strval(\app\model\ConStrategy::whereIn('uid', $children)->sum('quantity')), 8);
		return success([
			'count' => $count,
			'price' => $price,
			'list' => $list,
		]);
	}
	
}
