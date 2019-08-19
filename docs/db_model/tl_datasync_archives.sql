/*
Navicat MySQL Data Transfer

Source Server         : centos@local
Source Server Version : 50552
Source Host           : 192.168.8.250:3306
Source Database       : tl_datasync_archives

Target Server Type    : MYSQL
Target Server Version : 50552
File Encoding         : 65001

Date: 2017-10-10 14:57:57
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for archive_players_draw_stats
-- ----------------------------
DROP TABLE IF EXISTS `archive_players_draw_stats`;
CREATE TABLE `archive_players_draw_stats` (
  `user_id` int(11) NOT NULL,
  `msisdn` varchar(20) NOT NULL,
  `service_id` int(11) NOT NULL,
  `service_draw_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `entries` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  UNIQUE KEY `unique_range_values` (`user_id`,`service_id`,`service_draw_id`,`start_date`,`end_date`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_bc_pharma_notify
-- ----------------------------
DROP TABLE IF EXISTS `tl_bc_pharma_notify`;
CREATE TABLE `tl_bc_pharma_notify` (
  `archive_request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `freq_hours` int(11) NOT NULL DEFAULT '0',
  `duration_days` int(11) NOT NULL DEFAULT '0',
  `last_notify` datetime DEFAULT NULL,
  `flag` int(11) NOT NULL DEFAULT '0',
  KEY `pharma_request_id` (`archive_request_id`),
  KEY `archive_user_id` (`user_id`),
  CONSTRAINT `archive_user_id` FOREIGN KEY (`user_id`) REFERENCES `tl_contents`.`tl_users` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `pharma_request_id` FOREIGN KEY (`archive_request_id`) REFERENCES `tl_bc_pharma_service_requests` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for tl_bc_pharma_service_requests
-- ----------------------------
DROP TABLE IF EXISTS `tl_bc_pharma_service_requests`;
CREATE TABLE `tl_bc_pharma_service_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `request_id` int(1) NOT NULL,
  `request_detail` varchar(255) DEFAULT NULL,
  `outcome_result` varchar(255) DEFAULT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pharma_user_id` (`user_id`),
  CONSTRAINT `pharma_user_id` FOREIGN KEY (`user_id`) REFERENCES `tl_contents`.`tl_users` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=latin1;

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
  CONSTRAINT `tl_servicing_contents_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `tl_contents`.`tl_services` (`service_local_id`) ON UPDATE NO ACTION
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
  CONSTRAINT `tl_servicing_trivia_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `tl_contents`.`tl_services` (`service_local_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `date_created` date NOT NULL,
  UNIQUE KEY `unique_user_service_date` (`user_id`,`service_id`,`date_created`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
