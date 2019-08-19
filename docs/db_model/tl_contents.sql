/*
Navicat MySQL Data Transfer

Source Server         : centos@local
Source Server Version : 50552
Source Host           : 192.168.8.250:3306
Source Database       : tl_contents

Target Server Type    : MYSQL
Target Server Version : 50552
File Encoding         : 65001

Date: 2017-10-10 14:57:26
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for bc_medicine_data
-- ----------------------------
DROP TABLE IF EXISTS `bc_medicine_data`;
CREATE TABLE `bc_medicine_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation` varchar(255) DEFAULT NULL,
  `rayon` varchar(255) DEFAULT NULL,
  `prix_gros` int(11) DEFAULT NULL,
  `prix_pub` int(11) DEFAULT NULL,
  `prix_pub2` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5096 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_billing_type
-- ----------------------------
DROP TABLE IF EXISTS `tl_billing_type`;
CREATE TABLE `tl_billing_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) DEFAULT NULL,
  `days` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_broadcast_type
-- ----------------------------
DROP TABLE IF EXISTS `tl_broadcast_type`;
CREATE TABLE `tl_broadcast_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_draw_type
-- ----------------------------
DROP TABLE IF EXISTS `tl_draw_type`;
CREATE TABLE `tl_draw_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_keyword
-- ----------------------------
DROP TABLE IF EXISTS `tl_keyword`;
CREATE TABLE `tl_keyword` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_message_type
-- ----------------------------
DROP TABLE IF EXISTS `tl_message_type`;
CREATE TABLE `tl_message_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_process_type
-- ----------------------------
DROP TABLE IF EXISTS `tl_process_type`;
CREATE TABLE `tl_process_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `process` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_products
-- ----------------------------
DROP TABLE IF EXISTS `tl_products`;
CREATE TABLE `tl_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_sdp_id` varchar(255) NOT NULL,
  `billing_rate` int(11) NOT NULL,
  `billing_cycle_id` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sdp_productID` (`product_sdp_id`) USING BTREE,
  KEY `fk_billing_cycle_id` (`billing_cycle_id`),
  CONSTRAINT `fk_billing_cycle_id` FOREIGN KEY (`billing_cycle_id`) REFERENCES `tl_billing_type` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_service_base_code_references
-- ----------------------------
DROP TABLE IF EXISTS `tl_service_base_code_references`;
CREATE TABLE `tl_service_base_code_references` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_local_id` int(11) NOT NULL,
  `comments` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_base_service_id` (`service_local_id`),
  CONSTRAINT `fk_base_service_id` FOREIGN KEY (`service_local_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_service_trivia
-- ----------------------------
DROP TABLE IF EXISTS `tl_service_trivia`;
CREATE TABLE `tl_service_trivia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(160) NOT NULL,
  `answer` varchar(2) NOT NULL,
  `correct_notify` varchar(160) NOT NULL,
  `incorrect_notify` varchar(160) NOT NULL,
  `score` int(2) NOT NULL DEFAULT '0',
  `status` int(1) NOT NULL DEFAULT '0',
  `service_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_question_service_id` (`question`,`service_id`) USING BTREE,
  KEY `fk_trivia_service_id` (`service_id`),
  CONSTRAINT `fk_trivia_service_id` FOREIGN KEY (`service_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services
-- ----------------------------
DROP TABLE IF EXISTS `tl_services`;
CREATE TABLE `tl_services` (
  `service_local_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `service_type` int(11) NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `broadcast` int(1) DEFAULT NULL,
  `broadcast_type` int(1) DEFAULT NULL,
  `broadcast_length` int(2) NOT NULL DEFAULT '1',
  `cross_sell_services` int(1) NOT NULL DEFAULT '0',
  `cross_sell_on_sub` int(1) NOT NULL DEFAULT '0',
  `opening_message` int(1) NOT NULL DEFAULT '0',
  `set_thread` int(11) NOT NULL DEFAULT '10',
  `free_period` int(1) NOT NULL DEFAULT '0',
  `service_sdp_id` varchar(50) DEFAULT NULL,
  `accesscode` varchar(50) DEFAULT NULL,
  `sp_id` varchar(50) DEFAULT NULL,
  `sp_password` varchar(50) DEFAULT NULL,
  `last_process_id` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime DEFAULT NULL,
  `keywords_length` int(1) NOT NULL DEFAULT '5',
  `active` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_local_id`),
  UNIQUE KEY `unique_serviceID_productID` (`service_sdp_id`) USING BTREE,
  KEY `fk_service_brcastype_id` (`broadcast_type`),
  KEY `fk_service_type` (`service_type`),
  CONSTRAINT `fk_service_brcastype_id` FOREIGN KEY (`broadcast_type`) REFERENCES `tl_broadcast_type` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_service_type` FOREIGN KEY (`service_type`) REFERENCES `tl_services_type` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_billing
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_billing`;
CREATE TABLE `tl_services_billing` (
  `day` int(11) NOT NULL DEFAULT '0',
  `date` date NOT NULL,
  `total_new_day` int(11) NOT NULL DEFAULT '0',
  `total_unsubs_day` int(11) NOT NULL DEFAULT '0',
  `total_subs` int(11) NOT NULL DEFAULT '0',
  `total_day_bills` varchar(255) NOT NULL DEFAULT '0',
  `target_day_bills` varchar(255) NOT NULL DEFAULT '0',
  `rate_day_bills` varchar(255) NOT NULL DEFAULT '0',
  `repeat_bills` varchar(255) NOT NULL DEFAULT '0',
  `total_overall_bills` varchar(255) NOT NULL DEFAULT '0',
  `service_id` int(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `unique_date_service_id` (`date`,`service_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_contents
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_contents`;
CREATE TABLE `tl_services_contents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `message` varchar(160) NOT NULL,
  `status` int(1) NOT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_content_service_id` (`service_id`,`message`) USING BTREE,
  CONSTRAINT `fk_service_id_contents` FOREIGN KEY (`service_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_cross_sell_references
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_cross_sell_references`;
CREATE TABLE `tl_services_cross_sell_references` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_local_id` int(11) NOT NULL,
  `cross_sell_service_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_local_target_service` (`service_local_id`,`cross_sell_service_id`) USING BTREE,
  KEY `fk_cross_service_id_local` (`cross_sell_service_id`),
  CONSTRAINT `fk_cross_service_id` FOREIGN KEY (`service_local_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_cross_service_id_local` FOREIGN KEY (`cross_sell_service_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_draw_engine
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_draw_engine`;
CREATE TABLE `tl_services_draw_engine` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `notify` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `draw_type_id` int(11) NOT NULL,
  `draw_win_no` int(11) NOT NULL DEFAULT '1',
  `draw_engine_type` int(1) NOT NULL DEFAULT '1',
  `draw_win_rollout` int(1) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_draw_type_id` (`draw_type_id`),
  CONSTRAINT `fk_draw_type_id` FOREIGN KEY (`draw_type_id`) REFERENCES `tl_draw_type` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_draw_references
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_draw_references`;
CREATE TABLE `tl_services_draw_references` (
  `service_local_id` int(11) NOT NULL,
  `service_draw_id` int(11) NOT NULL,
  UNIQUE KEY `unique_service_local_draw_id` (`service_local_id`,`service_draw_id`) USING BTREE,
  KEY `fk_draw_ref_id` (`service_draw_id`),
  CONSTRAINT `fk_draw_ref_id` FOREIGN KEY (`service_draw_id`) REFERENCES `tl_services_draw_engine` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_service_id_draw` FOREIGN KEY (`service_local_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_draw_winners
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_draw_winners`;
CREATE TABLE `tl_services_draw_winners` (
  `service_draw_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL DEFAULT '0',
  `entries` int(11) NOT NULL DEFAULT '0',
  `select_type` int(1) NOT NULL DEFAULT '0',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `notify` int(1) NOT NULL DEFAULT '0',
  KEY `fk_service_draw_id_win` (`service_draw_id`),
  CONSTRAINT `fk_service_draw_id_win` FOREIGN KEY (`service_draw_id`) REFERENCES `tl_services_draw_engine` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_keywords
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_keywords`;
CREATE TABLE `tl_services_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword_id` int(11) NOT NULL,
  `service_local_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_keyword_id_service_id` (`keyword_id`,`service_local_id`) USING BTREE,
  KEY `fk_service_id_key` (`service_local_id`),
  CONSTRAINT `fk_keyword_id` FOREIGN KEY (`keyword_id`) REFERENCES `tl_keyword` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_service_id_key` FOREIGN KEY (`service_local_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_logs
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_logs`;
CREATE TABLE `tl_services_logs` (
  `service_local_id` int(11) NOT NULL,
  `process_id` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `result` int(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `unique_service_log_process_date` (`service_local_id`,`process_id`,`date`,`result`) USING BTREE,
  KEY `fk_log_process_id` (`process_id`),
  CONSTRAINT `fk_log_process_id` FOREIGN KEY (`process_id`) REFERENCES `tl_process_type` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_log_service_id` FOREIGN KEY (`service_local_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_messages
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_messages`;
CREATE TABLE `tl_services_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` varchar(160) NOT NULL,
  `correct_reply` varchar(160) DEFAULT NULL,
  `incorrect_reply` varchar(160) DEFAULT NULL,
  `message_type_id` int(2) NOT NULL,
  `target_service_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_message_type` (`message_type_id`,`target_service_id`) USING BTREE,
  KEY `fk_message_service_id` (`target_service_id`),
  CONSTRAINT `fk_message_service_id` FOREIGN KEY (`target_service_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_message_type` FOREIGN KEY (`message_type_id`) REFERENCES `tl_message_type` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_products
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_products`;
CREATE TABLE `tl_services_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `service_local_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_product_id` (`product_id`),
  KEY `fk_service_local_id` (`service_local_id`),
  CONSTRAINT `fk_product_id` FOREIGN KEY (`product_id`) REFERENCES `tl_products` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_service_local_id` FOREIGN KEY (`service_local_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_type
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_type`;
CREATE TABLE `tl_services_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `total_threads` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_sys_cache_register
-- ----------------------------
DROP TABLE IF EXISTS `tl_sys_cache_register`;
CREATE TABLE `tl_sys_cache_register` (
  `name` varchar(50) DEFAULT NULL,
  `surname` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone_no` varchar(30) NOT NULL,
  `by_date` datetime NOT NULL,
  `token` varchar(255) NOT NULL,
  `trace_id` int(11) NOT NULL,
  UNIQUE KEY `unique_phone_no` (`phone_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_sys_login
-- ----------------------------
DROP TABLE IF EXISTS `tl_sys_login`;
CREATE TABLE `tl_sys_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `surname` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone_no` varchar(30) NOT NULL,
  `role` int(1) NOT NULL,
  `date_created` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id_phone` (`phone_no`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_sys_login_history
-- ----------------------------
DROP TABLE IF EXISTS `tl_sys_login_history`;
CREATE TABLE `tl_sys_login_history` (
  `login_id` int(11) NOT NULL,
  `start_session` datetime NOT NULL,
  `end_session` varchar(255) DEFAULT NULL,
  `remote_address` varchar(50) DEFAULT NULL,
  KEY `fk_login_id` (`login_id`),
  CONSTRAINT `fk_login_id` FOREIGN KEY (`login_id`) REFERENCES `tl_sys_login` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_sys_settings
-- ----------------------------
DROP TABLE IF EXISTS `tl_sys_settings`;
CREATE TABLE `tl_sys_settings` (
  `set_ID` int(11) NOT NULL,
  `set_name` varchar(100) NOT NULL,
  `set_description` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `close_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_users
-- ----------------------------
DROP TABLE IF EXISTS `tl_users`;
CREATE TABLE `tl_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `msisdn` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_users_added_products
-- ----------------------------
DROP TABLE IF EXISTS `tl_users_added_products`;
CREATE TABLE `tl_users_added_products` (
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  UNIQUE KEY `unique_user_product` (`user_id`,`service_id`) USING BTREE,
  KEY `fk_user_added_product_id` (`product_id`),
  KEY `fk_user_id_serviceID` (`service_id`),
  CONSTRAINT `fk_user_added_product_id` FOREIGN KEY (`product_id`) REFERENCES `tl_products` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_id_product` FOREIGN KEY (`user_id`) REFERENCES `tl_users` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_id_serviceID` FOREIGN KEY (`service_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_users_added_services
-- ----------------------------
DROP TABLE IF EXISTS `tl_users_added_services`;
CREATE TABLE `tl_users_added_services` (
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  KEY `fk_user_id` (`user_id`),
  KEY `fk_service_id_added` (`service_id`),
  CONSTRAINT `fk_service_id_added` FOREIGN KEY (`service_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `tl_users` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_users_stats
-- ----------------------------
DROP TABLE IF EXISTS `tl_users_stats`;
CREATE TABLE `tl_users_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `level` int(11) NOT NULL DEFAULT '0',
  `score` int(11) NOT NULL DEFAULT '0',
  `last_brcast_id` int(11) NOT NULL DEFAULT '0',
  `service_id` int(11) NOT NULL,
  `day_entries` int(11) NOT NULL DEFAULT '0',
  `week_entries` int(11) NOT NULL DEFAULT '0',
  `month_entries` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_service` (`user_id`,`service_id`) USING BTREE,
  KEY `fk_service_id_stats` (`service_id`),
  CONSTRAINT `fk_service_id_stats` FOREIGN KEY (`service_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_id_stats` FOREIGN KEY (`user_id`) REFERENCES `tl_users` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_users_stats_draw
-- ----------------------------
DROP TABLE IF EXISTS `tl_users_stats_draw`;
CREATE TABLE `tl_users_stats_draw` (
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `day_entries` int(11) NOT NULL DEFAULT '0',
  `week_entries` int(11) NOT NULL DEFAULT '0',
  `month_entries` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `unique_user_id_service` (`user_id`,`service_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_users_sub_history
-- ----------------------------
DROP TABLE IF EXISTS `tl_users_sub_history`;
CREATE TABLE `tl_users_sub_history` (
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  KEY `fk_user_id_history` (`user_id`),
  KEY `fk_service_id_history` (`service_id`),
  CONSTRAINT `fk_service_id_history` FOREIGN KEY (`service_id`) REFERENCES `tl_services` (`service_local_id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_id_history` FOREIGN KEY (`user_id`) REFERENCES `tl_users` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
