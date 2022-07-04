<?php
/*
Developed by @mario v2.0.20220527
*/

//命令行运行
//php think websocket 7999

//ThinkPHP 自定义指令
//https://www.kancloud.cn/manual/thinkphp5_1/354146
//https://www.kancloud.cn/manual/thinkphp6_0/1037651
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

require_once 'WebSocket/Server.php';

class WebSocket extends Command {
    private $host = '0.0.0.0';
    private $port = 7999;

    protected function configure() {
        $this->setName('websocket')->addArgument('port', Argument::OPTIONAL, 'port')->setDescription('WebSocket');
        //->addArgument('name', Argument::OPTIONAL, "your name")
        //->addOption('city', null, Option::VALUE_REQUIRED, 'city name')
        //->setDescription('Say Hello')
    }

    protected function execute(Input $input, Output $output) {
        $port = trim($input->getArgument('port'));
        if ($port) $this->port = intval($port);
        $this->runSocket($this->host, $this->port);

        /*$name = trim($input->getArgument('name'));
          $name = $name ?: 'thinkphp';
          if ($input->hasOption('city')) {
            $city = PHP_EOL . 'From ' . $input->getOption('city');
        } else {
            $city = '';
        }
        $output->writeln("Hello," . $name . '!' . $city);*/

        //php think hello # Hello thinkphp!
        //php think hello liuchen # Hello liuchen!
        //php think hello liuchen --city shanghai # Hello liuchen! From shanghai
    }

    //public function __construct($host, $port) {
    public function runSocket($host, $port) {
	    $server = new \WebSocket\Server("ws://{$host}:{$port}");
	    /*$server->onTick(function ($round, $server) {
	        if ($round % 200 == 0) {
	            //200 * usleep(5000)的5000 = 1000000(1秒)
	            list($t1, $t2) = explode(' ', microtime());
                $msectime = intval(sprintf('%.0f',(floatval($t1)+floatval($t2))*1000));
                foreach ($server->getClients() as $client) {
	       	        $server->send($client['socket'], json_encode(['ping' => $msectime]));
        	    }
	        }
	    });*/
	    $server->onMessage(function ($sender, $message, $server) {
	        //$this->debug($message);
		    $recvMsg = json_decode($message, true);
		    switch ($recvMsg['type']) {
                case 'huobi_index':
                    $listTop = [];
                    $listBottom = [];
    
                    $arr = ['btc', 'xrp', 'ltc', 'bch', 'eth'];
                    foreach ($arr as $g) {
                        $detail_all = $this->curl_get_https("https://api.huobi.pro/market/detail/merged?symbol={$g}usdt");
                        $detail = $detail_all['tick'];
                        $a = bcsub($detail['close'], $detail['open'], 4);
                        $b = bcdiv($a, $detail['open'], 4);
                        $upOrDowm = bcmul($b, '100', 2);
                        $data_detail = [
                            'money_name' => strtoupper($g),
                            'usdt' => $detail['close'],
                            'volume_usdt' => $detail['amount'],
                            'day_max' => $detail['high'],
                            'day_min' => $detail['low'],
                            'cny' => bcmul($detail['close'], $recvMsg['cny'], 4),
                            'change' => $upOrDowm,
                            //'create_time' => time()
                        ];
    
                        if($g!='btc'){
                            $listBottom[] = $data_detail;
                        }else{
                            $listTop[] = $data_detail;
                        }
                    }
    
                    $detail_all = Db::name('currency_list1')->order('id','desc')->limit(1)->find();
                    $a2 = bcsub($detail_all['close'], $detail_all['open'], 4);
                    $b2 = bcdiv($a2, $detail_all['open'], 4);
                    $upOrDowm2 = bcmul($b2, '100', 2);

                    $listTop[] = [
                        'id' => 1,
                        'money_name' => 'TNS',
                        'usdt' => $detail_all['close'],
                        'volume_usdt' => $detail_all['amount'],
                        'cny' => bcmul($detail_all['close'], $recvMsg['cny'], 4),
                        'change' => $upOrDowm2,
                    ];
    
                    $list = compact('listTop', 'listBottom');
                    $list = ['type'=>'index', 'info'=>$list];
    
                    $msg = json_encode($list);
                    $server->send($sender['socket'], $msg);
                    break;
                case 'huobi_current':
                    $detail_all = $this->curl_get_https('https://api.huobi.pro/market/detail/merged?symbol='.$recvMsg['name'].'usdt');
                    $detail = $detail_all['tick'];
                    $res = array('type'=>'current', 'num'=>$detail['close']);
                    $msg = json_encode($res);
                    $server->send($sender['socket'], $msg);
                    break;
                case 'huobi_deal':
                    $sellOrderModel = new SellOrder();
                    $info1 = $sellOrderModel->where(['types'=>1,'status'=>1,'sort'=>$recvMsg['sort']])
                        ->field('price,thennumber')
                        ->order('price desc')
                        ->limit(5)
                        ->select();
                    $info2 = $sellOrderModel->where(['types'=>2,'status'=>1,'sort'=>$recvMsg['sort']])
                        ->field('price,thennumber')
                        ->order('price asc')
                        ->limit(5)
                        ->select();
    
                    $list = compact('info1', 'info2');
                    $list = ['type'=>'deal', 'info'=>$list];
    
                    $msg = json_encode($list);
                    $server->send($sender['socket'], $msg);
                    break;
                case 'huobi_kline':
    	            $name = strval($recvMsg['name']); //币名
                    $period = strval($recvMsg['period']); //周期
                    if($name!='tns'){
                        $detail_all = $this->curl_get_https("https://api.huobi.pro/market/history/kline?symbol={$name}usdt&period={$period}");
                    }else{
                        
                        $detail_all = Db::name('currency_list1')
                            ->where('time',$period)
                            ->field('vol,open,close,high,low,create_time as id,0 as count,amount')
                            ->limit(150)
                            ->order('id','desc')
                            ->select();
                        
                        // $detail_all = Proportion::field('id,day_max as high,day_min as low,31501.12 as open,31520.58 as close,10.635923316330846 as amount,335019.69295757986 as vol,819 as count')
                        //     ->limit(150)
                        //     ->order('id','desc')
                        //     ->select();

//                        $max = $recvMsg['day_max'];
//                        $min = $recvMsg['day_min'];
//
//                        for($i=0;$i<150;$i++) {
//                            $numMin = $this->random_float($min, $max, 4);
//                            $numMax = $this->random_float($numMin, $max, 4);
//
//                            $data = [
//                                [
//                                    "id" => 1652247540,
//                                    "open" => 31501.12,
//                                    "close" => 31520.58,
//                                    "low" => floatval($numMin),
//                                    "high" => floatval($numMax),
//                                    "amount" => 10.635923316330846,
//                                    "vol" => 335019.69295757986,
//                                    "count" => 819
//                                ]
//                            ];
//                        }

//                        $detail_all = compact('data');
                        
    
                        // $max = $recvMsg['day_max'];
                        // $min = $recvMsg['day_min'];
                        
                        // for($i=0;$i<150;$i++) {
                        //     $numMin = $this->random_float($min, $max, 4);
                        //     $numMax = $this->random_float($numMin, $max, 4);

                        //     $data = [
                        //         [
                        //             "id" => 1652247540,
                        //             "open" => 31501.12,
                        //             "close" => 31520.58,
                        //             "low" => floatval($numMin),
                        //             "high" => floatval($numMax),
                        //             "amount" => 10.635923316330846,
                        //             "vol" => 335019.69295757986,
                        //             "count" => 819
                        //         ]
                        //     ];
                        // }
    
                        // $detail_all = compact('data');
                    }
                    
                    $msg = json_encode($detail_all);
                    $server->send($sender['socket'], $msg);
    	            break;
		    }
	    });
	    $server->run();
    }
	
    public function curl_get_https($url) {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        $tmpInfo = curl_exec($curl);     //返回api的json对象
        //关闭URL请求
        curl_close($curl);
	    return json_decode($tmpInfo, true);    //返回json对象
    }
    
    public function random_float($min=0, $max=1, $length=2) {
        $num =  $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return substr(strval($num), 0, $length+2);
    }
    
    /**
     * 记录debug信息
     *
     * @param $info
     */
    private function debug($info) {
        $file = dirname(realpath(__FILE__)).'/debug.txt';
        if (!is_string($info)) $info = json_encode($info);
        file_put_contents($file, date('Y-m-d H:i:s').PHP_EOL.$info.PHP_EOL.'=============================='.PHP_EOL.PHP_EOL, FILE_APPEND);
    }
    
    /**
     * 记录错误信息
     *
     * @param $info
     */
    private function error($info) {
        $file = dirname(realpath(__FILE__)).'/error.txt';
        if (!is_string($info)) $info = json_encode($info);
        file_put_contents($file, date('Y-m-d H:i:s').PHP_EOL.$info.PHP_EOL.'=============================='.PHP_EOL.PHP_EOL, FILE_APPEND);
    }
    
}

//$ws = new WebSocket('0.0.0.0', '7999'); //要绑定的网址,端口
