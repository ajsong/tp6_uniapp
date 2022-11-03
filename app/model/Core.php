<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Core extends Model {
	//例如把 MoneyLog::getMoneyType 转二维数组, {"3":"TRC20", "4":"ERC20"} => [{"id":"3", "name":"TRC20"}, {"id":"4", "name":"ERC20"}]
	public static function keyValueToTDArray($array, $key_name, $value_name) {
		if (!is_array($array) || !count($array)) return null;
		$res = [];
		$keys = array_keys($array);
		$vals = array_values($array);
		foreach ($keys as $i => $key) {
			$res[] = ["$key_name"=>$key, "$value_name"=>$vals[$i]];
		}
		return $res;
	}
	
	public static function createAll($data, $loop_key = '') {
		$loop_value = NULL;
		if (strlen($loop_key)) {
			$loop_value = $data[$loop_key] ?? NULL;
			if (!is_array($loop_value)) $loop_key = '';
		}
		if (!$loop_key) {
			return self::insert($data);
		}
		$insertData = [];
		foreach ($loop_value as $item) {
			$row = [
				"$loop_key" => $item
			];
			foreach ($data as $key => $value) {
				if ($key == $loop_key) continue;
				$row[$key] = $value;
			}
			$insertData[] = $row;
		}
		$clazz = get_called_class();
		return (new $clazz())->saveAll($insertData);
	}
	
	protected static function onBeforeWrite($model) {
		if (MODULE_NAME != 'admin') return;
		if (\app\model\Config::get('manage_log') == 0) return;
		$manage = session('manage');
		if (!$manage || $manage['super'] == 1) return;
		if ($model->getWhere()) {
			$type = 'update';
			$clazz = explode('\\', get_called_class());
			$field = array_keys($model->getData());
			array_unshift($field, 'id');
			$res = m(end($clazz))::where($model->getWhere())->field($field)->find();
		} else {
			$type = 'insert';
			$res = $model->getData();
		}
		ManageLog::add($type, $res);
	}
	
	protected static function onBeforeDelete($model) {
		if (MODULE_NAME != 'admin') return;
		if (\app\model\Config::get('manage_log') == 0) return;
		$manage = session('manage');
		if (!$manage || $manage['super'] == 1) return;
		ManageLog::add('delete', $model->getData());
	}
}
