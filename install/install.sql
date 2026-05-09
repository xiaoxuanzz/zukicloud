DROP TABLE IF EXISTS `pre_config`;
CREATE TABLE `pre_config` (
  `k` varchar(32) NOT NULL,
  `v` text NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `pre_config` VALUES ('version', 'v2');
INSERT IGNORE INTO `pre_config` VALUES ('db_version', '1001');
INSERT IGNORE INTO `pre_config` VALUES ('admin_user', 'admin');
INSERT IGNORE INTO `pre_config` VALUES ('admin_pwd', '123456');
INSERT IGNORE INTO `pre_config` VALUES ('blackip', '');
INSERT IGNORE INTO `pre_config` VALUES ('title', 'ZuKizZ');
INSERT IGNORE INTO `pre_config` VALUES ('keywords', '外链网盘,免费外链,免费图床,图片外链');
INSERT IGNORE INTO `pre_config` VALUES ('description', 'ZuKizZ提供大容量云存储服务');
INSERT IGNORE INTO `pre_config` VALUES ('iptype', '0');
INSERT IGNORE INTO `pre_config` VALUES ('filesearch', '1');
INSERT IGNORE INTO `pre_config` VALUES ('storage', 'local');
INSERT IGNORE INTO `pre_config` VALUES ('filepath', '');
INSERT IGNORE INTO `pre_config` VALUES ('aliyun_ak', '');
INSERT IGNORE INTO `pre_config` VALUES ('aliyun_sk', '');
INSERT IGNORE INTO `pre_config` VALUES ('name_block', '');
INSERT IGNORE INTO `pre_config` VALUES ('type_block', '');
INSERT IGNORE INTO `pre_config` VALUES ('type_image', 'png|jpg|jpeg|gif|bmp|webp|ico|svg|svgz|tif|tiff|heic|exif');
INSERT IGNORE INTO `pre_config` VALUES ('type_audio', 'mp3|wav|ogg|m4a|flac|aac');
INSERT IGNORE INTO `pre_config` VALUES ('type_video', 'mp4|webm|flv|f4v|mov|3gp|3gpp|avi|mpg|mpeg|wmv|mkv|ts|dat|asf|mts|m2ts|m3u8|m4v');
INSERT IGNORE INTO `pre_config` VALUES ('green_check', '0');
INSERT IGNORE INTO `pre_config` VALUES ('green_check_region', 'cn-beijing');
INSERT IGNORE INTO `pre_config` VALUES ('green_check_porn', '0');
INSERT IGNORE INTO `pre_config` VALUES ('green_check_terrorism', '0');
INSERT IGNORE INTO `pre_config` VALUES ('green_label_porn', 'sexy,porn');
INSERT IGNORE INTO `pre_config` VALUES ('green_label_terrorism', 'bloody,explosion,outfit,logo,weapon,politics');
INSERT IGNORE INTO `pre_config` VALUES ('gg_file', '网站所有文件内容均由用户自行上传分享，本站严格遵守国家相关法律法规，尊重著作权、版权等第三方权利，如果当前文件侵犯了您的相关权利，请联系我们，我们将及时处理。');
INSERT IGNORE INTO `pre_config` VALUES ('isupload', '1');
INSERT IGNORE INTO `pre_config` VALUES ('upload_size', '0');
INSERT IGNORE INTO `pre_config` VALUES ('videoreview', '0');
INSERT IGNORE INTO `pre_config` VALUES ('userlogin', '1');
INSERT IGNORE INTO `pre_config` VALUES ('forcelogin', '0');
INSERT IGNORE INTO `pre_config` VALUES ('upload_code_open', '0');
INSERT IGNORE INTO `pre_config` VALUES ('upload_code', '');
INSERT IGNORE INTO `pre_config` VALUES ('upload_concurrent', '5');

DROP TABLE IF EXISTS `pre_file`;
CREATE TABLE `pre_file` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `size` int(11) unsigned NOT NULL,
  `hash` varchar(32) NOT NULL,
  `addtime` datetime NOT NULL,
  `lasttime` datetime DEFAULT NULL,
  `ontime` int(11) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL,
  `hide` int(1) NOT NULL DEFAULT '0',
  `pwd` varchar(255) DEFAULT NULL,
  `block` int(1) NOT NULL DEFAULT '0',
  `count` int(11) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `pre_user`;
CREATE TABLE `pre_user` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `openid` varchar(150) NOT NULL,
  `password` varchar(64) NOT NULL DEFAULT '',
  `nickname` varchar(255) NOT NULL,
  `faceimg` varchar(255) DEFAULT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT '1',
  `regip` varchar(20) DEFAULT NULL,
  `loginip` varchar(20) DEFAULT NULL,
  `level` tinyint(4) NOT NULL DEFAULT '0',
  `addtime` datetime NOT NULL,
  `lasttime` datetime NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `openid` (`openid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1000;

DROP TABLE IF EXISTS `pre_storage`;
CREATE TABLE `pre_storage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'local',
  `maxsize` bigint(20) unsigned NOT NULL DEFAULT '0',
  `usedsize` bigint(20) unsigned NOT NULL DEFAULT '0',
  `state` tinyint(1) NOT NULL DEFAULT '1',
  `orders` int(11) NOT NULL DEFAULT '0',
  `config` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
