<?php
/*
Developed by @mario v1.0.20221013
*/
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

//define('CURL_GFW', '127.0.0.1:7890');

class MetaMask {
	
	public $web3;
	public $provider;
	public $contract;
	public $host, $chainId;
	public $contractPath, $keyPath;
	public $password;
	public $abi, $addr;
	
	public function __construct($host, $chainId, $contract = 'contract', $addr = '') {
		$this->createDiy();
		
		$this->host = $host;
		$this->chainId = $chainId;
		$this->web3 = $this->createWeb3($host);
		$this->provider = $this->web3->provider;
		
		$this->contractPath = root_path() . 'contract';
		$this->keyPath = $this->contractPath . '/keystore';
		$abi = $this->contractPath . '/' . $contract . '.abi';
		if (!file_exists($abi)) error('The abi file does not exist');
		$this->abi = file_get_contents($abi);
		if (!strlen($addr)) {
			$addr = $this->contractPath . '/' . $contract . '.addr';
			if (!file_exists($addr)) error('The addr file does not exist');
			$this->addr = file_get_contents($addr);
		} else {
			$this->addr = $addr;
		}
		
		$this->password = env('wallet.password', "MetaMask's password");
		
		$this->contract = new Contract($this->provider, $this->abi);
		$this->contract->at($this->addr);
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
	private function createDiy() {
		$file = ROOT_PATH . '/vendor/guzzlehttp/guzzle/src/Handler/CurlFactory.php';
		$content = file_get_contents($file);
		if (strpos($content, 'if (defined("CURL_GFW")) {') === false) {
			preg_match('/(\\$conf\\[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;[^}]+})/', $content, $matcher);
			$code = $matcher[1] . PHP_EOL . PHP_EOL . '        if (defined("CURL_GFW")) {
		    $gfw = explode("@", CURL_GFW);
		    if (count($gfw) > 1) {
			    $account = $gfw[0];
			    $hp = explode(":", $gfw[1]);
		    } else {
			    $account = null;
			    $hp = explode(":", $gfw[0]);
		    }
		    $conf[CURLOPT_PROXY] = $hp[0];
		    $conf[CURLOPT_PROXYPORT] = count($hp) > 1 ? $hp[1] : "8080";
		    if ($account) $conf[CURLOPT_PROXYUSERPWD] = $account;
	    }';
			$content = preg_replace('/(\\$conf\\[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;[^}]+})/', $code, $content);
			file_put_contents($file, $content);
		}
		
		$file = ROOT_PATH . '/vendor/sc0vu/web3.php/src/RequestManagers/HttpRequestManager.php';
		$content = file_get_contents($file);
		if (strpos($content, '$this->client = new Client(["verify" => false]);') === false) {
			$content = str_replace('$this->client = new Client;', '$this->client = new Client(["verify" => false]);', $content);
			file_put_contents($file, $content);
		}
		
		$file = ROOT_PATH . '/vendor/sc0vu/web3.php/src/Contract.php';
		$content = file_get_contents($file);
		if (strpos($content, 'public function sends()') === false) {
			preg_match('/(public function send\(\)[\s\S]+?})([\n\s\t]+\/\*\*)/', $content, $matcher);
			$code = $matcher[1] . PHP_EOL . PHP_EOL . "    " . str_replace('$this->eth->sendTransaction($transaction, function ($err, $transaction) use ($callback){
                if ($err !== null) {
                    return call_user_func($callback, $err, null);
                }
                return call_user_func($callback, null, $transaction);
            });', 'return $transaction;', str_replace('public function send()', 'public function sends()', $matcher[1]));
			$content = preg_replace('/(public function send\(\)[\s\S]+?})[\n\s\t]+\/\*\*/', $code . $matcher[2], $content);
			file_put_contents($file, $content);
		}
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
			$res = $this->jsonToArray($cb->result);
			return floatval($this->decodeValue($res['value'] ?? $res[0]['value'], $scale, $decimals));
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
				error($cb->error->getMessage());
			}
			error("cb's error and result are null");
		}
		return $this->jsonToArray($cb->result);
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
		        "input": "0xb6b55f250000000000000000000000000000000000000000000000008ac7230489e80000",
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
	
	//解析hash数据的input参数, 返回数组, [from, to, ...param]
	public function checkHashData($hash, $host = '') {
		$res = $this->getHashData($hash, $host);
		$data = $this->decodeInputData($res['input']);
		array_unshift($data, $res['from'], $res['to']);
		return $data;
		//16进制转10进制且去精度
		//floatval($this->decodeValue(enumToStr(hexdec($data[0]))))
	}
	
	//获取hash数据, 严格判断
	public function getHashOrder($hash, $host = '') {
		$cb = new Callback;
		$web3 = strlen($host) ? $this->createWeb3($host) : $this->web3;
		$web3->eth->getTransactionReceipt($hash, $cb);
		if (!$cb->result) {
			if ($cb->error) {
				error($cb->error->getMessage());
			}
			error("cb's error and result are null");
		}
		$res = $this->jsonToArray($cb->result);
		return intval(hexdec($res['status'])) === 1;
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
	
	//解析hash里的inputData, offset以太坊10(0x+8)(波场8)
	public function decodeInputData($input, $offset = 10){
		$str = substr($input, $offset, strlen($input) - $offset); //0x+8个字母是方法名，剩余的是输入数据
		$input_num = ceil(floatval(bcdiv(strval(strlen($str)), '64', 2))); //参数个数
		//去除前导0
		$deleteZero = function($str) {
			for ($i = 0; $i < strlen($str); $i++) {
				if($str[$i] !== '0'){
					return $i;
				}
			}
			return strlen($str);
		};
		$data = [];
		for ($i = 0; $i < $input_num; $i++) {
			$param = substr($str, $i * 64, 64);
			$first_num = $deleteZero($param);
			$data[$i] = substr($param, $first_num, strlen($param) - $first_num);
		}
		return $data;
	}
	
	//获取精度
	public function getDecimals() {
		$decimals = 18;
		try {
			$cb = new Callback;
			$this->contract->call('decimals', $cb); //合约方法
			if ($cb->result) {
				$res = $this->jsonToArray($cb->result);
				if (intval($res[0]['value'])) $decimals = $res[0]['value'];
			}
		} catch (\Exception $e) {
			//
		}
		return intval($decimals);
	}
	
	//转数组
	public function jsonToArray($result) {
		return Utils::jsonToArray($result);
	}
	
	//上链金额检测(上链时必须加精度)
	public function setValue($value, $add_decimals=true) {
		if ($add_decimals) {
			if (strpos(strval($value), '.') !== false || strlen(strval($value)) < 8) $value = $this->encodeValue($value);
		} else {
			if (strpos(strval($value), '.') === false && strlen(strval($value)) > 8) $value = $this->decodeValue($value);
		}
		return $value;
	}
	
	//金额加精度
	public function encodeValue($value, $decimals = 0) {
		if (is_numeric($value)) {
			if (floatval($value) < 0) error('Amount cannot be negative');
			if ($decimals <= 0) $decimals = $this->getDecimals();
			$value = bcmul(strval($value), '1'.str_repeat('0', $decimals));
		}
		return $value;
	}
	
	//金额去精度
	public function decodeValue($value, $scale = 8, $decimals = 0) {
		if ($decimals <= 0) $decimals = $this->getDecimals();
		return bcdiv(strval($value), '1'.str_repeat('0', $decimals), $scale);
		//另一种方法
		//return bcdiv(strval($value), strval(pow(10, intval($decimals))), $scale);
	}
	
	//10进制转16进制
	public function decHex($dec) {
		return Utils::toHex($dec, true);
	}
	
	//16进制转10进制
	public function hexDec($hex) {
		return hexdec($hex);
	}
	
	//获取gasPrice
	public function gasPrice($default_gwei=1) {
		$cb = new Callback;
		$this->web3->eth->gasPrice($cb);
		if (!$cb->result) {
			if ($cb->error) {
				error($cb->error->getMessage());
			}
			error("cb's error and result are null");
		}
		$res = $this->jsonToArray($cb->result);
		$gwei = intval($res['value']);
		$default_gwei = intval(bcmul(strval($default_gwei), '1'.str_repeat('0', 9)));
		if ($gwei < $default_gwei) $gwei = $default_gwei;
		return bcdiv(strval($gwei), '1'.str_repeat('0', 9), 2);
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
			$res = $this->jsonToArray($cb->result);
			$res = intval($res['value']);
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
	public function transactionData($raw, $from, $to='', $default_gas=1800000, $default_gwei=1) {
		$cb = new Callback;
		//返回指定地址发生的交易数量
		$this->web3->eth->getTransactionCount($from, 'pending', $cb);
		$nonce = $cb->result;
		if (!$nonce) {
			if ($cb->error) {
				error($cb->error->getMessage());
			}
			error("cb's error and result are null");
		}
		$raw['from'] = $from;
		if (strlen($to)) $raw['to'] = $to; //存在to即是走链通道, 不存在即是走合约通道
		$raw['gasPrice'] = '0x' . Utils::toWei($this->gasPrice($default_gwei), 'gwei')->toHex();
		$raw['gasLimit'] = Utils::toHex($this->estimateGas($raw, $default_gas), true);
		$raw['chainId'] = $this->chainId;
		$raw['nonce'] = Utils::toHex($nonce, true);
		//write_log($raw, $this->contractPath.'/log.txt');
		//获取签名
		$file = $this->keyPath . '/' . (substr($from, 0, 2) === '0x' ? substr(strtolower($from), 2) : $from) . '.json';
		if (!file_exists($file)) error('The certificate file does not exist');
		$credential = Credential::fromWallet($this->password, $file);
		return $credential->signTransaction($raw);
	}
	
	//转账交易
	public function transfer($from, $to, $value) {
		$cb = new Callback;
		//生成交易数据, 需要在\Web3\Contract内复制一份send函数,然后直接返回$transaction
		$raw = $this->contract->sends('transfer', $to, $this->setValue($value), $this->createGas($from), $cb);
		//发起裸交易
		$this->web3->eth->sendRawTransaction($this->transactionData($raw, $from), $cb);
		if (!$cb->result) {
			if ($cb->error) {
				error($cb->error->getMessage());
			}
			error("cb's error and result are null");
		}
		//return $this->waitForTransaction($cb->result);
		return $cb->result;
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
	if (!$result) {
		if ($err) {
			error($err->getMessage());
		}
		error("cb's error and result are null");
	}
	//dump($result);
});
*/
