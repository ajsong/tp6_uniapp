<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class ManageLog extends Model
{
	//类型
	public static function getTypes($type = '') {
		$list = [
			'path' => '访问路径',
			'insert' => '插入数据',
			'update' => '更新数据',
			'delete' => '删除数据',
		];
		if (!$type) return $list;
		return $list[$type] ?? '';
	}
	
	public static function add($type = 'path', $remark = []) {
		if (\app\model\Config::get('manage_log') == 0) return;
		$manage = session('manage');
		if (!$manage || $manage['super'] == 1) return;
		$content = $remark;
		if ($type != 'path' && !is_string($remark)) {
			if ($remark instanceof \think\model\Collection) {
				$content = json_encode($remark->toArray(), JSON_UNESCAPED_UNICODE);
			} else {
				$content = json_encode($remark, JSON_UNESCAPED_UNICODE);
			}
		}
		self::create([
			'manage_id' => $manage['id'],
			'manage_name' => $manage['name'],
			'app' => CONTROLLER_NAME,
			'act' => ACTION_NAME,
			'type' => $type,
			'ip' => request()->ip(),
			'remark' => $type == 'path' ? request()->url() : $content,
			'add_time' => time(),
		]);
	}
}
