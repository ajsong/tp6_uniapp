<?php
return [
	//图片域名, 为空即使用当前域名
	'img_domain' => '',
	//前台检测登录状态controller, action, 星号为不限制
	'not_check_login' => [
		'index' => ['index', 'rule', 'counter'],
		'article' => ['index', 'detail'],
		'passport' => ['*'],
		'cron' => ['*'],
		'other' => ['*'],
		'blind' => ['*'],
	],
	//多端登录
	'multi_terminal' => 0,
	//允许跨域请求的controller, action, 星号为不限制
	'access_allow' => ['*'],
	//允许跨域请求的外站域名, 如 http://localhost:8080, 星号为不限制
	'access_allow_host' => ['*'],
	//CRON定时任务可执行IP白名单, 星号不限制
	'cron_allow_ip' => ['127.0.0.1', '::1', '*'],
	//多语言映射
	'language' => [
		'zh-Hans' => ['code'=>1, 'file'=>'zh-cn'],
		'zh-Hant' => ['code'=>2, 'file'=>'zh-tw'],
		'en' => ['code'=>3, 'file'=>'en-us']
	],
	//收款地址
	'client_wallet' => '0xbe32076F2DAB43435BFfC3155574cb9DAA97092C',
];
