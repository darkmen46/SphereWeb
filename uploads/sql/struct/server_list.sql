SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for server_list
-- ----------------------------
DROP TABLE IF EXISTS `server_list`;
CREATE TABLE `server_list`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(140) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `rate_exp` int(11) NULL DEFAULT NULL,
  `rate_sp` int(11) NULL DEFAULT NULL,
  `rate_adena` int(11) NULL DEFAULT NULL,
  `rate_drop_item` int(11) NULL DEFAULT NULL,
  `rate_spoil` int(11) NULL DEFAULT NULL,
  `date_start_server` datetime NULL DEFAULT NULL,
  `chronicle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `login_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `login_user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `login_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `login_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `game_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `game_user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `game_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `game_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `collection_sql_base_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `check_server_online` int(11) NULL DEFAULT 0,
  `check_loginserver_online_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `check_loginserver_online_port` int(11) NULL DEFAULT 2106,
  `check_gameserver_online_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `check_gameserver_online_port` int(11) NULL DEFAULT 7777,
  `chat_game_enabled` int(11) NULL DEFAULT 0,
  `launcher_enabled` int(11) NULL DEFAULT 0,
  `timezone` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `rest_api_enable` int(11) NOT NULL DEFAULT 0,
  `rest_api_hostname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `rest_api_port` int(11) NULL DEFAULT NULL,
  `rest_api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
