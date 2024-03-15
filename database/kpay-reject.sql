ALTER TABLE `orders_pay`
MODIFY COLUMN `pay_status`  enum('waiting','fail','success','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waiting' COMMENT '代付状态：success:出款成功，fail:出款失败，waiting:处理中, reject:驳回' AFTER `remark`,
MODIFY COLUMN `status`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1待匹配  2待上传凭证 3上传凭证超时 4待确认 5待确认超时 6订单成功 7订单异常 8进行中 9订单失败 10订单驳回' AFTER `pay_status`;

ALTER TABLE `merchant_balance_change_log`
MODIFY COLUMN `transaction_type`  tinyint(4) NOT NULL DEFAULT 1 COMMENT '交易类型：1=充值，2=提现，3=点位扣除金额, 4=余额手动划转，5-余额自动划转, 6=驳回' AFTER `currency`;
