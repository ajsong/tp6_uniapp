<?php
declare (strict_types = 1);

//RPC方法
//http://cw.hubwiz.com/card/c/ethereum-json-rpc-api/
//合约方法
//https://testnet.bscscan.com/address/0xF43a883a65Ee204539d0e6744D382C5a9B504fA6#code

namespace app\model;

use EthTool\Callback;
use EthTool\Credential;
use EthTool\KeyStore;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Utils;
use Web3\Web3;

class MetaMask {
	
	public $web3;
	public $provider;
	public $contract;
	public $host, $chainId;
	public $contractPath, $keyPath;
	public $abi, $addr;
	
	public function __construct($host, $chainId, $contract = 'contract') {
		$this->host = $host;
		$this->chainId = $chainId;
		$this->web3 = $this->createWeb3($host);
		$this->provider = $this->web3->provider;
		
		$this->contractPath = root_path() . 'contract/';
		$this->keyPath = $this->contractPath . 'keystore/';
		$abi = $this->contractPath . $contract . '.abi';
		$addr = $this->contractPath . $contract . '.addr';
		if (!file_exists($abi)) exit('The abi file does not exist');
		if (!file_exists($addr)) exit('The addr file does not exist');
		$this->abi = file_get_contents($abi);
		$this->addr = file_get_contents($addr);
		
		$this->contract = new Contract($this->provider, $this->abi);
		$this->contract->at($this->addr);
	}
	
	//创建Web3
	public function createWeb3($host, $timeout = 30): Web3 {
		//set this time accordingly by default it is 1 sec
		return new Web3(new HttpProvider(new HttpRequestManager($host, $timeout)));
	}
	
	//生成私钥文件
	public function createKey($privateKey, $password) {
		$this->_createDir($this->keyPath);
		KeyStore::save($privateKey, $password, $this->keyPath);
	}
	private function _createDir($destination) {
		$root_path = root_path();
		$target_path = trim(str_replace($root_path, '', $destination), '/');
		if (is_dir($root_path.'/'.$target_path)) return true;
		$each_path = explode('/', $target_path);
		$cur_path = $root_path; //当前循环处理的路径
		$origin_mask = @umask(0);
		foreach ($each_path as $path) {
			if ($path) {
				$cur_path .= '/' . $path;
				if (!is_dir($cur_path)) {
					if (@mkdir($cur_path)) {
						@chmod($cur_path, 0777);
					} else {
						@umask($origin_mask);
						return false;
					}
				}
			}
		}
		@umask($origin_mask);
		return true;
	}
	
	//获取余额
	public function getBalance($account, $scale = 0, $decimals = 18) {
		$cb = new Callback;
		$this->web3->eth->getBalance($account, 'latest', $cb); //RPC方法
		$result = $cb->result;
		if ($result) {
			return $this->valueWithDecimals($result->value, $scale, $decimals);
		} else {
			return 0;
		}
	}
	
	//获取hash数据
	public function getHashData($hash, $host = '') {
		$cb = new Callback;
		$web3 = strlen($host) ? $this->createWeb3($host) : $this->web3;
		$web3->eth->getTransactionByHash($hash, $cb);
		if (!$cb->result) {
			if ($cb->error) echo $cb->error->getMessage();
			exit;
		}
		/*if ($cb->result->status == '0x1') { //getTransactionReceipt才有status
			return json_decode(json_encode($cb->result), true);
		}*/
		return json_decode(json_encode($cb->result), true);
	}
	
	//获取hash数据, 严格判断
	public function getHashOrder($hash, $price, $from, $to, $host = '') {
		$cb = new Callback;
		$web3 = strlen($host) ? $this->createWeb3($host) : $this->web3;
		$web3->eth->getTransactionReceipt($hash, $cb);
		if (!$cb->result) {
			if ($cb->error) echo $cb->error->getMessage();
			exit;
		}
		$res = Utils::jsonToArray($cb->result);
		$price = floatval($this->valueWithDecimals($price, 8));
		$num = floatval($this->valueWithDecimals(hexdec($res['logs'][0]['data']), 8));
		if (strtolower($from) == strtolower($res['from']) && strtolower($to) == strtolower(substr_replace($res['logs'][0]['topics'][2], '', 2, 24)) && $num == $price) {
			if (hexdec($res['status']) == 1) {
				return true;
			}
		}
		return false;
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
	
	//获取精度
	public function getDecimals($decimals = 18) {
		$cb = new Callback;
		$this->contract->call('decimals', $cb); //合约方法
		if ($cb->result && intval($cb->result[0]->value)) $decimals = $cb->result[0]->value;
		return intval($decimals);
	}
	
	//金额转精度数值
	public function valueWithDecimals($value, $scale = 0, $decimals = 18) {
		$decimals = $this->getDecimals($decimals);
		$mul = '1' . str_repeat('0', $decimals);
		return bcmul(strval($value), $mul, $scale);
	}
	/*//金额转精度数值, 另一种方法
	public function pow($value, $scale = 0, $decimals = 18) {
		$decimals = $this->getDecimals($decimals);
		return bcdiv(strval($value), strval(pow(10, intval($decimals))), $scale);
	}*/
	
	//10进制转16进制
	public function toHex($value) {
		return Utils::toHex($value, true);
	}
	
	//获取gasPrice
	public function gasPrice() {
		$cb = new Callback;
		$this->web3->eth->gasPrice($cb);
		if (!$cb->result) {
			if ($cb->error) echo $cb->error->getMessage();
			exit;
		}
		$res = intval($cb->result->value);
		$gwei = ceil(floatval(bcdiv(strval($res), '1000000000')));
		return strval($gwei);
	}
	
	//执行并估算一个交易需要的gas用量
	public function estimateGas($row) {
		$cb = new Callback;
		$arr = array(
			'from' => $row['from'],
			'to' => $row['to'],
			'data' => $row['data']
		);
		$this->web3->eth->estimateGas($arr, $cb);
		if (!$cb->result) {
			$res = 1800000;
		} else {
			$res = intval($cb->result->value);
			if (!$res) $res = 1800000;
		}
		return bcadd(bcmul(strval(ceil(floatval(bcdiv(strval($res), '10000', 8)))), '10000'), '10000');
	}
	
	//转账交易
	public function transfer($from, $to, $value) {
		$cb = new Callback;
		//返回指定地址发生的交易数量
		$this->web3->eth->getTransactionCount($from, 'pending', $cb);
		$nonce = $cb->result;
		if (!$nonce) {
			if ($cb->error) echo $cb->error->getMessage();
			exit;
		}
		$opts = [
			'from' => $from,
			'gas' => Utils::toHex(100000, true)
		];
		//价格加精度转16进制
		if (is_numeric($value)) {
			if (floatval($value) < 0) exit('Amount cannot be negative');
			$value = $this->toHex($this->valueWithDecimals($value));
		}
		//生成交易数据, 需要在\Web3\Contract内复制一份send函数,然后直接返回$transaction
		$raw = $this->contract->sends('transfer', $to, $value, $opts, $cb);
		$raw['from'] = $from;
		$raw['to'] = $to;
		$raw['gasPrice'] = '0x' . Utils::toWei($this->gasPrice(), 'gwei')->toHex();
		$raw['gasLimit'] = Utils::toHex($this->estimateGas($raw), true);
		$raw['value'] = $value;
		$raw['chainId'] = $this->chainId;
		$raw['nonce'] = Utils::toHex($nonce, true);
		//file_put_contents($this->contractPath.'log.txt', date('Y-m-d H:i:s') . PHP_EOL . json_encode($raw) . PHP_EOL . PHP_EOL, FILE_APPEND);
		//获取签名
		$file = $this->keyPath.substr(strtolower($from), 2).'.json';
		if (!file_exists($file)) exit('The certificate file does not exist');
		$credential = Credential::fromWallet(env('wallet.password'), $file);
		$signed = $credential->signTransaction($raw);
		//发起裸交易
		$this->web3->eth->sendRawTransaction($signed, $cb);
		if ($cb->result) {
			return $this->waitForTransaction($cb->result);
		}
		return false;
	}
	
	//循环获取到hash数据为止
	public function waitForTransaction($hash, $host = '', $timeout = 60, $interval = 1) {
		$cb = new Callback;
		$web3 = strlen($host) ? $this->createWeb3($host) : $this->web3;
		$time = time();
		while (true) {
			$web3->eth->getTransactionReceipt($hash, $cb);
			if ($cb->result) break;
			if (time() - $time > $timeout) break;
			sleep($interval);
		}
		return $cb->result;
	}
}

/*
使用
//$host = 'https://bsc-mainnet.web3api.com/v1/EPEYY8PIX22QS8MPUUURZ4573AH8JUIPAZ'; //主网
$host = 'https://bsc-dataseed1.binance.org';
$test_host = 'https://data-seed-prebsc-2-s1.binance.org:8545'; //测试网
//$model = new \app\model\Ethereum($host, 56);
$model = new \app\model\Ethereum($test_host, 97);
//dump($model);

$wallet = '0x3a861F5bC4957067Ea3CE04658104c8eA8fD4800';
$my_wallet = '0x2A2BdcF89f6C8F359e5BFFcec54FBcB7Bc8aFA66';

//获取区块详情
//$res = $model->getHashData('0xa18bb269cebd3baae1405ef7a82c61c00bf17a396e91342c827105ebe9d0339b');
//$num = $model->valueWithDecimals(hexdec($res['value']), 8);

$num = $model->getBalance($wallet, 8);
dump($num);

//获取余额
$num = $model->getBalance($my_wallet, 8);
dump($num);

//转账
//$res = $model->transfer($wallet, $my_wallet, 0.1);
//dump($res);

//获取余额
//$num = $model->getBalance($my_wallet, 8);
//halt($num);
*/
