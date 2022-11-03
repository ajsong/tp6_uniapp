-- Adminer 4.8.1 MySQL 5.7.34 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `pf_article`;
CREATE TABLE `pf_article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL COMMENT '文章标题',
  `memo` varchar(100) DEFAULT NULL COMMENT '摘要',
  `content` text COMMENT '内容',
  `pic` varchar(1000) DEFAULT NULL COMMENT '主图',
  `category_id` int(11) DEFAULT '0' COMMENT '所属分类',
  `clicks` int(11) DEFAULT '0' COMMENT '点击数',
  `likes` int(11) DEFAULT '0' COMMENT '点赞人数',
  `comments` int(11) DEFAULT '0' COMMENT '评论人数',
  `ext_property` varchar(50) DEFAULT NULL COMMENT '扩展属性，逗号隔开：1推荐，2热门',
  `member_id` int(11) DEFAULT '0' COMMENT '发表人ID',
  `mark` varchar(50) DEFAULT NULL COMMENT '系统文章(不可删除)',
  `status` int(11) DEFAULT '1' COMMENT '状态，0隐藏，1正常',
  `sort` int(11) DEFAULT '999' COMMENT '数字越小，排在越前',
  `add_time` int(11) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC COMMENT='文章表';

INSERT INTO `pf_article` (`id`, `title`, `memo`, `content`, `pic`, `category_id`, `clicks`, `likes`, `comments`, `ext_property`, `member_id`, `mark`, `status`, `sort`, `add_time`) VALUES
(1,	'公告的标题',	NULL,	'<p>公告的内容</p>',	NULL,	1,	0,	0,	0,	NULL,	0,	NULL,	1,	999,	1660282648);

DROP TABLE IF EXISTS `pf_article_category`;
CREATE TABLE `pf_article_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL COMMENT '名称',
  `status` int(11) DEFAULT '1' COMMENT '状态',
  `sort` int(11) DEFAULT '999' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文章分类';

INSERT INTO `pf_article_category` (`id`, `name`, `status`, `sort`) VALUES
(1,	'最新公告',	1,	999);

DROP TABLE IF EXISTS `pf_banner`;
CREATE TABLE `pf_banner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL COMMENT '广告名称',
  `content` varchar(250) DEFAULT NULL COMMENT '可以是id或者链接或者关键词',
  `type` varchar(50) DEFAULT NULL COMMENT 'html5网页，goods产品，article文章，type一级分类，subtype二级分类，coupon优惠券，recharge充值，register注册',
  `pic` varchar(1000) DEFAULT NULL COMMENT '广告图片',
  `pic3` varchar(1000) DEFAULT NULL COMMENT '广告图片(英文)',
  `position` varchar(50) DEFAULT 'flash' COMMENT '广告位置，默认是首页轮播图flash',
  `channel` int(11) DEFAULT '0' COMMENT '投放渠道，0全渠道，1苹果+安卓，2苹果，3安卓，4微信，5web',
  `status` int(11) DEFAULT '1' COMMENT '状态',
  `sort` int(11) DEFAULT '999' COMMENT '数字越小，排在越前',
  `begin_time` int(11) DEFAULT '0' COMMENT '广告开始时间',
  `end_time` int(11) DEFAULT '0' COMMENT '广告结束时间',
  `add_time` int(11) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='广告表';

INSERT INTO `pf_banner` (`id`, `name`, `content`, `type`, `pic`, `pic3`, `position`, `channel`, `status`, `sort`, `begin_time`, `end_time`, `add_time`) VALUES
(1,	'广告图',	'',	'',	'/uploads/pic/2022/08/12/22081214362278915.jpg',	NULL,	'flash',	0,	1,	999,	0,	0,	1660286183);

DROP TABLE IF EXISTS `pf_config`;
CREATE TABLE `pf_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `memo` varchar(200) DEFAULT NULL COMMENT '参数说明',
  `key` varchar(50) DEFAULT NULL COMMENT '参数标识',
  `value` text COMMENT '参数值',
  `type` varchar(1000) DEFAULT NULL COMMENT '参数填写方式，[text(默认)|number|password|date|color|textarea]|占位符|附加attr，[radio|checkbox|select|switch]|值1:字1#值2:字2|附加attr，checkbox-app|文字|附加attr，file|可上传后缀(逗号隔开,默认图片)|附加attr',
  `status` int(11) DEFAULT '1' COMMENT '0隐藏，1正常',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='系统配置';

INSERT INTO `pf_config` (`id`, `memo`, `key`, `value`, `type`, `status`) VALUES
(1,	'后台日志记录，0关闭，1开启',	'manage_log',	'0',	'checkbox-app|开启',	0),
(2,	'测试开关 (打开后不调用钱包)',	'app_debug',	'0',	'checkbox-app|开启',	0),
(3,	'优惠券的限制商品改为限制分类',	'coupon_permit_category',	'0',	'checkbox-app|切换',	0),
(4,	'商品图册数量',	'goods_image_num',	'9',	'number',	0),
(5,	'商品规格项目数量',	'goods_spec_num',	'3',	'number',	0),
(6,	'商品分利层级数量',	'goods_commission_num',	'3',	'number',	0),
(7,	'订单退货退款地址',	'order_refund_address',	'广东广州市天河区',	'',	0),
(50,	'用户协议',	'agreement',	'用户协议的内容',	'textarea',	1),
(51,	'提现手续费 (带百分号即按百分比计算)',	'withdraw_fee',	'20%',	'',	1),
(52,	'最低提现金额',	'withdraw_least',	'20',	'number',	1),
(53,	'提现说明',	'withdraw_memo',	'提现的说明内容',	'textarea',	1),
(54,	'充币地址',	'recharge_address',	'',	'text',	1),
(55,	'充值说明',	'recharge_memo',	'充值的说明内容',	'textarea',	1);

DROP TABLE IF EXISTS `pf_feedback`;
CREATE TABLE `pf_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) DEFAULT '0' COMMENT '会员id',
  `account` varchar(20) DEFAULT NULL COMMENT '外网账号',
  `content` varchar(500) DEFAULT NULL COMMENT '内容',
  `reply` varchar(500) DEFAULT NULL COMMENT '回复',
  `add_time` int(11) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='意见反馈';

INSERT INTO `pf_feedback` (`id`, `member_id`, `account`, `content`, `reply`, `add_time`) VALUES
(1,	1,	'',	'测试一下意见反馈',	NULL,	1660833259);

DROP TABLE IF EXISTS `pf_manage`;
CREATE TABLE `pf_manage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL COMMENT '账号',
  `password` varchar(50) DEFAULT NULL COMMENT '密码',
  `salt` varchar(10) DEFAULT NULL COMMENT '盐值',
  `token` varchar(128) DEFAULT NULL COMMENT '签名',
  `parent_id` int(11) DEFAULT '0' COMMENT '上级',
  `parent_tree` varchar(500) DEFAULT NULL COMMENT '上级祖辈树',
  `group_id` int(11) DEFAULT '2' COMMENT '组别',
  `super` int(11) DEFAULT '0',
  `status` int(11) DEFAULT '1' COMMENT '状态',
  `add_time` int(11) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员';

INSERT INTO `pf_manage` (`id`, `name`, `password`, `salt`, `token`, `parent_id`, `parent_tree`, `group_id`, `super`, `status`, `add_time`) VALUES
(1,	'boylwf',	'9ee7fa72dc62ce087b2f03dd2cffd4ed',	'633256',	'8ff6b88ab3fffda7b42facb86716fb98',	0,	NULL,	1,	1,	1,	1653186743),
(2,	'jkl',	'4fc792e631798561280f1b31e31bbc03',	'463723',	'967a429a2d080ffecb94b1fee8c7433a',	0,	NULL,	1,	0,	1,	1653186743),
(4,	'account',	'95189cc5b45d8eb8978e33f8331e6165',	'511311',	NULL,	2,	'2',	2,	0,	1,	1654174768);

DROP TABLE IF EXISTS `pf_manage_group`;
CREATE TABLE `pf_manage_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL COMMENT '分组名称',
  `menu` varchar(200) DEFAULT NULL COMMENT '菜单权限',
  `permission` text COMMENT '权限',
  `power` int(11) DEFAULT '0' COMMENT '是否超级组别',
  `add_time` int(11) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='管理员组别';

INSERT INTO `pf_manage_group` (`id`, `name`, `menu`, `permission`, `power`, `add_time`) VALUES
(1,	'超级管理员',	'all',	NULL,	1,	1514180157),
(2,	'普通管理员',	'1,3',	NULL,	0,	1522137703);

DROP TABLE IF EXISTS `pf_manage_log`;
CREATE TABLE `pf_manage_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `manage_id` int(11) DEFAULT '0' COMMENT '管理员id',
  `manage_name` varchar(20) DEFAULT NULL COMMENT '管理员账号',
  `app` varchar(100) DEFAULT NULL COMMENT '控制器',
  `act` varchar(100) DEFAULT NULL COMMENT '方法',
  `type` varchar(20) DEFAULT NULL COMMENT '类型，url路径，insert插入数据，update更新数据，delete删除数据',
  `remark` text COMMENT '内容',
  `ip` varchar(20) DEFAULT NULL COMMENT 'ip',
  `add_time` int(11) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员日志';


SET NAMES utf8mb4;

DROP TABLE IF EXISTS `pf_member`;
CREATE TABLE `pf_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL COMMENT '用户名称',
  `wallet` varchar(200) DEFAULT NULL COMMENT '钱包地址',
  `token` varchar(128) DEFAULT NULL COMMENT '签名',
  `parent_id` int(11) DEFAULT '0' COMMENT '我的邀请人',
  `parent_tree` varchar(1000) DEFAULT '' COMMENT '我的祖辈树',
  `password` varchar(128) DEFAULT NULL COMMENT '密码，加密后',
  `salt` varchar(6) DEFAULT NULL COMMENT '盐值',
  `withdraw_password` varchar(128) DEFAULT NULL COMMENT '提现密码',
  `withdraw_salt` varchar(6) DEFAULT NULL COMMENT '提现盐值',
  `pay_password` varchar(128) DEFAULT NULL COMMENT '支付密码',
  `pay_salt` varchar(128) DEFAULT NULL COMMENT '支付盐值',
  `u_address` varchar(128) DEFAULT NULL COMMENT 'U盾地址',
  `withdraw_address` varchar(128) DEFAULT NULL COMMENT '提币地址',
  `session_id` varchar(100) DEFAULT NULL COMMENT 'session_id',
  `grade_id` int(11) DEFAULT '0' COMMENT '会员等级',
  `grade_score` int(11) DEFAULT '0' COMMENT '等级积分',
  `grade_time` int(11) DEFAULT '0' COMMENT '等级开始时间',
  `grade_endtime` int(11) DEFAULT '0' COMMENT '等级结束时间',
  `money` decimal(20,8) DEFAULT '0.00000000' COMMENT '余额',
  `hy_money` decimal(20,8) DEFAULT '0.00000000' COMMENT '合约余额',
  `xh_money` decimal(20,8) DEFAULT '0.00000000' COMMENT '现货余额',
  `commission` decimal(20,2) DEFAULT '0.00' COMMENT '佣金',
  `commission_total` decimal(20,2) DEFAULT '0.00' COMMENT '总收益',
  `integral` decimal(20,2) DEFAULT '0.00' COMMENT '积分',
  `invite_code` varchar(50) DEFAULT NULL COMMENT '邀请码',
  `real_name` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '真实姓名',
  `nick_name` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '昵称',
  `mobile` varchar(50) DEFAULT NULL COMMENT '手机',
  `email` varchar(50) DEFAULT NULL COMMENT '邮箱',
  `avatar` varchar(1000) DEFAULT NULL COMMENT '头像',
  `wechat` varchar(50) DEFAULT NULL COMMENT '微信号',
  `qq` varchar(50) DEFAULT NULL COMMENT 'QQ号',
  `idcard` varchar(50) DEFAULT NULL COMMENT '身份证号码',
  `sex` varchar(50) DEFAULT NULL COMMENT '性别',
  `province` varchar(50) DEFAULT NULL COMMENT '省',
  `city` varchar(50) DEFAULT NULL COMMENT '市',
  `district` varchar(50) DEFAULT NULL COMMENT '区',
  `town` varchar(50) DEFAULT NULL COMMENT '街道',
  `birth_year` int(11) DEFAULT '0' COMMENT '出生年',
  `birth_month` int(11) DEFAULT '0' COMMENT '出生月',
  `birth_day` int(11) DEFAULT '0' COMMENT '出生日',
  `remark` varchar(200) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '个性签名',
  `status` int(11) DEFAULT '0' COMMENT '0冻结，1正常',
  `logins` int(11) DEFAULT '0' COMMENT '登录次数',
  `last_ip` varchar(50) DEFAULT '' COMMENT '上次登录ip',
  `last_time` int(11) DEFAULT '0' COMMENT '上次登录时间',
  `reg_ip` varchar(50) DEFAULT '' COMMENT '注册ip',
  `reg_time` int(11) DEFAULT '0' COMMENT '注册时间',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `parent_id` (`parent_id`),
  KEY `parent_tree` (`parent_tree`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员表';

INSERT INTO `pf_member` (`id`, `name`, `wallet`, `token`, `parent_id`, `parent_tree`, `password`, `salt`, `withdraw_password`, `withdraw_salt`, `pay_password`, `pay_salt`, `u_address`, `withdraw_address`, `session_id`, `grade_id`, `grade_score`, `grade_time`, `grade_endtime`, `money`, `hy_money`, `xh_money`, `commission`, `commission_total`, `integral`, `invite_code`, `real_name`, `nick_name`, `mobile`, `email`, `avatar`, `wechat`, `qq`, `idcard`, `sex`, `province`, `city`, `district`, `town`, `birth_year`, `birth_month`, `birth_day`, `remark`, `status`, `logins`, `last_ip`, `last_time`, `reg_ip`, `reg_time`) VALUES
(1,	'user828791',	'0x2a2bdcf89f6c8f359e5bffcec54fbcb7bc8afa66',	'4cbd3d5c4c73ef5ed54387b4c96f77f6',	0,	'',	'3079644ef784bb3e03f9a4e1acfda852',	'527083',	'db9e9f19853adf77a25894b275bf17a7',	'919066',	NULL,	NULL,	NULL,	NULL,	'c2f6fe4065df37d4748cb4fae0125b7d',	3,	0,	0,	0,	9989656.53000000,	2000.00000000,	9897.84863321,	0.00,	0.00,	8.00,	'P21BNAiucJ',	'ajsong',	'@MARIO',	NULL,	'lwf_000001@126.com',	'http://img.softstao.com/uploadfiles/client/1/pic/2018/11/09/1811922224611250.gif',	NULL,	NULL,	NULL,	'男',	'Guangdong',	'Guangzhou',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	1,	49,	'211.136.223.20',	1662079406,	'::1',	1510475903),
(2,	'user204369',	'0x4d0f909a1a927c1724bffe6552a4210a291e38c5',	'f67d683e87633e565fa1432653c88df5',	1,	'1',	'cfc0489380e81971c5aef87bde090076',	'612054',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'471f66e8b7b76a233b10d55a2ae73e42',	2,	0,	0,	0,	33.00000000,	0.00000000,	0.00000000,	0.00,	0.00,	0.00,	'c3SMaoFvRM',	'MARIO-PRO',	'',	NULL,	'test1@126.com',	'https://wx.qlogo.cn/mmopen/vi_32/1K6u0BpZqTNv2khws8rStkXqPsv7UJZ1R7Nz5erIfbdia6W6tSayBfA48MRdMZANia49GviaibL6iarVc7VLPDkeVYg/132',	NULL,	NULL,	NULL,	'男',	'Guangdong',	'Guangzhou',	NULL,	NULL,	0,	0,	0,	NULL,	1,	1,	'113.65.229.221',	1662452056,	'127.0.0.1',	1531464771),
(10,	'user332211',	'0x3a861f5bc4957067ea3ce04658104c8ea8fd4800',	'bafdb2206deeb4456718f3a6a3fb4d74',	1,	'1',	'044ac0539fd19d82f1540a54569d7e68',	'451349',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'ea0fe6cbfc32a38457e70afd09677ce9',	2,	0,	0,	0,	2000.00000000,	0.00000000,	0.00000000,	0.00,	0.00,	0.00,	'oWJJruTgcq',	NULL,	NULL,	NULL,	'abc@qq.com',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	1,	17,	'127.0.0.1',	1655862309,	'127.0.0.1',	1653550778);

DROP TABLE IF EXISTS `pf_member_grade`;
CREATE TABLE `pf_member_grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL COMMENT '等级名称',
  `pic` varchar(250) DEFAULT NULL COMMENT '图片',
  `memo` text COMMENT '备注',
  `num` int(11) DEFAULT '0' COMMENT '数量',
  `person` int(11) DEFAULT '3' COMMENT '升级条件(下级人数)',
  `score` int(11) DEFAULT '0' COMMENT '所需积分',
  `price` decimal(20,2) DEFAULT '0.00' COMMENT '价格',
  `level_percent` decimal(10,2) DEFAULT '0.00' COMMENT '级别收益，百分比',
  `line_percent` decimal(10,2) DEFAULT '0.00' COMMENT '平级收益，百分比',
  `team_total` decimal(10,2) DEFAULT '0.00' COMMENT '升级条件(团队合约交易总额)',
  `status` int(11) DEFAULT '1' COMMENT '状态：1正常，0隐藏',
  `sort` int(11) DEFAULT '999' COMMENT '数字越小，排在越前',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员等级表';

INSERT INTO `pf_member_grade` (`id`, `name`, `pic`, `memo`, `num`, `person`, `score`, `price`, `level_percent`, `line_percent`, `team_total`, `status`, `sort`) VALUES
(1,	'普通',	'',	'',	0,	0,	0,	0.00,	0.00,	0.00,	0.00,	1,	999),
(2,	'VIP',	'',	'',	48,	3,	0,	0.00,	40.00,	1.00,	1000.00,	1,	999),
(3,	'社区',	NULL,	NULL,	0,	3,	0,	0.00,	50.00,	1.50,	2000.00,	1,	999),
(4,	'节点',	NULL,	NULL,	0,	3,	0,	0.00,	60.00,	2.00,	3000.00,	1,	999);

DROP TABLE IF EXISTS `pf_menu`;
CREATE TABLE `pf_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL COMMENT '名称',
  `parent_id` int(11) DEFAULT '0' COMMENT '上级id',
  `path` varchar(100) DEFAULT NULL COMMENT '路径',
  `icon` varchar(100) DEFAULT NULL COMMENT '图标',
  `level` int(11) DEFAULT '1' COMMENT '层级，0隐藏，1根目录，2子目录',
  `sort` int(11) DEFAULT '0' COMMENT '排序，越小越前',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='导航';

INSERT INTO `pf_menu` (`id`, `name`, `parent_id`, `path`, `icon`, `level`, `sort`) VALUES
(1,	'会员管理',	0,	'member',	'fa fa-user-friends',	1,	1),
(2,	'会员列表',	1,	'/admin/member/index',	'',	2,	1),
(3,	'会员等级',	1,	'/admin/member/grade',	'',	2,	2),
(25,	'文章管理',	0,	'article',	'fa fa-file-text',	1,	5),
(26,	'文章列表',	25,	'/admin/article/index',	'',	2,	1),
(27,	'文章分类',	25,	'/admin/article/category',	'',	0,	2),
(28,	'促销管理',	0,	'promote',	'fa fa-gift',	1,	6),
(29,	'广告列表',	28,	'/admin/banner/index',	'',	2,	1),
(32,	'财务管理',	0,	'money',	'fa fa-dollar',	1,	7),
(33,	'帐变记录',	32,	'/admin/money/wallet',	'',	2,	1),
(34,	'提现申请',	32,	'/admin/money/withdraw',	'',	2,	2),
(61,	'系统设置',	0,	'system',	'fas fa-cog',	1,	8),
(62,	'管理员列表',	61,	'/admin/manage/index',	'',	2,	2),
(63,	'管理员分组',	61,	'/admin/manage/group',	'',	2,	3),
(64,	'系统参数',	61,	'/admin/config/index',	'',	2,	4),
(105,	'意见反馈',	61,	'/admin/feedback/index',	'',	2,	1),
(106,	'充值管理',	32,	'/admin/money/recharge',	'',	2,	3);

DROP TABLE IF EXISTS `pf_money_log`;
CREATE TABLE `pf_money_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) DEFAULT '0' COMMENT '会员id',
  `number` decimal(20,8) DEFAULT '0.00000000' COMMENT '变化的数量',
  `old` decimal(20,8) DEFAULT '0.00000000' COMMENT '原来的数值',
  `new` decimal(20,8) DEFAULT '0.00000000' COMMENT '变化后的数值',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `type` int(11) DEFAULT '0' COMMENT '1充值，2提现，...',
  `status` int(11) DEFAULT '0' COMMENT '0支出，1收入',
  `money_type` int(11) DEFAULT '0' COMMENT '钱包类型，1USDT，2BTC，3EXP',
  `fromtable` varchar(255) DEFAULT NULL COMMENT '所属表',
  `fromid` int(11) DEFAULT '0' COMMENT '所属表id',
  `fee` decimal(20,8) DEFAULT '0.00000000' COMMENT '手续费',
  `add_time` int(11) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_id` (`member_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='账变记录表';

INSERT INTO `pf_money_log` (`id`, `member_id`, `number`, `old`, `new`, `remark`, `type`, `status`, `money_type`, `fromtable`, `fromid`, `fee`, `add_time`) VALUES
(105,	2,	0.50000000,	0.00000000,	0.50000000,	'直推奖励第一代0.5%',	4,	1,	0,	'blind_order',	1,	0.00000000,	1653723531),
(106,	1,	0.30000000,	9999975.93000000,	9999976.23000000,	'直推奖励第二代0.3%',	4,	1,	0,	'blind_order',	1,	0.00000000,	1653723531),
(107,	2,	1.00000000,	0.50000000,	1.50000000,	'平级奖励1.0%',	6,	1,	0,	'blind_order',	1,	0.00000000,	1653723531),
(108,	2,	0.50000000,	1.50000000,	2.00000000,	'直推奖励第一代0.5%',	4,	1,	0,	'blind_order',	2,	0.00000000,	1653724368),
(109,	1,	0.30000000,	9999976.23000000,	9999976.53000000,	'直推奖励第二代0.3%',	4,	1,	0,	'blind_order',	2,	0.00000000,	1653724368),
(110,	2,	1.00000000,	2.00000000,	3.00000000,	'平级奖励1.0%',	8,	1,	0,	'blind_order',	2,	0.00000000,	1653724368),
(111,	2,	10.00000000,	3.00000000,	13.00000000,	'推荐奖励1.0%',	7,	1,	0,	'member_grade_order',	0,	0.00000000,	1653833457),
(112,	2,	10.00000000,	13.00000000,	23.00000000,	'推荐奖励1.0%',	7,	1,	0,	'member_grade_order',	0,	0.00000000,	1653833532),
(113,	2,	10.00000000,	23.00000000,	33.00000000,	'推荐奖励1.0%',	7,	1,	0,	'member_grade_order',	0,	0.00000000,	1653833562),
(114,	2,	0.00000000,	33.00000000,	33.00000000,	'直推奖励第一代0.5%',	4,	1,	0,	'blind_order',	4,	0.00000000,	1653915254),
(115,	1,	0.00000000,	9999976.53000000,	9999976.53000000,	'直推奖励第二代0.3%',	4,	1,	0,	'blind_order',	4,	0.00000000,	1653915254),
(116,	2,	0.00100000,	33.00000000,	33.00100000,	'平级奖励1.0%',	6,	1,	0,	'blind_order',	4,	0.00000000,	1653915254),
(117,	2,	0.00000000,	33.00000000,	33.00000000,	'直推奖励第一代0.5%',	4,	1,	0,	'blind_order',	5,	0.00000000,	1653915667),
(118,	1,	0.00000000,	9999976.53000000,	9999976.53000000,	'直推奖励第二代0.3%',	4,	1,	0,	'blind_order',	5,	0.00000000,	1653915667),
(119,	2,	0.00100000,	33.00000000,	33.00100000,	'平级奖励1.0%',	6,	1,	0,	'blind_order',	5,	0.00000000,	1653915667),
(120,	1,	-300.00000000,	9999976.53000000,	9999676.53000000,	'系统扣除',	1,	0,	0,	'',	0,	0.00000000,	1654084886),
(121,	1,	-300.00000000,	9999676.53000000,	9999376.53000000,	'系统扣除',	1,	0,	0,	'',	0,	0.00000000,	1654085114),
(122,	10,	0.01287500,	0.00000000,	0.01287500,	'盲盒收益',	8,	1,	0,	'blind_order',	4,	0.00000000,	1654481005),
(123,	15,	3000.00000000,	0.00000000,	3000.00000000,	'系统充值',	1,	1,	0,	'',	0,	0.00000000,	1654591746),
(124,	15,	20.00000000,	2980.00000000,	2956.00000000,	'申请提现',	2,	0,	0,	'withdraw',	6,	4.00000000,	1654592107),
(125,	15,	20.00000000,	3000.00000000,	2976.00000000,	'申请提现',	2,	0,	0,	'withdraw',	7,	4.00000000,	1654592182),
(126,	15,	20.00000000,	3000.00000000,	2980.00000000,	'申请提现',	2,	0,	0,	'withdraw',	8,	4.00000000,	1654592322),
(127,	10,	1000.00000000,	3000.00000000,	2000.00000000,	'余额支付等级提升',	9,	0,	0,	'member_grade_order',	4,	0.00000000,	1654659507),
(128,	15,	1000.00000000,	2980.00000000,	1980.00000000,	'余额支付等级提升',	9,	0,	0,	'member_grade_order',	5,	0.00000000,	1655774333),
(129,	15,	500.00000000,	10000.00000000,	9500.00000000,	'余额支付盲盒复投',	9,	0,	0,	'blind_order',	6,	0.00000000,	1655858986),
(130,	15,	200.00000000,	0.00000000,	200.00000000,	'复投获得积分',	3,	1,	0,	'blind_order',	6,	0.00000000,	1655858986),
(131,	15,	10.00000000,	9500.00000000,	9490.00000000,	'余额支付盲盒复投',	9,	0,	0,	'blind_order',	7,	0.00000000,	1655859018),
(132,	15,	4.00000000,	200.00000000,	204.00000000,	'复投获得积分',	3,	1,	0,	'blind_order',	7,	0.00000000,	1655859018),
(133,	2,	10.00000000,	33.00000000,	43.00000000,	'系统充值',	1,	1,	0,	'recharge',	0,	0.00000000,	1663207645),
(134,	37,	612.00000000,	0.00000000,	612.00000000,	'手续费收益40.00%',	10,	1,	0,	'con_strategy',	18,	0.00000000,	1663212952),
(135,	37,	1.20720000,	612.00000000,	613.20720000,	'手续费收益40.00%',	10,	1,	0,	'con_strategy',	19,	0.00000000,	1663212952),
(136,	37,	1.20900000,	613.20720000,	614.41620000,	'手续费收益40.00%',	10,	1,	0,	'con_strategy',	20,	0.00000000,	1663212952),
(137,	38,	0.00229520,	0.00000000,	0.00229520,	'止盈收益',	8,	1,	0,	'con_strategy',	20,	0.00000000,	1663218821),
(138,	38,	0.00229520,	0.00229520,	0.00459040,	'止盈收益',	8,	1,	0,	'con_strategy',	20,	0.00000000,	1663218853),
(139,	38,	0.00229520,	0.00459040,	0.00688560,	'止盈收益',	8,	1,	0,	'con_strategy',	20,	0.00000000,	1663218859),
(140,	38,	0.00229520,	0.00688560,	0.00918080,	'止盈收益',	8,	1,	0,	'con_strategy',	20,	0.00000000,	1663218897),
(141,	38,	0.00229520,	0.00918080,	0.01147600,	'止盈收益',	8,	1,	0,	'con_strategy',	20,	0.00000000,	1663218900),
(142,	38,	0.00229520,	0.01147600,	0.01377120,	'止盈收益',	8,	1,	0,	'con_strategy',	20,	0.00000000,	1663218902),
(143,	38,	0.00229520,	0.01377120,	0.01606640,	'止盈收益',	8,	1,	0,	'con_strategy',	20,	0.00000000,	1663218910),
(144,	38,	0.00229520,	0.01606640,	0.01836160,	'止盈收益',	8,	1,	0,	'con_strategy',	20,	0.00000000,	1663218915),
(145,	38,	0.00229520,	0.01836160,	0.02065680,	'止盈收益',	8,	1,	0,	'con_strategy',	20,	0.00000000,	1663218918),
(146,	38,	0.00229520,	0.02065680,	0.02295200,	'止盈收益',	8,	1,	0,	'con_strategy',	20,	0.00000000,	1663218938);

DROP TABLE IF EXISTS `pf_recharge`;
CREATE TABLE `pf_recharge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) DEFAULT '0' COMMENT '会员id',
  `field` varchar(10) DEFAULT 'money' COMMENT '充值字段',
  `num` int(11) DEFAULT '0' COMMENT '数量',
  `pic` varchar(1000) DEFAULT NULL COMMENT '凭证图片',
  `status` int(11) DEFAULT '0' COMMENT '状态，0等待审核，1完成',
  `add_time` int(11) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='充值表';

INSERT INTO `pf_recharge` (`id`, `member_id`, `field`, `num`, `pic`, `status`, `add_time`) VALUES
(1,	15,	'money',	55,	'/uploads/recharge/2022/06/21/22062111201071500.jpg',	0,	1655781643),
(2,	15,	'money',	55,	'/uploads/recharge/2022/06/21/22062111201071500.jpg',	-1,	1655781643),
(3,	1,	'money',	99,	'/uploads/recharge/2022/09/13/22091310050969444.png',	0,	1663034709);

DROP TABLE IF EXISTS `pf_system_data`;
CREATE TABLE `pf_system_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reward_profit` int(11) DEFAULT '0',
  `reward_fee` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统数据';

INSERT INTO `pf_system_data` (`id`, `reward_profit`, `reward_fee`) VALUES
(1,	0,	1);

DROP TABLE IF EXISTS `pf_withdraw`;
CREATE TABLE `pf_withdraw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) DEFAULT '0' COMMENT '会员id',
  `order_sn` varchar(50) DEFAULT NULL COMMENT '订单号',
  `trade_no` varchar(250) DEFAULT NULL COMMENT '外部订单号',
  `pay_method` varchar(50) DEFAULT 'wxpay_h5' COMMENT 'alipay/wxpay/wxpay_h5',
  `client_type` int(11) DEFAULT '0' COMMENT '客户端类型，0网页，1小程序，2iOS，3Android',
  `pay_time` int(11) DEFAULT '0' COMMENT '付款时间',
  `price` decimal(20,2) DEFAULT '0.00' COMMENT '金额(不含手续费)',
  `fee` decimal(20,2) DEFAULT '0.00' COMMENT '手续费',
  `total` decimal(20,2) DEFAULT '0.00' COMMENT '金额(含手续费)',
  `name` varchar(50) DEFAULT NULL COMMENT '真实姓名',
  `bank_name` varchar(50) DEFAULT NULL COMMENT '银行名称',
  `bank_branch` varchar(100) DEFAULT NULL COMMENT '支行名称',
  `bank_card` varchar(50) DEFAULT NULL COMMENT '银行卡号',
  `alipay` varchar(50) DEFAULT NULL COMMENT '支付宝账号',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机号码',
  `address` varchar(500) DEFAULT NULL COMMENT '提现地址',
  `field` varchar(10) DEFAULT 'money' COMMENT '提现字段',
  `type` int(11) DEFAULT '0' COMMENT '提现来源或网络(会员表字段名、getTypes)',
  `person_income` int(11) DEFAULT '0' COMMENT '个人所得税',
  `invoice_number` varchar(200) DEFAULT NULL COMMENT '发票单号',
  `ip` varchar(50) DEFAULT NULL COMMENT '提现人ip',
  `status` int(11) DEFAULT '0' COMMENT '0等待审核，1提现成功，-1提现失败',
  `add_time` int(11) DEFAULT '0' COMMENT '提现申请日期',
  `audit_memo` varchar(250) DEFAULT NULL COMMENT '审核备注',
  `audit_adminid` int(11) DEFAULT '0' COMMENT '审单人ID',
  `audit_admin` varchar(50) DEFAULT NULL COMMENT '审单人',
  `audit_time` int(11) DEFAULT '0' COMMENT '审单时间',
  `withdraw_memo` varchar(250) DEFAULT NULL COMMENT '提现结果的备注，如流水号',
  `withdraw_adminid` int(11) DEFAULT '0' COMMENT '提现操作人ID',
  `withdraw_admin` varchar(50) DEFAULT NULL COMMENT '提现操作人',
  `withdraw_time` int(11) DEFAULT '0' COMMENT '提现操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='提现表';

INSERT INTO `pf_withdraw` (`id`, `member_id`, `order_sn`, `trade_no`, `pay_method`, `client_type`, `pay_time`, `price`, `fee`, `total`, `name`, `bank_name`, `bank_branch`, `bank_card`, `alipay`, `mobile`, `address`, `field`, `type`, `person_income`, `invoice_number`, `ip`, `status`, `add_time`, `audit_memo`, `audit_adminid`, `audit_admin`, `audit_time`, `withdraw_memo`, `withdraw_adminid`, `withdraw_admin`, `withdraw_time`) VALUES
(1,	1,	NULL,	NULL,	NULL,	0,	0,	1.00,	0.00,	0.00,	'',	'',	'',	'',	NULL,	NULL,	NULL,	'money',	3,	0,	NULL,	'127.0.0.1',	0,	1526006170,	'',	0,	'',	0,	NULL,	0,	NULL,	0),
(2,	1,	NULL,	NULL,	NULL,	0,	0,	2.00,	0.00,	0.00,	'',	'',	'',	'',	NULL,	NULL,	NULL,	'money',	3,	0,	NULL,	'127.0.0.1',	1,	1526006170,	NULL,	1,	'admin',	1653359647,	NULL,	0,	NULL,	0),
(3,	10,	NULL,	NULL,	NULL,	0,	0,	1.00,	0.00,	0.00,	'',	'',	'',	'',	NULL,	NULL,	NULL,	'money',	3,	0,	NULL,	'127.0.0.1',	-1,	1526006170,	'测试申请不通过',	1,	'admin',	1653360463,	NULL,	0,	NULL,	0),
(4,	10,	NULL,	NULL,	NULL,	0,	0,	1.00,	0.00,	0.00,	'',	'',	'',	'',	NULL,	NULL,	NULL,	'money',	3,	0,	NULL,	'127.0.0.1',	1,	1526006170,	NULL,	1,	'admin',	1653358585,	NULL,	0,	NULL,	0),
(5,	10,	NULL,	NULL,	NULL,	0,	0,	1.00,	0.00,	0.00,	'',	'',	'',	'',	NULL,	NULL,	NULL,	'money',	3,	0,	NULL,	'127.0.0.1',	-1,	1526006170,	'测试审单拒绝',	1,	'admin',	1653358553,	NULL,	0,	NULL,	0),
(6,	15,	'22060716550760720',	NULL,	'wxpay_h5',	0,	0,	20.00,	4.00,	0.00,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'fff',	'money',	3,	0,	NULL,	'127.0.0.1',	0,	1654592107,	NULL,	0,	NULL,	0,	NULL,	0,	NULL,	0),
(7,	15,	'22060716562276664',	NULL,	'wxpay_h5',	0,	0,	20.00,	4.00,	0.00,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'gg',	'money',	3,	0,	NULL,	'127.0.0.1',	0,	1654592182,	NULL,	0,	NULL,	0,	NULL,	0,	NULL,	0),
(8,	15,	'22060716584281310',	NULL,	'wxpay_h5',	0,	0,	20.00,	4.00,	0.00,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'cc',	'money',	3,	0,	NULL,	'127.0.0.1',	0,	1654592322,	NULL,	0,	NULL,	0,	NULL,	0,	NULL,	0);

-- 2022-11-03 02:34:55
