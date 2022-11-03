<?php
namespace Udun\Dispatch;
use Hanson\Foundation\AbstractAPI;
class Api extends AbstractAPI
{
    //商户号
    protected $merchant_no;   
    //apikey      
    protected $api_key;  
    //节点地址           
    protected $gateway_address;
    //回调地址     
    protected $callUrl;             
    public function __construct( $merchant_no, string $api_key,string $gateway_address, string $callUrl)
    {
        $this->merchant_no = $merchant_no;
        $this->api_key = $api_key;
        $this->gateway_address = $gateway_address;
        $this->callUrl = $callUrl;
    }

 
    /**
     * @param string $method  
     * @param array $params
     * @return result
     * @throws UdunDispatchException
     */
    public function request(string $method, array $body)
    {
    	$time = time();
    	$nonce = rand(100000, 999999);
        if($method=='/mch/support-coins'){
            $body = json_encode($body);
        }else{
            $body = '['.json_encode($body).']';
        }
        
        $sign = $this->signature($body,$time,$nonce);
        $params = array(
        	'timestamp' => $time,
            'nonce' => $nonce,
            'sign' => $sign,
            'body' => $body
        );
        $data_string = json_encode($params);
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $this->gateway_address.$method);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8", "Content-Length: ".strlen($data_string)));
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	    $result = curl_exec($ch);
	    curl_close($ch);
	    $result = json_decode($result, true);
        $this->checkErrorAndThrow($result);
        return $result;
    }

    public function signature($body,$time,$nonce)
    {
        return md5($body.$this->api_key.$nonce.$time);
    }

    /**
     * @param $result
     * @throws UdunDispatchException
     */
    private function checkErrorAndThrow($result)
    {
        if (!$result || $result['code'] != 200) {
            throw new UdunDispatchException($result['code'], $result['message']);
        }
    }
}

?>