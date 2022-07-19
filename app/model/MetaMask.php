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
	
	public function __construct($host, $chainId, $contract = 'contract', $addr = '') {
		$this->host = $host;
		$this->chainId = $chainId;
		$this->web3 = $this->createWeb3($host);
		$this->provider = $this->web3->provider;
		
		$this->contractPath = root_path() . 'contract';
		$this->keyPath = $this->contractPath . '/keystore';
		$abi = $this->contractPath . '/' . $contract . '.abi';
		if (!file_exists($abi)) exit('The abi file does not exist');
		$this->abi = file_get_contents($abi);
		if (!strlen($addr)) {
			$addr = $this->contractPath . '/' . $contract . '.addr';
			if (!file_exists($addr)) exit('The addr file does not exist');
			$this->addr = file_get_contents($addr);
		} else {
			$this->addr = $addr;
		}
		
		$this->contract = new Contract($this->provider, $this->abi);
		$this->contract->at($this->addr);
		
		$this->createSends();
	}
	
	//创建Web3
	public function createWeb3($host, $timeout = 60): Web3 {
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
	
	//创建sends方法
	private function createSends() {
		if (method_exists($this->contract, 'sends')) return;
		$contractFile = ROOT_PATH . '/vendor/sc0vu/web3.php/src/Contract.php';
		$content = file_get_contents($contractFile);
		preg_match('/(public function send\(\)[\s\S]+?})([\n\s\t]+\/\*\*)/', $content, $matcher);
		$code = $matcher[1] . PHP_EOL . PHP_EOL . "    " . str_replace('$this->eth->sendTransaction($transaction, function ($err, $transaction) use ($callback){
                if ($err !== null) {
                    return call_user_func($callback, $err, null);
                }
                return call_user_func($callback, null, $transaction);
            });', 'return $transaction;', str_replace('public function send()', 'public function sends()', $matcher[1]));
		$content = preg_replace('/(public function send\(\)[\s\S]+?})[\n\s\t]+\/\*\*/', $code . $matcher[2], $content);
		file_put_contents($contractFile, $content);
	}
	
	//获取余额
	public function getBalance($account, $scale = 8, $decimals = 0) {
		$cb = new Callback;
		try {
			$this->contract->call('balanceOf', $account, $cb); //合约方法
		} catch (\InvalidArgumentException $e) {
			$this->web3->eth->getBalance($account, 'latest', $cb); //RPC方法
		}
		if ($cb->result) {
			$result = json_decode(json_encode($cb->result), true);
			return floatval($this->decodeValue($result['value'] ?? $result[0]['value'], $scale, $decimals));
		}
		return 0;
	}
	
	//获取hash数据
	public function getHashData($hash, $host = '') {
		$cb = new Callback;
		$web3 = strlen($host) ? $this->createWeb3($host) : $this->web3;
		$web3->eth->getTransactionByHash($hash, $cb);
		if (!$cb->result) {
			if ($cb->error) {
				echo $cb->error->getMessage();
				exit;
			}
			exit("cb's error and result are null");
		}
		return json_decode(json_encode($cb->result), true);
	}
	
	//判断hash数据是否正确
	public function checkHashData($hash, $from, $to, $price = 0, $host = '') {
		$res = $this->getHashData($hash, $host);
		$priceCorrect = true;
		if (floatval($price) > 0) {
			$value = floatval($this->decodeValue(hexdec($res['value'])));
			$priceCorrect = $value != floatval($price);
		}
		return (strtolower($from) == strtolower($res['from']) && strtolower($to) == strtolower($res['to']) && $priceCorrect);
		/*{
		    "error": null,
		    "result": {
		        "blockHash": "0x0493a8469f5f8503c3cbcd27960f36788b7008eb047d71d6130406cfc32ade6d",
		        "blockNumber": "0x102de8f",
		        "from": "0x2a2bdcf89f6c8f359e5bffcec54fbcb7bc8afa66",
		        "gas": "0x613e",
		        "gasPrice": "0x3b9aca00",
		        "maxFeePerGas": "0x3b9aca00",
		        "maxPriorityFeePerGas": "0x3b9aca00",
		        "hash": "0xf79b4631a4e8b5e1dcd3458af9b16caea08ee450e4500f79df32c1684e2b3f6c",
		        "input": "0x",
		        "nonce": "0x1",
		        "to": "0xb2f103a37679ec8071ed06fcede0493c8dcc3cf7",
		        "transactionIndex": "0x0",
		        "value": "0x71afd498d0000",
		        "type": "0x2",
		        "accessList": [],
		        "chainId": "0x100",
		        "v": "0x0",
		        "r": "0x60f705fe78661027838f4116a9bdad0f9686a3829ffa92c43a31dd853eda147c",
		        "s": "0x7b5c29d2679b7f4b832c7a5a23ca7dd547ccbbb5883da5f637d2186a12e7c0c0"
		    }
		}*/
	}
	
	//获取精度
	public function getDecimals() {
		$decimals = 18;
		try {
			$cb = new Callback;
			$this->contract->call('decimals', $cb); //合约方法
			if ($cb->result && intval($cb->result[0]->value)) $decimals = $cb->result[0]->value;
		} catch (\Exception $e) {
			//
		}
		return intval($decimals);
	}
	
	//金额加精度转16进制
	public function encodeValue($value) {
		if (is_numeric($value)) {
			if (floatval($value) < 0) exit('Amount cannot be negative');
			$value = $this->decHex($this->decodeValue($value));
		}
		return $value;
	}
	
	//金额转10进制去精度
	public function decodeValue($value, $scale = 8, $decimals = 0) {
		if ($decimals <= 0) $decimals = $this->getDecimals();
		$mul = '1' . str_repeat('0', $decimals);
		return bcdiv(strval($value), $mul, $scale);
	}
	/*//金额转精度数值, 另一种方法
	public function pow($value, $scale = 8, $decimals = 0) {
		if ($decimals <= 0) $decimals = $this->getDecimals();
		return bcdiv(strval($value), strval(pow(10, intval($decimals))), $scale);
	}*/
	
	//10进制转16进制
	public function decHex($dec) {
		return Utils::toHex($dec, true);
	}
	
	//16进制转10进制
	public function hexDec($hex) {
		return hexdec($hex);
	}
	
	//获取gasPrice
	public function gasPrice() {
		$cb = new Callback;
		$this->web3->eth->gasPrice($cb);
		if (!$cb->result) {
			if ($cb->error) {
				echo $cb->error->getMessage();
				exit;
			}
			exit("cb's error and result are null");
		}
		$res = intval($cb->result->value);
		$gwei = ceil(floatval(bcdiv(strval($res), '1000000000')));
		return strval($gwei);
	}
	
	//执行并估算一个交易需要的gas用量
	public function estimateGas($row, $default_gas=1800000) {
		$cb = new Callback;
		$arr = array(
			'from' => $row['from'],
			'to' => $row['to'] ?? '',
			'data' => $row['data'] ?? ''
		);
		$this->web3->eth->estimateGas($arr, $cb);
		if (!$cb->result) {
			$res = $default_gas;
		} else {
			$res = intval($cb->result->value);
			if (!$res) $res = $default_gas;
		}
		return bcadd(bcmul(strval(ceil(floatval(bcdiv(strval($res), '10000', 8)))), '10000'), '10000');
	}
	
	//生成矿工费选项
	public function createGas($from, $gas=1800000) {
		return [
			'from' => $from,
			'gas' => Utils::toHex($gas, true)
		];
	}
	
	//生成交易数据
	public function transactionData($raw, $from, $to='', $value='', $default_gas=1800000) {
		$cb = new Callback;
		//返回指定地址发生的交易数量
		$this->web3->eth->getTransactionCount($from, 'pending', $cb);
		$nonce = $cb->result;
		if (!$nonce) {
			if ($cb->error) {
				echo $cb->error->getMessage();
				exit;
			}
			exit("cb's error and result are null");
		}
		$raw['from'] = $from;
		if ($to) $raw['to'] = $to;
		$raw['gasPrice'] = '0x' . Utils::toWei($this->gasPrice(), 'gwei')->toHex();
		$raw['gasLimit'] = Utils::toHex($this->estimateGas($raw, $default_gas), true);
		if ($value) $raw['value'] = $value;
		$raw['chainId'] = $this->chainId;
		$raw['nonce'] = Utils::toHex($nonce, true);
		//write_log($raw, $this->contractPath.'/log.txt');
		//获取签名
		$file = $this->keyPath . '/' . (substr($from, 0, 2) == '0x' ? substr(strtolower($from), 2) : $from) . '.json';
		if (!file_exists($file)) exit('The certificate file does not exist');
		$credential = Credential::fromWallet(env('wallet.password', 'passwordMetaMask'), $file);
		return $credential->signTransaction($raw);
	}
	
	//转账交易
	public function transfer($from, $to, $value) {
		$cb = new Callback;
		$value = $this->encodeValue($value);
		//生成交易数据, 需要在\Web3\Contract内复制一份send函数,然后直接返回$transaction
		$raw = $this->contract->sends('transfer', $to, $value, $this->createGas($from), $cb);
		//发起裸交易
		$this->web3->eth->sendRawTransaction($this->transactionData($raw, $from, $to, $value), $cb);
		if (!$cb->result) {
			if ($cb->error) {
				echo $cb->error->getMessage();
				exit;
			}
			exit("cb's error and result are null");
		}
		return $this->waitForTransaction($cb->result);
	}
	
	//循环获取到hash数据为止
	public function waitForTransaction($hash, $host = '', $timeout = 60, $interval = 1) {
		$cb = new Callback;
		$web3 = strlen($host) ? $this->createWeb3($host) : $this->web3;
		$time = time();
		while (true) {
			$web3->eth->getTransactionByHash($hash, $cb);
			if ($cb->result) break;
			if (time() - $time > $timeout) break;
			sleep($interval);
		}
		return json_decode(json_encode($cb->result), true);
	}
}

/*
使用
//$host = 'https://bsc-mainnet.web3api.com/v1/EPEYY8PIX22QS8MPUUURZ4573AH8JUIPAZ'; //主网
$host = 'https://bsc-dataseed1.binance.org';
$test_host = 'https://data-seed-prebsc-2-s1.binance.org:8545'; //测试网
//$model = new \app\model\MetaMask($host, 56);
$model = new \app\model\MetaMask($test_host, 97);
//dump($model);

$wallet = '0x3a861F5bC4957067Ea3CE04658104c8eA8fD4800';
$my_wallet = '0x2A2BdcF89f6C8F359e5BFFcec54FBcB7Bc8aFA66';

//获取区块详情
//$res = $model->getHashData('0xa18bb269cebd3baae1405ef7a82c61c00bf17a396e91342c827105ebe9d0339b');
//$num = $model->decodeValue(hexdec($res['value']), 8);

$num = $model->getBalance($wallet, 8);
dump($num);

$model->contract->call('test', function($err, $result) {
	if ($err !== null) {
		echo $err->getMessage();
		exit;
	}
	if (isset($result)) {
		//dump($result);
	}
});
*/
