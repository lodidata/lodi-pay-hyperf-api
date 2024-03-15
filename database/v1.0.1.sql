ALTER TABLE `orders_pay`
MODIFY COLUMN `pay_status`  enum('waiting','fail','success') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'waiting' COMMENT '代付状态：success:出款成功，fail:出款失败，waiting:处理中',

ALTER TABLE `orders_collection`
MODIFY COLUMN `order_type`  tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '订单类型：1内充订单 2兜底订单 3三方代付';

ALTER TABLE `transfer_record`
DROP COLUMN `order_sn`,
DROP COLUMN `bank_card_name`,
DROP COLUMN `received_amount`,
MODIFY COLUMN `pay_config_id`  int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `pay_inner_order_sn`,
MODIFY COLUMN `bank`  varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '银行代码' AFTER `pay_config_id`,
MODIFY COLUMN `bank_card_account`  varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款人账号 ' AFTER `bank`,
MODIFY COLUMN `merchant_id`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商户id' AFTER `amount`,
MODIFY COLUMN `status`  tinyint(2) NOT NULL DEFAULT 1 COMMENT '1=待处理，2=转账成功，0=转账失败' AFTER `merchant_id`,
MODIFY COLUMN `remark`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '备注' AFTER `status`,
ADD COLUMN `inner_order_sn`  varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '三方代付订单号(同orders_collection表)' AFTER `pay_inner_order_sn`,
ADD INDEX `idx_innerordersn` (`inner_order_sn`) USING BTREE ,
ADD INDEX `idx_payinnerordresn` (`pay_inner_order_sn`) USING BTREE ,
ADD INDEX `idx_createdat` (`created_at`) USING BTREE ;

ALTER TABLE `orders_pay`
MODIFY COLUMN `status`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1待匹配  2待上传凭证 3上传凭证超时 4待确认 5待确认超时 6订单成功 7订单异常 8进行中 9订单失败 10订单取消 11订单驳回' AFTER `pay_status`;

ALTER TABLE `merchant_balance_change_log`
MODIFY COLUMN `transaction_type`  tinyint(4) NOT NULL DEFAULT 1 COMMENT '交易类型：1=充值，2=提现，3=点位扣除金额, 4=余额手动划转，5-余额自动划转, 6=驳回, 7=三方代付' AFTER `currency`;

#修复历史数据
UPDATE orders_pay set `status`=11 WHERE `status`=10;

