<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\model\Banner;
use app\model\Config;
use app\model\BlindOrder;
use app\model\MemberGrade;
use app\model\Member;
use app\model\Reward;
use app\model\MoneyLog;

use think\facade\Cache;

class Index extends Core
{
    public function index() {
		if (!IS_AJAX) $this->redirect('/mobile');
		$game_url = Config::get('game_url');
		if (strlen($game_url)) $game_url = explode(PHP_EOL, $game_url);
		$blind_begin = Config::get('blind_begin');
		$blind_end = Config::get('blind_end');
	    $activate = 1;
		if ($blind_begin && $blind_end) {
			$now = time();
			$begin = strtotime(date('Y-m-d').' '.$blind_begin);
			$end = strtotime(date('Y-m-d').' '.$blind_end);
			if ($end <= $begin) $end = strtotime(date('Y-m-d H:i:s', $end).' +1 day');
			$activate = ($now >= $begin && $now <= $end) ? 1 : 0;
		}
		return success([
			'banner' => Banner::get('flash'),
			'wallet' => config('app.client_wallet'),
			'activate' => $activate,
			'begin' => $blind_begin,
			'end' => $blind_end,
			'rule' => cut_str(Config::get("game_rule$this->lang"), 180),
			'url' => $game_url,
			'blind' => add_domain_deep(\app\model\Blind::getBoxData(), 'pic'),
			'debug' => intval(Config::get('app_debug')),
		]);
    }
	
	public function rule() {
		return success(Config::get("game_rule$this->lang"));
	}
	
	public function counter() {
		$grade = MemberGrade::where([
			['level_percent', '>', 0],
			['status', '=', 1],
		])->order(['sort', 'id'])->field('id, name, level_percent')->select();
		return success([
			'grade' => $grade,
			'percent' => Config::get('counter_percent'),
			'day' => Config::get('counter_day'),
			'people' => Config::get('counter_people'),
		]);
	}
	
	//购买盲盒
	public function submit() {
		$resubmit = $this->request->post('resubmit/f', 0); //预生成订单
		$price = $this->request->post('price/f', 0);
		$reinvest = $this->request->post('reinvest/d', 0); //重投
		$hash = $this->request->post('hash');
		$verify = $this->request->post('verify/d', 0); //验证
		
		if ($resubmit != 0 && !$hash && $reinvest == 0) error(lang('index.submit.hash'));
		if ($price <= 0) error(lang('index.submit.price'));
		
		$blind_begin = Config::get('blind_begin');
		$blind_end = Config::get('blind_end');
		$activate = 1;
		if ($blind_begin && $blind_end) {
			$now = time();
			$begin = strtotime(date('Y-m-d').' '.$blind_begin);
			$end = strtotime(date('Y-m-d').' '.$blind_end);
			if ($end <= $begin) $end = strtotime(date('Y-m-d H:i:s', $end).' +1 day');
			$activate = ($now >= $begin && $now <= $end) ? 1 : 0;
		}
		if ($activate <= 0) error(str_replace('%s', $blind_begin.'~'.$blind_end, lang('not.blind')));
		
		$blind = \app\model\Blind::getBoxData();
		if ($price < $blind['price_min']) error(lang('index.submit.price_min').$blind['price_min']);
		if ($price > $blind['price_max']) error(lang('index.submit.price_max').$blind['price_max']);
		
		if ($reinvest == 1) {
			$money = Member::whereId($this->memberId)->value('money');
			if ($price > $money) error(lang('team.buy.balance'));
		}
		
		$data = [];
		foreach ($blind['statics'] as $static) {
			$data[] = ['day' => $static['day'], 'rate' => $static['rate'], 'count' => $static['count']];
		}
		
		return $this->_lottery($data, $blind, $blind['statics'], $hash, $price, $resubmit, $reinvest, $verify);
	}
	private function _lottery($data, $blind, $statics, $hash, $price, $resubmit = 0, $reinvest = 0, $verify = 0) {
		$member_id = $this->memberId;
		if ($verify == 0) {
			//预订单
			if ($reinvest == 0) {
				if ($resubmit == 0) {
					$probability = [];
					foreach ($data as $value) {
						$probability[] = $value['rate'];
					}
					$index = $this->_get_rand($probability);
					$day = $data[$index]['day'];
					$count = $data[$index]['count'];
					if ($count <= 0) { //盲盒数量不足，重抽其他
						$s = [];
						foreach ($statics as $static) {
							if (intval($static['day']) != intval($day)) $s[] = $static;
						}
						return $this->_lottery($data, $blind, $s, $hash, $price);
					}
				} else {
					$res = BlindOrder::whereId($resubmit)->field('day')->find();
					if (!$res) error(lang('data.error'));
					BlindOrder::whereId($resubmit)->update(['hash'=>$hash]);
					$day = $res->day;
				}
			} else {
				//复投
				$day = $blind['static_sure'];
			}
			
			if ($resubmit == 0) {
				$percent = 0;
				$pic = '';
				foreach ($statics as $static) {
					if (intval($static['day']) == intval($day)) {
						$percent = $static['percent'];
						$pic = $static['pic'];
						break;
					}
				}
				$total = bcadd(strval($price), bcmul(strval($price), bcdiv(strval($percent), '100', 8), 8), 8);
				$status = 0;
				$add_time = time();
				$order_id = BlindOrder::insertGetId(compact('member_id', 'hash', 'price', 'day', 'percent', 'total', 'pic', 'reinvest', 'status', 'add_time'));
				$no = 51000000 + $order_id;
				BlindOrder::whereId($order_id)->update(['no'=>$no]);
			} else {
				$order_id = $resubmit;
			}
			
			return success($order_id);
		}
		
		//验证订单
		$id = $verify;
		$status = 0;
		$where = compact('id', 'member_id', 'price', 'status');
		$res = BlindOrder::where($where)->field('id, day, price, reinvest')->find();
		if (!$res) error(lang('data.error'));
		BlindOrder::where($where)->update(['status'=>1]);
		$order_id = $res->id;
		$day = $res->day;
		$price = $res->price;
		$reinvest = $res->reinvest;
		
		//盲盒下单奖励
		Reward::grant($member_id, $price, $order_id, 'blind_order');
		
		if ($reinvest == 0) {
			//减扣数量
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
		
		return success(null, lang('index.submit.success'));
	}
	private function _get_rand($probability) {
		//概率数组的总概率精度
		$max = array_sum($probability);
		foreach ($probability as $index => $value) {
			$rand_number = mt_rand(1, $max); //从1到max中随机一个值
			if ($rand_number <= $value) { //如果这个值小于等于当前中奖项的概率，我们就认为已经中奖
				return $index;
			} else {
				$max -= $value; //否则max减去当前中奖项的概率，然后继续参与运算
			}
		}
		/*
		确保对每个人获取奖品的概率是一样的
		如果某件奖品没了，应该讲概率修改为0
		考虑到高并发，在检测到用户中奖后，应该检查一下奖品是否存在，没了就直接返回没中奖或者次一级奖品
		最后才将中奖结果返回
		*/
		return -1;
	}
}
