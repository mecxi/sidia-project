/*
Navicat MySQL Data Transfer

Source Server         : centos@local
Source Server Version : 50552
Source Host           : 192.168.8.250:3306
Source Database       : tl_gateway

Target Server Type    : MYSQL
Target Server Version : 50552
File Encoding         : 65001

Date: 2017-10-10 14:58:16
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for tl_bulk_sync
-- ----------------------------
DROP TABLE IF EXISTS `tl_bulk_sync`;
CREATE TABLE `tl_bulk_sync` (
  `cross_sell` varchar(50) DEFAULT NULL,
  `date_string` varchar(10) DEFAULT NULL,
  `service_id` int(1) DEFAULT NULL,
  `process_start` datetime DEFAULT NULL,
  `process_end` datetime DEFAULT NULL,
  UNIQUE KEY `unique_sell_on_date` (`cross_sell`,`date_string`,`service_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_delay_content_delivery
-- ----------------------------
DROP TABLE IF EXISTS `tl_delay_content_delivery`;
CREATE TABLE `tl_delay_content_delivery` (
  `user_id` int(11) NOT NULL,
  `service_id` int(1) NOT NULL,
  `msisdn` varchar(50) NOT NULL,
  `sleep` int(11) NOT NULL,
  `sent` int(1) NOT NULL,
  `date_created` date NOT NULL,
  `process_start` datetime DEFAULT NULL,
  `process_end` datetime DEFAULT NULL,
  UNIQUE KEY `unique_date_msisdn` (`msisdn`,`date_created`,`service_id`) USING BTREE,
  KEY `fk_service_id_delivery` (`service_id`),
  CONSTRAINT `fk_service_id_delivery` FOREIGN KEY (`service_id`) REFERENCES `tl_contents`.`tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_sync_services_requests
-- ----------------------------
DROP TABLE IF EXISTS `tl_sync_services_requests`;
CREATE TABLE `tl_sync_services_requests` (
  `msisdn` varchar(50) NOT NULL,
  `keyword` varchar(10) DEFAULT NULL,
  `req_type` varchar(50) NOT NULL,
  `req_service` int(1) NOT NULL,
  `req_state` int(1) NOT NULL DEFAULT '0',
  `by_date` datetime NOT NULL,
  `req_sent` int(1) NOT NULL DEFAULT '0',
  `processing` int(1) DEFAULT NULL,
  `resp_time` datetime DEFAULT NULL,
  `resp_type` int(1) DEFAULT NULL,
  `resp_desc` varchar(50) DEFAULT NULL,
  `resp_service_id` varchar(50) DEFAULT NULL,
  `resp_product_id` varchar(50) DEFAULT NULL,
  `transID` varchar(255) DEFAULT NULL,
  `gracePeriod` int(1) NOT NULL DEFAULT '0',
  `daily_count` int(11) NOT NULL DEFAULT '0',
  `date_created` date NOT NULL,
  `code` int(2) NOT NULL DEFAULT '0',
  UNIQUE KEY `unique_req_msisdn_by_date` (`msisdn`,`keyword`,`date_created`,`req_type`,`resp_product_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_threading_services
-- ----------------------------
DROP TABLE IF EXISTS `tl_threading_services`;
CREATE TABLE `tl_threading_services` (
  `service_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `completed` int(1) DEFAULT '0',
  UNIQUE KEY `unique_service_users` (`service_id`,`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
