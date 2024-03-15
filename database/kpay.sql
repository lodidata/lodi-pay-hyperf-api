/*
Navicat MySQL Data Transfer

Source Server         : 52.74.208.242
Source Server Version : 50726
Source Host           : 52.74.208.242:3308
Source Database       : kpay

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2023-06-12 19:52:10
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for admin
-- ----------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(30) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(64) NOT NULL DEFAULT '' COMMENT '登录密码',
  `real_name` varchar(60) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `nick_name` varchar(30) NOT NULL DEFAULT '' COMMENT '昵称',
  `position` varchar(20) NOT NULL DEFAULT '' COMMENT '职位',
  `department` varchar(20) NOT NULL DEFAULT '' COMMENT '部门',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态 1：启用；0：禁用',
  `creator_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者id',
  `creator_name` varchar(50) NOT NULL DEFAULT '',
  `last_login_ip` char(19) NOT NULL DEFAULT '' COMMENT '上次登录ip',
  `last_login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上次登录时间',
  `merchant_id` int(11) unsigned DEFAULT NULL COMMENT '所属商户',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `udx_admin_name` (`admin_name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT=' 管理用户表';

-- ----------------------------
-- Records of admin
-- ----------------------------
INSERT INTO `admin` VALUES ('1', 'admin', '$2y$10$IeCQyhGAVmI/w2T6m2XaYuIDWujhkfSJkvn4EJmahzJTiZw9.k3w2', 'super', 'test', '', '', '', '1', '1', '', '82.102.25.146', '2023-06-12 19:46:31', null, '2023-05-11 15:08:37', '2023-06-12 19:46:31');

-- ----------------------------
-- Table structure for admin_config
-- ----------------------------
DROP TABLE IF EXISTS `admin_config`;
CREATE TABLE `admin_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '编码',
  `key` varchar(100) DEFAULT NULL COMMENT '变量',
  `default_config` json DEFAULT NULL,
  `info` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `sort` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COMMENT='系统配置';

-- ----------------------------
-- Records of admin_config
-- ----------------------------
INSERT INTO `admin_config` VALUES ('3', '0', '提款订单拆分阈值', 'order_threshold', '{\"type\": \"number\", \"unit\": \"\", \"value\": \"500\"}', '单个提款订单金额大于阈值时，才会被拆分', '1');
INSERT INTO `admin_config` VALUES ('4', '0', '单站点单日最大提现金额', 'max_amount_site', '{\"type\": \"number\", \"unit\": \"\", \"value\": \"999999999\"}', '单站点单日最大提现金额，按订单金额计算，（非实际到手金额），999999999为无上限', '2');
INSERT INTO `admin_config` VALUES ('5', '0', '单站点提现成功金额率', 'withdrawal_success_rate', '{\"type\": \"percent\", \"unit\": \"%\", \"value\": \"100\"}', '单站点提现成功金额率大于等于多少时可使用内充平台提现成功金额率=成功金额数  /  总充值金额', '3');
INSERT INTO `admin_config` VALUES ('6', '0', '单站点充值成功金额率', 'successful_amount_rate', '{\"type\": \"percent\", \"unit\": \"%\", \"value\": \"100\"}', '单站点充值成功金额率大于等于多少时可使用内充平台充值成功金额率=成功金额数  /  总充值金额', '4');
INSERT INTO `admin_config` VALUES ('7', '0', '单站点提现成功率', 'withdrawal_success_rate', '{\"type\": \"percent\", \"unit\": \"%\", \"value\": 20}', '单站点提现成功率大于等于多少时可使用内充平台提现成功率=成功订单数  /  总提现订单数', '5');
INSERT INTO `admin_config` VALUES ('8', '0', '单站点充值成功率', 'recharge_success_rate', '{\"type\": \"percent\", \"unit\": \"%\", \"value\": 30}', '单站点充值成功率大于等于多少时可使用内充平台\n充值成功率=成功订单数  /  总充值订单数', '6');
INSERT INTO `admin_config` VALUES ('9', '0', '\n单用户单笔充值最小金额', 'min_recharge_amount', '{\"type\": \"number\", \"unit\": \"\", \"value\": \"100\"}', '单用户单日最高提现金额限制，按订单金额计算，（非实际到手金额），999999999为无上限', '7');
INSERT INTO `admin_config` VALUES ('10', '0', '单用户单笔提现金额限制倍数', 'limit_withdrawal_amount', '{\"type\": \"number\", \"unit\": \"\", \"value\": \"100\"}', '单用户单笔提现金额限制倍数（用户习惯数据模型按历史订单数据中的金额最大值作为用户习惯金额值）用户下一笔可提现金额=用户习惯金额值*限制倍数', '8');
INSERT INTO `admin_config` VALUES ('11', '0', '单用户提现成功金额率', 'user_withdrawal_amount_success_rate', '{\"type\": \"percent\", \"unit\": \"%\", \"value\": 40}', '同一用户在内充平台提现成功金额率大于等于多少时可使用内充平台提现成功金额率=成功金额数  /  总充值金额', '9');
INSERT INTO `admin_config` VALUES ('12', '0', '单用户充值成功金额率', 'user_recharge_amount_success_rate', '{\"type\": \"percent\", \"unit\": \"%\", \"value\": \"70\"}', '同一用户在内充平台充值成功金额率大于等于多少时可使用内充平台充值成功金额率=成功金额数  /  总充值金额', '10');
INSERT INTO `admin_config` VALUES ('13', '0', '单用户提现成功率', 'user_withdrawal_success_rate', '{\"type\": \"percent\", \"unit\": \"%\", \"value\": 60}', '同一用户在内充平台提现成功率大于等于多少时可使用内充平台提现成功率=成功订单数  /  总提现订单数', '11');
INSERT INTO `admin_config` VALUES ('14', '0', '单用户充值成功率', 'user_recharge_success_rate', '{\"type\": \"percent\", \"unit\": \"%\", \"value\": \"66\"}', '同一用户在内充平台充值成功率大于等于多少时可使用内充平台充值成功率=成功订单数  /  总充值订单数', '12');
INSERT INTO `admin_config` VALUES ('15', '0', '单用户历史提现笔数', 'history_withdrawal_nums', '{\"type\": \"boolean\", \"unit\": \"\", \"value\": 0}', '在内充平台内历史提现笔数小于等于0时，是否可以使用内充平台（准入机制）', '13');
INSERT INTO `admin_config` VALUES ('16', '0', '单用户历史充值笔数', 'history_recharge_nums', '{\"type\": \"boolean\", \"unit\": \"\", \"value\": 1}', '在内充平台内历史充值笔数小于等于0时，是否可以使用内充平台（准入机制）', '14');
INSERT INTO `admin_config` VALUES ('17', '0', '提款配置', 'min_amount', '{\"type\": \"number\", \"unit\": \"\", \"value\": \"10\"}', '提款配置 min_amount=最小匹配提款金额(必须是100倍数）', '15');
INSERT INTO `admin_config` VALUES ('18', '0', '系统兜底', 'is_system_pay', '{\"type\": \"boolean\", \"unit\": \"\", \"value\": 1}', 'is_system_pay=系统兜底（1开启0关闭）', '16');

-- ----------------------------
-- Table structure for admin_log
-- ----------------------------
DROP TABLE IF EXISTS `admin_log`;
CREATE TABLE `admin_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '状态 0：未成功',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人员id',
  `admin_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人员账户',
  `method` varchar(10) NOT NULL DEFAULT '' COMMENT '方法名',
  `record` json NOT NULL COMMENT '记录(用于溯源)',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0：未成功 1：成功',
  `ip` varchar(20) NOT NULL DEFAULT '0.0.0.0' COMMENT '操作ip',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `path` varchar(30) NOT NULL DEFAULT '' COMMENT '请求路径',
  `uname2` varchar(30) NOT NULL DEFAULT '' COMMENT '被操作要用户名称',
  `module` varchar(30) NOT NULL DEFAULT '' COMMENT '模块名称',
  `module_child` varchar(30) NOT NULL DEFAULT '' COMMENT '子模块名称',
  `fun_name` varchar(30) NOT NULL DEFAULT '' COMMENT '功能模块',
  `uid2` int(10) NOT NULL DEFAULT '0' COMMENT '被操作要用户id',
  `remark` text COMMENT '操作详情',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='系统变更日志';

-- ----------------------------
-- Records of admin_log
-- ----------------------------
INSERT INTO `admin_log` VALUES ('1', '0', 'admin', '创建', '{\"code\": \"0239\", \"token\": \"47faf6ec0c532e162e0b2720e092c91b\", \"password\": \"123456\", \"admin_name\": \"admin\"}', '1', '82.102.25.146', '2023-06-12 19:46:31', '/admin/login', '', '用户登录', '用户登录', '用户登录', '0', '【admin】登录系统');

-- ----------------------------
-- Table structure for admin_role
-- ----------------------------
DROP TABLE IF EXISTS `admin_role`;
CREATE TABLE `admin_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色名',
  `auth` text NOT NULL,
  `creator_id` int(11) NOT NULL COMMENT '创建人id',
  `member_control` json DEFAULT NULL COMMENT '控制',
  `creator_name` varchar(50) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `role` (`role_name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='角色表';

-- ----------------------------
-- Records of admin_role
-- ----------------------------
INSERT INTO `admin_role` VALUES ('1', '超级管理员', '49,50,51,52,53,54,55,56,57,59,60,61,58,62,63,64,65,1,46,47,2,6,7,8,3,9,11,12,13,10,14,15,16,17,31,4,35,38,66,39,32,40,41,44,33,42,43,34,36,37,5,48,18,19,45,21,22,23,24,20,25,26,27,28', '5', null, 'redi123456', '2023-05-04 12:00:34', '2023-06-06 10:55:14');

-- ----------------------------
-- Table structure for admin_role_auth
-- ----------------------------
DROP TABLE IF EXISTS `admin_role_auth`;
CREATE TABLE `admin_role_auth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级id',
  `auth_name` varchar(50) NOT NULL DEFAULT '' COMMENT '权限名',
  `method` varchar(10) NOT NULL DEFAULT '' COMMENT '请求方式 GET|POST|PATCH|PUT|DELETE',
  `path` varchar(50) NOT NULL DEFAULT '' COMMENT '请求URI地址',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0：禁用 1：启用',
  `sort` int(11) NOT NULL COMMENT '排序序号',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='权限表';

-- ----------------------------
-- Records of admin_role_auth
-- ----------------------------
INSERT INTO `admin_role_auth` VALUES ('1', '0', '商户管理', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('2', '1', '查询', 'GET', '/merchant', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('3', '0', '用户管理', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('4', '0', '订单管理', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('5', '0', '系统管理', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('6', '1', '新增', 'POST', '/merchant', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('7', '1', '修改', 'PUT', '/merchant', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('8', '1', '删除', 'DELETE', '/merchant', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('9', '3', '用户列表', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('10', '3', '标签管理', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('11', '9', '列表', 'GET', '/user', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('12', '9', '编辑', 'PUT', '/user', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('13', '9', '删除', 'DELETE', '/user', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('14', '10', '列表', 'GET', '/tag', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('15', '10', '编辑', 'PUT', '/tag', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('16', '10', '增加', 'POST', '/tag', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('17', '10', '删除', 'DELETE', '/tag', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('18', '5', '系统配置', 'GET', '/settings', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('19', '5', '账号管理', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('20', '5', '角色权限', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('21', '19', '列表', 'GET', '/admin', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('22', '19', '编辑', 'PUT', '/admin', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('23', '19', '增加', 'POST', '/admin', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('24', '19', '删除', 'DELETE', '/admin', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('25', '20', '列表', 'GET', '/admin/role', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('26', '20', '编辑', 'PUT', '/admin/role', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('27', '20', '增加', 'POST', '/admin/role', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('28', '20', '删除', 'DELETE', '/admin/role', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('31', '10', '标签树', 'GET', '/tag/tree', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('32', '4', '提款订单', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('33', '4', '充值订单', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('34', '4', '匹配订单', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('35', '4', '争议订单', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('36', '34', '列表', 'GET', '/orders/matched', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('37', '34', '详情', 'GET', '/orders/matched/show', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('38', '35', '列表', 'GET', '/orders/trial', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('39', '35', '详情', 'GET', '/orders/trial/show', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('40', '32', '列表', 'GET', '/orders/pay', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('41', '32', '详情', 'GET', '/orders/pay/show', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('42', '33', '列表', 'GET', '/orders/collection', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('43', '33', '详情', 'GET', '/orders/collection/show', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('44', '32', '标记争议', 'POST', '/orders/matched/controversial', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('45', '19', '修改密码', 'PATCH', '/admin', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('46', '1', '待付钱包修改', 'PUT', '/merchant/balace', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('47', '1', '待付自动充值设置', 'PUT', '/merchant/autoCharge', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('48', '5', '管理员操作日志', 'GET', '/admin/log', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('49', '0', '第三方代付', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('50', '49', '添加', 'POST', '/thirdPayment', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('51', '49', '编辑', 'PUT', '/thirdPayment', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('52', '49', '删除', 'DELETE', '/thirdPayment', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('53', '49', '列表', 'GET', '/thirdPayment', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('54', '49', '代付树', 'GET', '/thirdPayment/tree', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('55', '49', '商家树', 'GET', '/merchant/tree', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('56', '49', '转账记录', 'GET', '/transfer', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('57', '0', '财务报表', 'GET', '/financialStatements', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('58', '0', '首页', '', '', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('59', '57', '财务详情', 'GET', '/financialStatements/show', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('60', '59', '财务统计详情', 'GET', 'financialStatements/mateShow', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('61', '59', '第三方支付统计详情', 'GET', '/financialStatements/thirdPayment/show', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('62', '58', '用户统计', 'GET', '/dashboard/user', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('63', '58', '充值订单统计', 'GET', '/dashboard/collection', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('64', '58', '匹配订单统计', 'GET', '/dashboard/pay', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('65', '58', '争议订单统计', 'GET', '/dashboard/dispute', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('66', '38', '争议订单处理', 'PUT', '/orders/trial', '1', '1');
INSERT INTO `admin_role_auth` VALUES ('67', '38', '争议订单-三方代付', 'POST', '/orders/trial/pay', '1', '1');

-- ----------------------------
-- Table structure for admin_role_relation
-- ----------------------------
DROP TABLE IF EXISTS `admin_role_relation`;
CREATE TABLE `admin_role_relation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员id',
  `role_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '角色id',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='用户角色关系表';

-- ----------------------------
-- Records of admin_role_relation
-- ----------------------------
INSERT INTO `admin_role_relation` VALUES ('1', '1', '1');

-- ----------------------------
-- Table structure for currency
-- ----------------------------
DROP TABLE IF EXISTS `currency`;
CREATE TABLE `currency` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `currency_type` char(5) NOT NULL DEFAULT '' COMMENT '货币类型(货币简码)',
  `currency_name` varchar(20) NOT NULL DEFAULT '' COMMENT '货币名称',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0：下架； 1：上架(默认)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间|维护时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `udx_curreny_type_name` (`currency_type`,`currency_name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='货币表';

-- ----------------------------
-- Records of currency
-- ----------------------------
INSERT INTO `currency` VALUES ('99', 'AED', '阿联酋迪拉姆', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('100', 'AUD', '澳元', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('101', 'BDT', '孟加拉国塔卡', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('102', 'BND', '汶莱元', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('103', 'BRL', '巴西雷亚尔', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('104', 'CAD', '加拿大元', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('105', 'CHF', '瑞士法郎', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('106', 'COP', '哥伦比亚比索', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('107', 'CNY', '人民币', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('108', 'EUR', '欧元币', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('109', 'GBP', '英镑', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('110', 'HKD', '港元', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('111', 'IDR', '印尼盾(1:1000)', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('112', 'INR', '印度卢比', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('113', 'JPY', '日币', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('114', 'KRW', '韩元', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('115', 'KZT', '哈萨克坦吉', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('116', 'KHR', '柬埔寨瑞尔(1:1000)', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('117', 'KES', '肯亚先令', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('118', 'LAK', '柬埔寨老挝基普', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('119', 'LKR', '斯里兰卡卢比', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('120', 'MOP', '澳门元', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('121', 'MMK', '缅元 (1:1)', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('122', 'MYR', '马来西亚林吉特', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('123', 'MXN', '墨西哥比索', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('124', 'MNT', '蒙古图格里克', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('125', 'NOK', '挪威克朗', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('126', 'NZD', '新西兰元', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('127', 'NPR', '尼泊尔卢比', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('128', 'NGN', '奈及利亚奈拉', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('129', 'PTI', '原版印尼盾 (1:1)', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('130', 'PTV', '原版越南盾 (1:1)', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('131', 'PKR', '巴基斯坦卢比', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('132', 'PHP', '菲律宾比索', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('133', 'PEN', '秘鲁新索尔', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('134', 'SEK', '瑞典克朗', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('135', 'SGD', '新加坡元', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('136', 'THB', '泰铢', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('137', 'TRY', '土耳其里拉', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('138', 'TND', '突尼斯第纳尔', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('139', 'TZS', '坦尚尼亚先令', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('140', 'USD', '美元', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('141', 'UAH', '乌克兰赫夫纳', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('142', 'VND', '越南盾(1:1000)', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('143', 'ZAR', '南非兰特', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('144', 'ZWD', '津巴布韦元', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('145', 'ZMW', '尚比亚克瓦查', '1', '2023-04-19 14:07:32', '2023-04-19 14:07:32');
INSERT INTO `currency` VALUES ('146', 'RMB', '人民币', '0', '2023-04-21 03:04:28', '2023-04-21 03:04:28');
INSERT INTO `currency` VALUES ('147', 'VND', '越南币', '0', '2023-04-21 03:06:15', '2023-04-21 03:33:08');
INSERT INTO `currency` VALUES ('149', 'RMB', '人民币rmb', '0', '2023-04-21 03:32:57', '2023-04-21 03:32:57');
INSERT INTO `currency` VALUES ('150', 'ETH', 'eths1', '0', '2023-04-21 11:26:33', '2023-04-21 14:58:43');
INSERT INTO `currency` VALUES ('151', 'XIAO', '逍遥', '0', '2023-04-22 22:05:48', '2023-04-22 22:18:23');

-- ----------------------------
-- Table structure for financial_statements
-- ----------------------------
DROP TABLE IF EXISTS `financial_statements`;
CREATE TABLE `financial_statements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户id',
  `merchant_name` varchar(50) NOT NULL COMMENT '商户名称',
  `payment_num` int(11) NOT NULL DEFAULT '0' COMMENT '代付笔数',
  `payment_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '代付金额',
  `recharge_num` int(11) NOT NULL DEFAULT '0' COMMENT '代充笔数',
  `recharge_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '代充金额',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总计',
  `payment_total` int(11) NOT NULL DEFAULT '0' COMMENT '代付总笔数',
  `recharge_total` int(11) NOT NULL DEFAULT '0' COMMENT '代充总笔数',
  `trial_collection_num` int(11) NOT NULL DEFAULT '0' COMMENT '代充争议订单笔数',
  `trial_pay_num` int(11) NOT NULL DEFAULT '0' COMMENT '代付争议订单笔数',
  `finance_date` date NOT NULL COMMENT '统计日期',
  `updated_at` datetime NOT NULL COMMENT '修改时间',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `merchant_idx` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代充代付数据统计表';

-- ----------------------------
-- Records of financial_statements
-- ----------------------------

-- ----------------------------
-- Table structure for merchant
-- ----------------------------
DROP TABLE IF EXISTS `merchant`;
CREATE TABLE `merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '名称',
  `account` varchar(20) NOT NULL DEFAULT '' COMMENT '账号',
  `is_pay_behalf` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启代付',
  `pay_behalf_level` int(11) NOT NULL DEFAULT '1' COMMENT '代付等级',
  `pay_behalf_point` decimal(3,1) NOT NULL DEFAULT '0.0' COMMENT '代付点位',
  `is_collection_behalf` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启代收',
  `collection_pay_level` int(11) NOT NULL DEFAULT '1' COMMENT '代收等级',
  `recharge_waiting_limit` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '充值中的订单限制数(超过则限制匹配)',
  `collection_pay_point` decimal(3,1) NOT NULL DEFAULT '0.0' COMMENT '代收点位',
  `ip_white_list` varchar(255) NOT NULL COMMENT 'ip白名单',
  `office_url` varchar(255) DEFAULT '' COMMENT '官网地址',
  `pay_callback_url` varchar(255) DEFAULT '' COMMENT '支付回调地址',
  `collect_callback_url` varchar(255) DEFAULT '' COMMENT '收款回调地址',
  `order_complete_method` tinyint(1) NOT NULL DEFAULT '1' COMMENT '订单完成方式',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `account` (`account`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='商户表';

-- ----------------------------
-- Records of merchant
-- ----------------------------

-- ----------------------------
-- Table structure for merchant_balance_change_log
-- ----------------------------
DROP TABLE IF EXISTS `merchant_balance_change_log`;
CREATE TABLE `merchant_balance_change_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_account` int(11) NOT NULL DEFAULT '0' COMMENT '商户号',
  `currency` char(3) NOT NULL DEFAULT '' COMMENT '币种',
  `transaction_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '交易类型：1=充值，2=提现，3=点位扣除金额, 4=余额手动划转，5-余额自动划转',
  `order_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '订单类型：1=充值，2=提现',
  `order_sn` varchar(255) NOT NULL DEFAULT '' COMMENT '交易订单号',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `change_after` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额变动之后',
  `change_before` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额变动之前',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  PRIMARY KEY (`id`),
  KEY `idx_createdat` (`created_at`) USING BTREE,
  KEY `idx_merchantaccount_currency` (`merchant_account`,`currency`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商户余额流水表';

-- ----------------------------
-- Records of merchant_balance_change_log
-- ----------------------------

-- ----------------------------
-- Table structure for merchant_collection_balance
-- ----------------------------
DROP TABLE IF EXISTS `merchant_collection_balance`;
CREATE TABLE `merchant_collection_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_account` varchar(20) NOT NULL COMMENT '商户号',
  `currency` char(3) NOT NULL COMMENT '币种',
  `is_auto` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否启用自动转入代付0-否，1-是',
  `limit_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '设定金额(启用自动转入代付时的设置)',
  `balance` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '充值余额',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mb_m_id_c_id` (`merchant_account`,`currency`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商户代收余额表';

-- ----------------------------
-- Records of merchant_collection_balance
-- ----------------------------

-- ----------------------------
-- Table structure for merchant_pay_balance
-- ----------------------------
DROP TABLE IF EXISTS `merchant_pay_balance`;
CREATE TABLE `merchant_pay_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_account` varchar(20) NOT NULL COMMENT '商户号',
  `currency` char(3) NOT NULL COMMENT '币种',
  `balance` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '提款余额',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mb_m_id_c_id` (`merchant_account`,`currency`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商户代付余额表';

-- ----------------------------
-- Records of merchant_pay_balance
-- ----------------------------

-- ----------------------------
-- Table structure for merchant_secret
-- ----------------------------
DROP TABLE IF EXISTS `merchant_secret`;
CREATE TABLE `merchant_secret` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` int(11) NOT NULL COMMENT '商户id',
  `merchant_key` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商户私钥',
  `merchant_public_key` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商户公钥',
  `secret_key` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '平台私钥',
  `public_key` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '平台公钥',
  PRIMARY KEY (`id`),
  KEY `mer_secret_mer_id` (`merchant_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商户与平台秘钥表';

-- ----------------------------
-- Records of merchant_secret
-- ----------------------------

-- ----------------------------
-- Table structure for orders_attachment
-- ----------------------------
DROP TABLE IF EXISTS `orders_attachment`;
CREATE TABLE `orders_attachment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inner_order_sn` varchar(60) NOT NULL COMMENT '内部订单号',
  `url` json DEFAULT NULL COMMENT '图片地址',
  `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0是order_collection表数据 1=是ordes_pay数据',
  `remark` varchar(400) DEFAULT '' COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `o_at_inner_order_sn_IDX` (`inner_order_sn`,`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='订单附件';

-- ----------------------------
-- Records of orders_attachment
-- ----------------------------

-- ----------------------------
-- Table structure for orders_collection
-- ----------------------------
DROP TABLE IF EXISTS `orders_collection`;
CREATE TABLE `orders_collection` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '代收充值订单号（商户号）',
  `inner_order_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '平台内部订单号',
  `pay_inner_order_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '代付平台内部订单号（提现）',
  `merchant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户站点',
  `merchant_account` varchar(20) NOT NULL DEFAULT '' COMMENT '商户号',
  `payment` varchar(20) NOT NULL DEFAULT '' COMMENT '代收支付方式',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(30) NOT NULL DEFAULT '' COMMENT '用户充值账号',
  `currency` varchar(11) NOT NULL DEFAULT '' COMMENT '货币',
  `amount` decimal(15,2) NOT NULL COMMENT '代收金额',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '处理人',
  `order_status` enum('success','fail','waiting','canceled') NOT NULL DEFAULT 'waiting' COMMENT '订单状态',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1=待匹配 2=待上传凭证 3=上传凭证超时 4=待确认 5=确认超时 6=订单完成 7=订单异常 8=进行中 9=订单失败 10=取消订单',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `call_back_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=未回调，1=支付上传凭证回调成功，2=收款确认回调成功，3=失败',
  `callback_url` varchar(200) NOT NULL DEFAULT '' COMMENT '回调地址',
  `station_url` varchar(100) NOT NULL DEFAULT '' COMMENT '跳转到站点地址',
  `order_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '订单类型：1内充订单 2兜底订单',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '代付备注',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uni_innerordersn` (`inner_order_sn`) USING BTREE,
  UNIQUE KEY `ord_coll_order_sn` (`order_sn`,`merchant_id`) USING BTREE,
  KEY `n_merchant_status` (`merchant_id`,`status`),
  KEY `idx_payinnerordersn` (`pay_inner_order_sn`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='代收订单表(充值)';

-- ----------------------------
-- Records of orders_collection
-- ----------------------------

-- ----------------------------
-- Table structure for orders_collection_trial
-- ----------------------------
DROP TABLE IF EXISTS `orders_collection_trial`;
CREATE TABLE `orders_collection_trial` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `orders_collection_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '平台内部订单号(充值)',
  `action_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '处理方案：1-待处理，2-订单失败，3-订单完成',
  `problem_source` tinyint(4) NOT NULL DEFAULT '0' COMMENT '问题归责：0，待处理，1.无法确定,2.充值方问题,3.提款方问题,4.圆满解决',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人',
  `description` varchar(255) DEFAULT NULL COMMENT '事件描述',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `pay_status` tinyint(2) unsigned DEFAULT NULL COMMENT '代付状态:0=失败 1=成功',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_ordersn` (`orders_collection_sn`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='争议订单处理方案表';

-- ----------------------------
-- Records of orders_collection_trial
-- ----------------------------

-- ----------------------------
-- Table structure for orders_pay
-- ----------------------------
DROP TABLE IF EXISTS `orders_pay`;
CREATE TABLE `orders_pay` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '代付(提现)订单号（商户号）',
  `inner_order_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '平台内部订单号',
  `merchant_id` int(11) NOT NULL COMMENT '代付站点',
  `merchant_account` varchar(20) NOT NULL DEFAULT '' COMMENT '商户号',
  `payment` varchar(20) NOT NULL DEFAULT '' COMMENT '代付支付方式',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(30) NOT NULL DEFAULT '' COMMENT '提款账号',
  `currency` varchar(11) NOT NULL DEFAULT '' COMMENT '货币',
  `amount` decimal(15,2) NOT NULL COMMENT '代付金额',
  `match_timeout_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '匹配超时金额',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '处理人',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '代付备注',
  `pay_status` enum('waiting','fail','success') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waiting' COMMENT '代付状态：success:出款成功，fail:出款失败，waiting:处理中',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1待匹配  2待上传凭证 3上传凭证超时 4待确认 5待确认超时 6订单成功 7订单异常 8进行中 9订单失败',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `callback_url` varchar(100) NOT NULL DEFAULT '' COMMENT '回调地址',
  `call_back_status` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uni_innerordersn` (`inner_order_sn`) USING BTREE,
  UNIQUE KEY `uni_ordersn_merchantid` (`order_sn`,`merchant_id`) USING BTREE,
  KEY `n_merchant_status` (`merchant_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='代付订单表（提现）';

-- ----------------------------
-- Records of orders_pay
-- ----------------------------

-- ----------------------------
-- Table structure for pay_config
-- ----------------------------
DROP TABLE IF EXISTS `pay_config`;
CREATE TABLE `pay_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `merchant_account` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '商户号',
  `name` char(40) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL COMMENT ' 支付类型',
  `partner_id` varchar(100) CHARACTER SET utf8 NOT NULL COMMENT '三方商户号',
  `key` varchar(2000) CHARACTER SET utf8 NOT NULL COMMENT '私钥',
  `pub_key` varchar(400) CHARACTER SET utf8 NOT NULL COMMENT '公钥',
  `payurl` varchar(200) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '支付地址',
  `ip` varchar(500) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT 'IP白名单',
  `show_type` enum('code','h5') CHARACTER SET utf8 NOT NULL DEFAULT 'h5' COMMENT '支付端',
  `status` enum('default','enabled','disabled') CHARACTER SET utf8 NOT NULL DEFAULT 'default',
  `sort` tinyint(5) unsigned NOT NULL DEFAULT '0',
  `return_type` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT 'json' COMMENT '返回类型',
  `pay_callback_domain` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '生成支付回调域名',
  `params` json NOT NULL COMMENT '支付差异参数',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uni_merchantaccount_type` (`merchant_account`,`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='三方代付配置表';

-- ----------------------------
-- Records of pay_config
-- ----------------------------

-- ----------------------------
-- Table structure for pay_log
-- ----------------------------
DROP TABLE IF EXISTS `pay_log`;
CREATE TABLE `pay_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(64) NOT NULL DEFAULT '',
  `payUrl` varchar(100) NOT NULL DEFAULT '' COMMENT '请求接口地址',
  `pay_type` varchar(20) NOT NULL DEFAULT '' COMMENT '支付方式',
  `json` varchar(500) NOT NULL DEFAULT '' COMMENT '请求参数',
  `response` varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='三方代付请求日志表';

-- ----------------------------
-- Records of pay_log
-- ----------------------------

-- ----------------------------
-- Table structure for pay_type
-- ----------------------------
DROP TABLE IF EXISTS `pay_type`;
CREATE TABLE `pay_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父级id',
  `name` varchar(100) NOT NULL COMMENT '支付名称',
  `name_code` int(11) NOT NULL COMMENT '支付编码',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pay_type_UN` (`name_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付类型(支付通道)';

-- ----------------------------
-- Records of pay_type
-- ----------------------------

-- ----------------------------
-- Table structure for tag
-- ----------------------------
DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '标签名称',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '添加人',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='标签';

-- ----------------------------
-- Records of tag
-- ----------------------------

-- ----------------------------
-- Table structure for transfer_record
-- ----------------------------
DROP TABLE IF EXISTS `transfer_record`;
CREATE TABLE `transfer_record` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(20) DEFAULT NULL COMMENT '订单号',
  `pay_inner_order_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '平台内部订单号(提现)',
  `pay_config_id` int(11) unsigned DEFAULT NULL,
  `bank_card_name` varchar(100) DEFAULT NULL COMMENT '收款人姓名',
  `bank` varchar(30) DEFAULT NULL COMMENT '银行代码',
  `bank_card_account` varchar(100) DEFAULT NULL COMMENT '收款人账号 ',
  `amount` decimal(16,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '转账金额',
  `received_amount` decimal(16,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际到账金额',
  `merchant_id` bigint(20) DEFAULT NULL COMMENT '商户id',
  `status` tinyint(2) NOT NULL COMMENT '1=待处理，2=转账成功，0=转账失败',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='转账记录表';

-- ----------------------------
-- Records of transfer_record
-- ----------------------------

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_account` varchar(30) NOT NULL COMMENT '用户支付/收款账号',
  `username` varchar(30) NOT NULL COMMENT '用户姓名',
  `merchant_id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1正常 0 拉黑',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uni_useraccount` (`user_account`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ----------------------------
-- Records of user
-- ----------------------------

-- ----------------------------
-- Table structure for user_sms
-- ----------------------------
DROP TABLE IF EXISTS `user_sms`;
CREATE TABLE `user_sms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `merchant_account` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '商户号',
  `message` varchar(500) NOT NULL DEFAULT '' COMMENT '消息内容',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_userid` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户短信表';

-- ----------------------------
-- Records of user_sms
-- ----------------------------

-- ----------------------------
-- Table structure for user_tag
-- ----------------------------
DROP TABLE IF EXISTS `user_tag`;
CREATE TABLE `user_tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户 id',
  `tag_id` int(11) NOT NULL COMMENT '标签 id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户标签关联表';

-- ----------------------------
-- Records of user_tag
-- ----------------------------
SET FOREIGN_KEY_CHECKS=1;
