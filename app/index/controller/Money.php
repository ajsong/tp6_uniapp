<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\model\Config;
use app\model\Recharge;
use app\model\MoneyLog;
use app\model\Withdraw;

class Money extends Core
{
	
	/**
	 * showdoc
	 * @catalog 账单
	 * @title 资产
	 * @method get
	 * @url #
	 * @param type 必选 int 0总览，1现货，2合约
	 * @return {}
	 */
	public function index() {
		$type = $this->request->get('type/d', 0);
		$member = $this->get_member_from_token($this->token, true);
		$full_name = 'Tether';
		$pic = '/uploads/pic/2022/08/25/22082515062373916.png';
		switch ($type) {
			case 0:
				$asset = strval($member->money);
				$list = \app\model\Funds::alias('f')->where('uid', $this->memberId)
					->leftJoin('symbol s', 'f.symbol=s.name')
					->field('UPPER(f.symbol) as symbol, quantity, 0 as current, full_name, pic')->select()->each(function($item) use (&$asset) {
					$res = getCoinRate($item->symbol, 'bian');
					$item->quantity = floatval($item->quantity);
					$item->current = floatval(bcmul(strval($res), strval($item->quantity), 8));
					$item->pic = add_domain($item->pic);
					$asset = bcadd($asset, strval($item->current), 8);
				});
				$list = $list->toArray();
				array_unshift($list, ['symbol'=>'USDT', 'full_name'=>$full_name, 'pic'=>$pic, 'quantity'=>floatval($member->money), 'current'=>floatval($member->money)]);
				break;
			case 1:
				$asset = strval($member->xh_money);
				$list = \app\model\Funds::alias('f')->where('uid', $this->memberId)
					->leftJoin('symbol s', 'f.symbol=s.name')
					->field('UPPER(f.symbol) as symbol, quantity, 0 as current, full_name, pic')->select()->each(function($item) use (&$asset) {
					$res = getCoinRate($item->symbol, 'bian');
					$item->quantity = floatval($item->quantity);
					$item->current = floatval(bcmul(strval($res), strval($item->quantity), 8));
					$item->pic = add_domain($item->pic);
					$asset = bcadd($asset, strval($item->current), 8);
				});
				$list = $list->toArray();
				array_unshift($list, ['symbol'=>'USDT', 'full_name'=>$full_name, 'pic'=>$pic, 'quantity'=>floatval($member->xh_money), 'current'=>floatval($member->xh_money)]);
				break;
			default:
				$asset = strval($member->hy_money);
				$list = [['symbol'=>'USDT', 'full_name'=>$full_name, 'pic'=>$pic, 'quantity'=>floatval($member->hy_money), 'current'=>floatval($member->hy_money)]];
				break;
		}
		return success([
			'asset' => floatval($asset),
			'list' => add_domain_deep($list, 'pic'),
		]);
	}
	
	/**
	 * showdoc
	 * @catalog 账单
	 * @title 钱包记录
	 * @method get
	 * @url #
	 * @param page 可选 int 页数，0开始
	 * @param pagesize 可选 int 每页记录数
	 * @param type 必选 int 1充值，2提现，11合约，12现货
	 * @param start_time 可选 string 起始日期，如2022-8-18
	 * @param end_time 可选 string 结束日期，如2022-8-18
	 * @return {}
	 */
	public function log() {
		$page = $this->request->get('page/d', 0);
		$pagesize = $this->request->get('pagesize/d', 15);
		$type = $this->request->get('type/d', 0);
		$start_time = $this->request->get('start_time');
		$end_time = $this->request->get('end_time');
		switch ($type) {
			case 11:
				$list = \app\model\ConTransaction::where('uid', $this->memberId)->order('id', 'desc')->page($page, $pagesize)
					->field("id, UPPER(symbol) as symbol, side, '' as position, type, entryPrice as price, quantity as amount, fee, rp, timestamp, '' as add_time");
				if ($start_time) $list->whereTime('timestamp', '>=', $start_time);
				if ($end_time) $list->whereTime('timestamp', '<=', $end_time.' 23:59:59');
				$list = $list->select()->each(function($item) {
					$item->type = $item->type == 'Limit' ? '限价' : '市价';
					$item->position = $item->side == 'BUY' ? '买入' : '卖出';
					$item->add_time = date('Y-m-d H:i:s', $item->timestamp);
				});
				$list->hidden(['side', 'timestamp']);
				break;
			case 12:
				$list = \app\model\Transaction::where([
					['uid', '=', $this->memberId],
					['status', 'in', '1,3'],
				])->field("id, UPPER(symbol) as symbol, position, type, fee, quantity as amount, 0 as price, limt_price, timestamp, '' as add_time")
					->order('id', 'desc')->page($page, $pagesize);
				if ($start_time) $list->whereTime('timestamp', '>=', $start_time);
				if ($end_time) $list->whereTime('timestamp', '<=', $end_time.' 23:59:59');
				$list = $list->select()->each(function($item) {
					$item->price = bcmul(strval($item->amount), strval($item->limt_price), 3);
					$item->type = $item->type == 'Limit' ? '限价' : '市价';
					$item->position = $item->position == 'BUY' ? '买入' : '卖出';
					$item->add_time = date('Y-m-d H:i:s', $item->timestamp);
				});
				$list->hidden(['timestamp']);
				break;
			default:
				$where = [];
				$where[] = ['ml.member_id', '=', $this->memberId];
				$where[] = ['ml.type', 'in', $type > 0 ? $type : [1, 2, 11, 12]];
				$where[] = ['ml.remark', '<>', '提现不通过退还金额'];
				if ($type == 1) $where[] = ['ml.status', '=', 1];
				$list = MoneyLog::alias('ml')->field('id, remark, number, status, add_time')
					->where($where)->order('id', 'desc')->page($page, $pagesize);
				if ($start_time) $list->whereTime('add_time', '>=', $start_time);
				if ($end_time) $list->whereTime('add_time', '<=', $end_time.' 23:59:59');
				$list = $list->select()->each(function($item) {
					$item->number = str_replace('-', '', $item->number);
					$item->add_time = date('Y-m-d H:i', $item->add_time);
				});
		}
		return success($list);
	}
	
	/**
	 * showdoc
	 * @catalog 账单
	 * @title 账单列表
	 * @method get
	 * @url #
	 * @param type 可选 int 0今日盈利，1累计盈利
	 * @param page 可选 int 页数，0开始
	 * @param pagesize 可选 int 每页记录数
	 * @return {}
	 */
	public function listing() {
		$type = $this->request->get('type/d', 0);
		$page = $this->request->get('page/d', 0);
		$pagesize = $this->request->get('pagesize/d', 15);
		$list = \app\model\ConTransaction::alias('t')
			->leftJoin(['con_strategy'=>'s'], 't.strategy_id=s.id')
			->where([
				['t.uid', '=', $this->memberId],
				['t.status', '<>', 0],
				//['t.rp', '>', 0],
			])->field('t.*, s.order_sn, "" as add_time')->order('t.id', 'desc');
		if ($type == 0) $list = $list->whereTime('t.timestamp', 'today');
		$list = $list->page($page, $pagesize)->select()->each(function($item) use ($type) {
			if ($type == 0) {
				$item->add_time = date('Y-m-d H:i:s', $item->timestamp);
			} else {
				$item->add_time = date('Y-m-d', $item->timestamp);
			}
		});
		$list->hidden(['timestamp']);
		return success([
			'list' => $list,
			'today' => \app\model\ConTransaction::alias('t')
				->leftJoin(['con_strategy'=>'s'], 't.strategy_id=s.id')
				->where([
					['t.uid', '=', $this->memberId],
					['t.status', '<>', 0],
					//['t.rp', '>', 0],
				])->whereTime('t.timestamp', 'today')->sum('t.rp'),
			'total' => \app\model\ConTransaction::alias('t')
				->leftJoin(['con_strategy'=>'s'], 't.strategy_id=s.id')
				->where([
					['t.uid', '=', $this->memberId],
					['t.status', '<>', 0],
					//['t.rp', '>', 0],
				])->sum('t.rp'),
			'month' => \app\model\ConTransaction::alias('t')
				->leftJoin(['con_strategy'=>'s'], 't.strategy_id=s.id')
				->where([
					['t.uid', '=', $this->memberId],
					['t.status', '<>', 0],
					//['t.rp', '>', 0],
				])->whereTime('t.timestamp', 'month')->sum('t.rp'),
			'last_month' => \app\model\ConTransaction::alias('t')
				->leftJoin(['con_strategy'=>'s'], 't.strategy_id=s.id')
				->where([
					['t.uid', '=', $this->memberId],
					['t.status', '<>', 0],
					//['t.rp', '>', 0],
				])->whereTime('t.timestamp', 'last month')->sum('t.rp'),
		]);
	}
	
	/**
	 * showdoc
	 * @catalog 账单
	 * @title 提现页面
	 * @method get
	 * @url #
	 * @return {}
	 * @return_param type array 提现网络
	 * @return_param money float 钱包余额
	 * @return_param fee float 手续费(带百分号即按百分比计算)
	 * @return_param least float 最低提现金额
	 * @return_param memo string 提现说明
	 */
	/**
	 * showdoc
	 * @catalog 账单
	 * @title 提现申请
	 * @description 使用 登录注册-发送验证码 接口发送验证码，type使用withdraw
	 * @method post
	 * @url #
	 * @param type 必选 int 提现网络
	 * @param address 必选 string 提现钱包地址
	 * @param code 必选 string 验证码
	 * @param price 必选 float 提现金额(含手续费，即实际到手金额将低于price)
	 * @return {}
	 */
	public function withdraw() {
		$member = \app\model\Member::whereId($this->memberId)->find();
		$fee = Config::get('withdraw_fee');
		$least = floatval(Config::get('withdraw_least'));
		if (IS_POST) {
			$pickup = 0;
			$open_pickup = Config::get('open_pickup');
			$close_pickup = Config::get('close_pickup');
			if ($open_pickup && $close_pickup) {
				$now = time();
				$begin = strtotime(date('Y-m-d').' '.$open_pickup);
				$end = strtotime(date('Y-m-d').' '.$close_pickup);
				if ($end <= $begin) $end = strtotime(date('Y-m-d H:i:s', $end).' +1 day');
				if ($now >= $begin && $now <= $end) $pickup = 1;
			}
			if ($pickup == 0) error('当前时间不可提现');
			
			$field = $this->request->post('field', 'money');
			$type = $this->request->post('type/d', 3); //提现来源或网络(会员表字段名、getTypes)
			$address = $this->request->post('address');
			$code = $this->request->post('code');
			$price = $this->request->post('price/f', 0); //不含手续费
			if (!$code || $type <= 0 || !$address || $price <= 0) error(lang('data.error'));
			if ($code != \Send\SendCode::getCode('withdraw')) error(lang('passport.tips.code'));
			if ($price < $least) error(lang('member.withdraw.least').$least);
			
			$money = $member->{$field};
			
			if (preg_match('/%$/', $fee)) $fee = bcmul(strval($price), strval(floatval(substr($fee, 0, strlen($fee)-1))/100), 8);
			if (bccomp(strval($price), strval($money), 8) === 1) error(lang('member.withdraw.most').bcmul(strval($money), '1', 2));
			
			$id = Withdraw::insertGetId([
				'member_id' => $this->memberId,
				'order_sn' => generate_sn(),
				'field' => $field,
				'type' => $type,
				'address' => $address,
				'price' => $price,
				'fee' => $fee,
				'total' => bcadd(strval($price), $fee, 8),
				'ip' => $this->ip,
				'add_time' => time(),
			]);
			
			$money = bcsub($money, strval($price), 8);
			MoneyLog::withdraw($this->memberId, $price, '申请提现', $field, MoneyLog::getFieldUnit($field), $id, 'withdraw', $fee);
			MoneyLog::balance($this->memberId, $fee, $money, '申请提现(手续费)', 2, 0, MoneyLog::getFieldUnit($field), $id, 'withdraw');
			
			\app\model\Member::whereId($this->memberId)->update([$field=>bcsub($money, strval($fee), 8)]);
			
			return success(null, '提交成功');
		}
		return success([
			'type' => \app\model\Core::keyValueToTDArray(Withdraw::getTypes(), 'id', 'name'),
			'money' => floatval($member->money),
			'fee' => $fee,
			'least' => $least,
			'memo' => Config::get('withdraw_memo'),
		]);
	}
	
	/**
	 * showdoc
	 * @catalog 账单
	 * @title 充值页面
	 * @method get
	 * @url #
	 * @return {}
	 * @return_param type array 充值网络
	 * @return_param address string U盾地址
	 * @return_param memo string 充值说明
	 */
	/**
	 * showdoc
	 * @catalog 账单
	 * @title 充值提交
	 * @method post
	 * @url #
	 * @param num 必选 int 数量
	 * @param pic 必选 file 凭证，图片文件
	 * @return {}
	 */
	public function recharge() {
		if (IS_POST) {
			$field = $this->request->post('field', 'money');
			$num = $this->request->post('num/f', 0);
			$pic = c('upload')->image('pic', 'recharge');
			if ($num <= 0 || !$pic) error('缺少参数');
			$member_id = $this->memberId;
			$add_time = time();
			Recharge::insert(compact('member_id', 'field', 'num', 'pic', 'add_time'));
			return success(null, '提交成功');
		}
		return success([
			'type' => \app\model\Core::keyValueToTDArray(Withdraw::getTypes(), 'id', 'name'),
			'address' => Config::get('recharge_address'),
			'memo' => Config::get('recharge_memo'),
		]);
	}
	
	/**
	 * showdoc
	 * @catalog 账单
	 * @title 划转页面
	 * @method get
	 * @url #
	 * @return {}
	 * @return_param money float 总览余额
	 * @return_param hy_money float 合约余额
	 * @return_param xh_money float 现货余额
	 */
	/**
	 * showdoc
	 * @catalog 账单
	 * @title 划转提交
	 * @method post
	 * @url #
	 * @param from 必选 int 来源，0总览，1合约，2现货
	 * @param to 必选 int 目标，0总览，1合约，2现货
	 * @param money 必选 float 划转金额
	 * @return {}
	 */
	public function trans() {
		$member = \app\model\Member::whereId($this->memberId)->field('money, hy_money, xh_money')->find();
		if (IS_POST) {
			$from = $this->request->post('from/d', 0);
			$to = $this->request->post('to/d', 0);
			$money = $this->request->post('money/f', 0);
			if ($from < 0 || $from > 2 || $to < 0 || $to > 2 || $money <= 0) error(lang('data.error'));
			$fields = ['money', 'hy_money', 'xh_money'];
			$fields_name = ['总览账户', '合约账户', '现货账户'];
			$from_field = $fields[$from];
			if (bccomp(strval($money), strval($member->{$from_field}), 8) === 1) error(lang('data.error'));
			
			$from_name = $fields_name[$from];
			$from_old = $member->{$from_field};
			$from_new = bcsub(strval($member->{$from_field}), strval($money), 8);
			$to_field = $fields[$to];
			$to_name = $fields_name[$to];
			$to_old = $member->{$to_field};
			$to_new = bcadd(strval($member->{$to_field}), strval($money), 8);
			
			$api = new \app\model\PythonApi();
			if ($to == 1) {
				$ret = $api->update($this->memberId, 1, $money);
				if ($ret != 1) error($ret);
			} else if ($from == 1) {
				$ret = $api->update($this->memberId, 2, $money);
				if ($ret != 1) error($ret);
			}
			
			\app\model\MoneyLog::insert([
				'member_id' => $this->memberId,
				'number' => $money,
				'old' => $from_old,
				'new' => $from_new,
				'remark' => "{$from_name}→{$to_name}",
				'type' => 13,
				'status' => 0,
				'money_type' => 0,
				'add_time' => time(),
			]);
			\app\model\MoneyLog::insert([
				'member_id' => $this->memberId,
				'number' => $money,
				'old' => $to_old,
				'new' => $to_new,
				'remark' => "{$from_name}→{$to_name}",
				'type' => 13,
				'status' => 1,
				'money_type' => 0,
				'add_time' => time(),
			]);
			\app\model\Member::whereId($this->memberId)->update([
				"$from_field" => $from_new,
				"$to_field" => $to_new,
			]);
			
			return success(null, '提交成功');
		}
		return success([
			'money' => $member->money,
			'hy_money' => $member->hy_money,
			'xh_money' => $member->xh_money,
		]);
	}
}
