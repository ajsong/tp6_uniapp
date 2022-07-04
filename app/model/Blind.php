<?php
declare (strict_types = 1);

namespace app\model;

use think\facade\Cache;
use think\Model;

/**
 * @mixin \think\Model
 */
class Blind extends Core
{
	public static function getBoxData($refresh = false) {
		if ($refresh) Cache::delete('blind:data');
		$res = Cache::remember('blind:data', function() {
			$row = self::order('id')->find();
			$static_day = explode(',', $row['static_day']??''); //天数
			$static_percent = explode(',', $row['static_percent']??''); //收益率
			$static_rate = explode(',', $row['static_rate']??''); //开奖率
			$static_count = explode(',', $row['static_count']??''); //个数
			$static_pic = explode(',', $row['static_pic']??''); //图片
			$share_percent = explode(',', $row['share_percent']??''); //直推奖(分享收益)
			
			$statics = [];
			for ($i = 0; $i < count($static_day); $i++) {
				$day = $static_day[$i];
				$percent = count($static_percent)>$i ? $static_percent[$i] : '';
				$rate = count($static_rate)>$i ? $static_rate[$i] : '';
				$count = count($static_count)>$i ? $static_count[$i] : '';
				$pic = count($static_pic)>$i ? $static_pic[$i] : '';
				$statics[] = ['day' => $day, 'percent' => $percent, 'rate' => $rate, 'count' => $count, 'pic' => $pic];
			}
			
			$shares = [];
			for ($i = 0; $i < count($share_percent); $i++) {
				$percent = $share_percent[$i];
				$shares[] = $percent;
			}
			
			return json_encode([
				'price_min' => $row['price_min'],
				'price_max' => $row['price_max'],
				'static_sure' => intval($row['static_sure']),
				'statics' => $statics,
				'shares' => $shares,
			]);
		});
		return json_decode($res, true);
	}
	
	public static function numToHan($num) {
		$arr1 = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
		$arr2 = ['', '十', '百', '千', '万', '十', '百', '千', '亿', '十', '百', '千', '万', '十', '百', '千', '亿']; //可继续追加更高位转换值
		if (!$num || !is_numeric($num)) return '零';
		$english = str_split(strval($num));
		$result = '';
		for ($i = 0; $i < count($english); $i++) {
			$des_i = count($english) - 1 - $i; //倒序排列设值
			$result = $arr2[$i] . $result;
			$arr1_index = $english[$des_i];
			$result = $arr1[$arr1_index] . $result;
		}
		$result = preg_replace('/十零/', '十', preg_replace('/零[千百十]/', '零', $result)); //将【零千、零百】换成【零】 【十零】换成【十】
		$result = preg_replace('/零+/', '零', $result); //合并中间多个零为一个零
		$result = preg_replace('/零万/', '万', preg_replace('/零亿/', '亿', $result)); //将【零亿】换成【亿】【零万】换成【万】
		$result = preg_replace('/亿万/', '亿', $result); //将【亿万】换成【亿】
		$result = preg_replace('/零+$/', '', $result); //移除末尾的零
		//$result = preg_replace('/零一十/', '零十', $result);////将【零一十】换成【零十】，貌似正规读法是零一十
		$result = preg_replace('/^一十/', '十', $result); //将【一十】换成【十】
		return $result;
	}
}
