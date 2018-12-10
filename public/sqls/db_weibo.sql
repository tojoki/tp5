/*
SQLyog Ultimate v12.09 (64 bit)
MySQL - 5.5.57 : Database - db_weibo
*********************************************************************
*/


/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`db_weibo` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `db_weibo`;

/*Table structure for table `wb_comment` */

DROP TABLE IF EXISTS `wb_comment`;

CREATE TABLE `wb_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wb_id` varchar(255) DEFAULT NULL COMMENT '微博id',
  `wb_uid` varchar(255) DEFAULT NULL COMMENT '微博用户id',
  `wb_screen_name` varchar(255) DEFAULT NULL COMMENT '微博用户昵称',
  `uid` varchar(255) DEFAULT NULL COMMENT '评论人id',
  `screen_name` varchar(255) DEFAULT NULL COMMENT '评论人昵称',
  `comment_id` varchar(255) DEFAULT NULL COMMENT '评论id',
  `comment_content` longtext COMMENT '评论内容',
  `ctime` int(11) DEFAULT NULL COMMENT '评论时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

/*Table structure for table `wb_user` */

DROP TABLE IF EXISTS `wb_user`;

CREATE TABLE `wb_user` (
  `uid` varchar(255) COLLATE utf8_estonian_ci NOT NULL COMMENT '微博uid',
  `screen_name` varchar(255) COLLATE utf8_estonian_ci DEFAULT NULL,
  `access_token` varchar(255) COLLATE utf8_estonian_ci DEFAULT NULL,
  `expires_in` int(11) DEFAULT NULL COMMENT 'access_token失效时间',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_estonian_ci;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
