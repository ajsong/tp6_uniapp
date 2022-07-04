<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\Base;
use app\model\BlindOrder;
use app\model\MemberGradeOrder;
use app\model\MemberGrade;
use app\model\Member;
use app\model\Reward;
use app\model\MoneyLog;
use EthTool\Callback;
use Web3\Utils;
use Web3\Web3;

class Cron extends Base
{
    public function run() {
	    $this->blind_income();
		//$this->blind_everyday_income();
	    $this->check_hash_order('blind_order');
	    $this->check_hash_order('member_grade_order');
    }
	
	//盲盒是否已到收益时间
	public function blind_income() {
		$list = BlindOrder::where('status', 1)->field('id, member_id, day, total, add_time')->select();
		$memberMoneyData = [];
		$walletLogData = [];
		$blindOrderData = [];
		foreach ($list as $g) {
			if (time() - $g->add_time < $g->day * 60 *60 * 24) continue;
			$member = \app\model\Member::whereId($g->member_id)->field('money')->find();
			if (!$member) continue;
			$memberMoneyData[] = ['id'=>$g->member_id, 'money'=>bcadd(strval($member->money), strval($g->total), 8)];
			$walletLogData[] = [
				'member_id' => $g->member_id,
				'number' => $g->total,
				'old' => $member->money,
				'new' => bcadd(strval($member->money), strval($g->total), 8),
				'remark' => '盲盒收益',
				'type' => 8,
				'status' => 1,
				'money_type' => 1,
				'fromid' => $g->id,
				'fromtable' => 'blind_order',
				'add_time' => time(),
			];
			$blindOrderData[] = ['id'=>$g->id, 'status'=>2];
		}
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
		//write_log('Scheduled task: blind_income', '', false, true);
	}
	
	//盲盒每天收益
	public function blind_everyday_income() {
		$list = BlindOrder::where('status', 1)->whereColumn('income_count', '<', 'day')->select();
		$memberMoneyData = [];
		$walletLogData = [];
		$blindOrderData = [];
		foreach ($list as $g) {
			$income_count = $g->income_count + 1;
			$money = bcdiv(strval($g->total), strval($g->day), 8);
			if ($income_count >= $g->day) {
				//本次为最后一次
				$money = bcsub(strval($g->total), bcmul($money, strval($g->day - 1), 8), 8);
			}
			$member = \app\model\Member::whereId($g->member_id)->field('money')->find();
			$memberMoneyData[] = ['id'=>$g->member_id, 'money'=>bcadd(strval($member->money), strval($money), 8)];
			$walletLogData[] = [
				'member_id' => $g->member_id,
				'number' => $money,
				'old' => $member->money,
				'new' => bcadd(strval($member->money), strval($money), 8),
				'remark' => '盲盒收益',
				'type' => 8,
				'status' => 1,
				'money_type' => 1,
				'fromid' => $g->id,
				'fromtable' => 'blind_order',
				'add_time' => time(),
			];
			$data = ['id'=>$g->id, 'income_count'=>$income_count];
			if ($income_count >= $g->day) $data['status'] = 2;
			$blindOrderData[] = $data;
		}
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
		//write_log('Scheduled task: blind_everyday_income', '', false, true);
	}
	
	//检查hash订单状态
    public function check_hash_order($table) {
	    //客户地址
	    $address = config('app.client_wallet');
	    $cb = new Callback;
		//查询节点(正式、测试)
	    $web3 = new Web3('https://bsc-dataseed1.binance.org/');
	    //https://docs.bscscan.com/misc-tools-and-utilities/public-rpc-nodes
		
		$field = ['t.id', 't.member_id', 'hash', 'price', 'wallet'];
		if ($table == 'member_grade_order') {
			$field[] = 't.grade_id';
			$list = MemberGradeOrder::alias('t')->leftJoin('member_wallet mw', 'mw.member_id=t.member_id')
				->whereNotNull('hash')->whereStatus(0)->field($field)->select();
		} else {
			$list = BlindOrder::alias('t')->leftJoin('member_wallet mw', 'mw.member_id=t.member_id')
				->whereNotNull('hash')->whereStatus(0)->field($field)->select();
		}
		foreach ($list as $g) {
			$web3->eth->getTransactionReceipt($g->hash, $cb);
			$result = $cb->result;
			if ($result) {
				$res = Utils::jsonToArray($result);
				
				$price = bcdiv(strval($g->price), '1', 8);
				
				$num = hexdec($res['logs'][0]['data']);
				$num = bcdiv(strval($num), strval(pow(10, 18)), 8);
				$toaddress = substr_replace($res['logs'][0]['topics'][2], '', 2, 24);
				
				if (strtolower($toaddress) == strtolower($address) && strtolower($g->wallet) == strtolower($res['from']) && $num == $price) {
					if (hexdec($result->status) == 1) {
						if ($table == 'blind_order') {
							BlindOrder::whereId($g->id)->update(['status' => 1]);
							Reward::grant($g->member_id, $price, $g->id, $table);
						} else {
							MemberGradeOrder::whereId($g->id)->update(['status' => 1]);
							$grade = MemberGrade::whereId($g->grade_id)->field('price, recommend')->find();
							Reward::gradeLevelUp($this->memberId, $grade, $g->id, $table);
						}
					}
				}
				/*{
					"error": null
					"result": {
						"blockHash": "0xbb744de38834f41cc50491289e34ae6c887fd47c5a1d5baebe3f4482cab9c138"
						"blockNumber": "0x11683be"
						"contractAddress": null
						"cumulativeGasUsed": "0x199858e"
						"from": "0x3a861f5bc4957067ea3ce04658104c8ea8fd4800"
						"gasUsed": "0x8d07"
						"logs": [
							0 => {
								"address": "0x55d398326f99059ff775485246999027b3197955"
								"topics": [
									0 => "0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef"
									1 => "0x0000000000000000000000003a861f5bc4957067ea3ce04658104c8ea8fd4800"
									2 => "0x0000000000000000000000002a2bdcf89f6c8f359e5bffcec54fbcb7bc8afa66"
								]
								"data": "0x000000000000000000000000000000000000000000000000016345785d8a0000"
								"blockNumber": "0x11683be"
								"transactionHash": "0x966ba9fa608bd020e67b78787087a78af03e02cf73227aa14454860b6e2442a8"
								"transactionIndex": "0x101"
								"blockHash": "0xbb744de38834f41cc50491289e34ae6c887fd47c5a1d5baebe3f4482cab9c138"
								"logIndex": "0x2b4"
								"removed": false
							}
						]
						"logsBloom": "0x0000000000000000000000000000000000000000000000000000000000000000004000000001000000000000000"
						"status": "0x1"
						"to": "0x55d398326f99059ff775485246999027b3197955"
						"transactionHash": "0x966ba9fa608bd020e67b78787087a78af03e02cf73227aa14454860b6e2442a8"
						"transactionIndex": "0x101"
						"type": "0x0"
					}
				}*/
			}
		}
	    //write_log('Scheduled task: '.$table, '', false, true);
    }
}
