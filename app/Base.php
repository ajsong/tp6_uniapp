<?php
declare (strict_types = 1);

namespace app;

use think\exception\HttpResponseException;

define('ROOT_PATH', rtrim(root_path(), '/'));
define('PUBLIC_PATH', rtrim(public_path(), '/'));
define('RUNTIME_PATH', rtrim(dirname(runtime_path()), '/'));
define('UPLOAD_PATH', config('filesystem.disks.public.url'));
define('IS_POST', request()->isPost());
define('IS_AJAX', (request()->isAjax() || request()->get('output') == 'json'));
define('MODULE_NAME', strtolower(app('http')->getName()));
define('CONTROLLER_NAME', strtolower(request()->controller()));
define('ACTION_NAME', strtolower(request()->action()));

class Base extends BaseController {
	
	public $app, $act;
	public $ip;
	
	public function __construct() {
		parent::__construct(app());
		$this->app = CONTROLLER_NAME;
		$this->act = ACTION_NAME;
		$this->ip = $this->request->ip();
	}
	
	//重定向
	public function redirect($url, $code = 302) {
		throw new HttpResponseException(redirect($url, $code));
	}
	
	//TP5查询条件转TP6格式, 如 $where['field'] = ['>=', '10'];
	public function whereArr($where): array {
		$newWhere = [];
		foreach ($where as $field => $value) {
			if (is_array($value)) {
				if (count($value) > 1) $newWhere[] = [$field, $value[0], $value[1]];
			}
			else $newWhere[] = [$field, '=', $value];
		}
		return $newWhere;
	}
	
	//组合两端加标识包裹的LIKE条件
	public function whereWrap($field, $value, $delimiter=','): string {
		return "CONCAT('$delimiter',$field,'$delimiter') LIKE '%$delimiter$value$delimiter%'";
	}
	
	//构建paginate数据
	public function paginateArr($pagesize = 15): array {
		return [
			'list_rows' => $pagesize,
			'query' => $this->request->param()
		];
	}
	
	//动态设置/获取属性
	//当设置的属性不存在或者不可访问(private)时就会调用此函数
	public function __set($name, $value) {
		$name = uncamelize($name);
		$array = explode('_', $name);
		if (count($array) < 2) return;
		$object = $array[0];
		$property = substr($name, strlen($object) + 1);
		$session = session($object);
		if (!$session) $session = [];
		$session[$property] = $value;
		session($object, $session);
	}
	//当获取的属性不存在或者不可访问(private)时就会调用此函数
	public function __get($name) {
		$name = uncamelize($name);
		$array = explode('_', $name);
		if (count($array) < 2) return null;
		$object = $array[0];
		$property = substr($name, strlen($object) + 1);
		$session = session($object);
		if (!$session) return null;
		return $session[$property] ?? null;
	}
	
}
