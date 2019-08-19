/*
Navicat MySQL Data Transfer

Source Server         : centos@local
Source Server Version : 50552
Source Host           : 192.168.8.250:3306
Source Database       : tl_datasync

Target Server Type    : MYSQL
Target Server Version : 50552
File Encoding         : 65001

Date: 2017-10-10 14:57:44
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for tl_datasync_notify
-- ----------------------------
DROP TABLE IF EXISTS `tl_datasync_notify`;
CREATE TABLE `tl_datasync_notify` (
  `user_id` int(11) NOT NULL DEFAULT '0',
  `msisdn` varchar(20) NOT NULL,
  `trace_id` int(11) NOT NULL,
  `trivia_id` int(11) NOT NULL,
  `score` varchar(50) NOT NULL,
  `sent` int(1) NOT NULL DEFAULT '0',
  `delivered` int(1) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  `sleep` int(11) NOT NULL DEFAULT '0',
  `suspended` int(1) NOT NULL DEFAULT '0',
  `service_id` int(1) NOT NULL DEFAULT '0',
  `process_start` datetime DEFAULT NULL,
  `process_end` datetime DEFAULT NULL,
  `start` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_datasync_trivia
-- ----------------------------
DROP TABLE IF EXISTS `tl_datasync_trivia`;
CREATE TABLE `tl_datasync_trivia` (
  `user_id` int(11) NOT NULL,
  `msisdn` varchar(20) NOT NULL,
  `service_id` int(1) NOT NULL,
  `trivia_id` int(11) NOT NULL,
  `trace_id` int(11) NOT NULL,
  `sleep` int(11) NOT NULL,
  `sent` int(1) NOT NULL DEFAULT '0',
  `delivered` int(1) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  `process_start` datetime DEFAULT NULL,
  `process_end` datetime DEFAULT NULL,
  `suspended` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_services_retries
-- ----------------------------
DROP TABLE IF EXISTS `tl_services_retries`;
CREATE TABLE `tl_services_retries` (
  `date_created` date NOT NULL,
  `time` time NOT NULL,
  `count` int(11) DEFAULT '0',
  `completed` int(1) DEFAULT NULL,
  `service_id` int(1) NOT NULL,
  `date_completed` datetime DEFAULT NULL,
  UNIQUE KEY `unique_date` (`date_created`,`service_id`) USING BTREE,
  KEY `fk_service_id_retries` (`service_id`),
  CONSTRAINT `fk_service_id_retries` FOREIGN KEY (`service_id`) REFERENCES `tl_contents`.`tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_servicing_contents
-- ----------------------------
DROP TABLE IF EXISTS `tl_servicing_contents`;
CREATE TABLE `tl_servicing_contents` (
  `tracer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `process_start` datetime NOT NULL,
  `process_end` datetime DEFAULT NULL,
  `process_state` int(1) NOT NULL DEFAULT '0',
  `suspended` int(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `unique_tracer_id_service` (`tracer_id`,`service_id`,`user_id`) USING BTREE,
  KEY `fk_service_id_serving` (`service_id`),
  KEY `fk_content_id_serving` (`content_id`),
  CONSTRAINT `fk_service_id_serving` FOREIGN KEY (`service_id`) REFERENCES `tl_contents`.`tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_servicing_skipped
-- ----------------------------
DROP TABLE IF EXISTS `tl_servicing_skipped`;
CREATE TABLE `tl_servicing_skipped` (
  `service_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date` date NOT NULL,
  UNIQUE KEY `unique_skipped_date_entry` (`service_id`,`user_id`,`date`) USING BTREE,
  KEY `fk_skipped_user_id` (`user_id`),
  CONSTRAINT `fk_skipped_service_id` FOREIGN KEY (`service_id`) REFERENCES `tl_contents`.`tl_services` (`service_local_id`) ON UPDATE NO ACTION,
  CONSTRAINT `fk_skipped_user_id` FOREIGN KEY (`user_id`) REFERENCES `tl_contents`.`tl_users` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_servicing_trivia
-- ----------------------------
DROP TABLE IF EXISTS `tl_servicing_trivia`;
CREATE TABLE `tl_servicing_trivia` (
  `tracer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trivia_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `cross_sell_id` int(11) NOT NULL DEFAULT '0',
  `user_answer` varchar(50) DEFAULT NULL,
  `score` varchar(20) DEFAULT NULL,
  `process_start` datetime NOT NULL,
  `process_end` datetime DEFAULT NULL,
  `process_state` int(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `unique_trace_id` (`tracer_id`),
  KEY `fk_servicing_trivia_service_id` (`service_id`),
  CONSTRAINT `fk_servicing_trivia_service_id` FOREIGN KEY (`service_id`) REFERENCES `tl_contents`.`tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
