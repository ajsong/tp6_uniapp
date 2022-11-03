<?php
/*
Developed by @mario v1.0.20221013
*/
declare (strict_types = 1);

//波宝钱包, 全部接口每日累加最多10万次查询
//https://cn.developers.tron.network/reference/validateaddress
//线上哈希查询订单是否完成
//https://tronscan.io/#/

namespace app\model;


class TronLink {
	
	//判断钱包地址是否正确
	public static function checkAddress($address) {
		$res = requestUrl('post', 'https://api.trongrid.io/wallet/validateaddress', '{"address":"'.$address.'"}', true, true);
		return $res['result'];
	}
	
	//检查交易是否完成
	public static function checkHash($hash) {
		$res = requestUrl('post', 'https://api.trongrid.io/wallet/gettransactionbyid', '{"value":"'.$hash.'"}', true, true);
		return $res['ret'][0]['contractRet'] == 'SUCCESS';
	}
}
