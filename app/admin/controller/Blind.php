<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\model\BlindOrder;
use app\model\Config;
use app\model\Member;
use app\model\Reward;
use app\model\MoneyLog;
use think\facade\Cache;

//@blind
class Blind extends Core
{
	public function index() {
		$keyword = $this->request->get('keyword');
		$member_id = $this->request->get('member_id/f', 0);
		$price_min = $this->request->get('price_min/f', 0);
		$price_max = $this->request->get('price_max/f', 0);
		$day = $this->request->get('day/d', 0);
		$status = $this->request->get('status');
		$begin_time = $this->request->get('begin_time');
		$end_time = $this->request->get('end_time');
		$sortby = $this->request->get('sortby');
		$where = [];
		if ($keyword) $where[] = ['mw.wallet', 'like', "%$keyword%"];
		if ($member_id > 0) $where[] = ['bo.member_id', '=', $member_id];
		if ($price_min > 0) $where[] = ['price', '>=', $price_min];
		if ($price_max > 0) $where[] = ['price', '<=', $price_max];
		if ($day > 0) $where[] = ['day', '=', $day];
		if (!is_null($status)) $where[] = ['status', '=', $status];
		if ($begin_time) $where[] = ['add_time', '>=', strtotime($begin_time)];
		if ($end_time) $where[] = ['add_time', '<=', strtotime($end_time.' 23:59:59')];
		
		$order = ['bo.id'=>'desc'];
		if ($sortby) {
			$sort = explode(',', $sortby);
			$order = [$sort[0] => $sort[1]] + $order;
		}
		
		$list = BlindOrder::alias('bo')
			->leftJoin('member_wallet mw', 'mw.member_id=bo.member_id')
			->where($where)->field('bo.*, mw.wallet')->order($order)
			->paginate($this->paginateArr());
		
		$blind = \app\model\Blind::getBoxData();
		$days = [];
		foreach ($blind['statics'] as $g) {
			$days[] = $g['day'];
		}
		return $this->render([
			'list' => $list,
			'days' => $days,
		]);
	}
	
	//盲盒收益
	public function income() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('缺少数据');
		$row = BlindOrder::where(['status'=>1, 'id'=>$id])->whereNotNull('hash')->whereColumn('income_count', '<', 'day')->find();
		if (!$row) error('该下注单已过期');
		$memberMoneyData = [];
		$walletLogData = [];
		$blindOrderData = [];
		
		$income_count = $row->income_count + 1;
		$money = bcdiv(strval($row->total), strval($row->day), 8);
		if ($income_count >= $row->day) {
			//本次为最后一次
			$money = bcsub(strval($row->total), bcmul($money, strval($row->day - 1), 8), 8);
		}
		$member = \app\model\Member::whereId($row->member_id)->field('money')->find();
		$memberMoneyData[] = ['id'=>$row->member_id, 'money'=>$member->money + $money];
		$walletLogData[] = [
			'member_id' => $row->member_id,
			'number' => $money,
			'old' => $member->money,
			'new' => $member->money + $money,
			'remark' => '盲盒收益',
			'type' => 8,
			'status' => 1,
			'money_type' => 1,
			'fromid' => $row->id,
			'fromtable' => 'blind_order',
			'add_time' => time(),
		];
		$data = ['id'=>$row->id, 'income_count'=>$income_count];
		if ($income_count >= $row->day) $data['status'] = 2;
		$blindOrderData[] = $data;
		
		if (count($memberMoneyData) > 0) {
			$memberModel = new Member();
			$memberModel->saveAll($memberMoneyData);
		}
		if (count($walletLogData) > 0) {
			$walletLogModel = new MoneyLog();
			$walletLogModel->saveAll($walletLogData);
		}
		if (count($blindOrderData) > 0) {
			$blindOrderModel = new BlindOrder();
			$blindOrderModel->saveAll($blindOrderData);
		}
		return success('tourl:blind/index', '操作成功');
	}
	
	//设为生效
	public function status() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('缺少数据');
		$res = BlindOrder::whereId($id)->find();
		$order_id = $res->id;
		$day = $res->day;
		$price = $res->price;
		$reinvest = $res->reinvest;
		$member_id = $res->member_id;
		
		//盲盒下单奖励
		Reward::grant($member_id, $price, $order_id, 'blind_order');
		
		if ($reinvest == 0) {
			//减扣数量
			$blind = \app\model\Blind::getBoxData();
			$counts = [];
			foreach ($blind['statics'] as $static) {
				if (intval($static['day']) == intval($day)) {
					$counts[] = intval($static['count']) - 1;
				} else {
					$counts[] = intval($static['count']);
				}
			}
			\app\model\Blind::whereId(1)->update(['static_count' => implode(',', $counts)]);
			Cache::delete('blind:data');
		} else {
			//减扣余额
			$money = Member::whereId($member_id)->value('money');
			MoneyLog::payment($member_id, $price, '余额支付盲盒复投', 'money', 'USDT', $order_id, 'blind_order');
			Member::whereId($member_id)->update(['money' => bcsub(strval($money), strval($price), 8)]);
			
			//积分
			$reinvest_integral = Config::get('reinvest_integral');
			$usdt_integral = Config::get('usdt_integral');
			if (floatval($reinvest_integral) > 0 && floatval($usdt_integral) > 0) {
				$integral = Member::whereId($member_id)->value('integral');
				$exchange = bcmul(bcmul(strval($price), strval($reinvest_integral/100), 8), $usdt_integral, 8);
				Member::whereId($member_id)->update(['integral' => bcadd(strval($integral), $exchange, 8)]);
				MoneyLog::create([
					'member_id' => $member_id,
					'number' => $exchange,
					'old' => $integral,
					'new' => bcadd(strval($integral), $exchange, 8),
					'remark' => '复投获得积分',
					'type' => 3,
					'status' => 1,
					'money_type' => 4,
					'fromid' => $order_id,
					'fromtable' => 'blind_order',
					'add_time' => time()
				]);
			}
		}
		
		BlindOrder::whereId($id)->update(['status'=>1]);
		return success('tourl:blind/index', '操作成功');
	}
	
	//设为过期
	public function overdue() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('缺少数据');
		$row = BlindOrder::whereId($id)->find();
		BlindOrder::whereId($id)->update(['income_count'=>$row->day, 'status'=>2]);
		return success('tourl:blind/index', '操作成功');
	}
	
	public function set() {
		if (IS_POST) {
			$price_min = $this->request->post('price_min/f', 0);
			$price_max = $this->request->post('price_max/f', 0);
			$static_days = $this->request->post('static_day/a');
			$static_percents = $this->request->post('static_percent/a');
			$static_rates = $this->request->post('static_rate/a');
			$static_counts = $this->request->post('static_count/a');
			$static_pics = $this->request->post('static_pic/a');
			$static_sure = $this->request->post('static_sure/d', 0);
			$share_percents = $this->request->post('share_percent/a');
			$total = 0;
			array_map(function($e) use (&$total) {
				$total += floatval($e);
			}, $static_rates);
			if ($total != 100) error('所有<strong style="color:red;">开奖率</strong>相加必须等于100');
			if ($price_max < $price_min) {
				$price_tmp = $price_min;
				$price_min = $price_max;
				$price_max = $price_tmp;
			}
			$static_day = is_array($static_days) ? implode(',', $static_days) : '';
			$static_percent = is_array($static_percents) ? implode(',', $static_percents) : '';
			$static_rate = is_array($static_rates) ? implode(',', $static_rates) : '';
			$static_count = is_array($static_counts) ? implode(',', $static_counts) : '';
			$static_pic = is_array($static_pics) ? implode(',', $static_pics) : '';
			$share_percent = is_array($share_percents) ? implode(',', $share_percents) : '';
			$data = compact('price_max', 'price_min', 'static_day', 'static_percent', 'static_rate', 'static_count', 'static_pic', 'static_sure', 'share_percent');
			\app\model\Blind::update($data, ['id'=>1]);
		}
		$data = \app\model\Blind::getBoxData(true);
		return $this->render([
			'data' => $data
		]);
	}
}
