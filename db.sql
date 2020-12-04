-- ----------------------------
-- Demo Database DDL
-- ----------------------------

-- ----------------------------
-- Table structure for t_cart
-- ----------------------------
DROP TABLE IF EXISTS `t_cart`;
CREATE TABLE `t_cart` (
                          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
                          `product_id` int(11) NOT NULL COMMENT '产品id',
                          `sku_id` int(11) NOT NULL COMMENT 'SKU ID',
                          `qty` int(11) NOT NULL DEFAULT 1 COMMENT '数量',
                          `member_id` int(11) NOT NULL DEFAULT 0 COMMENT '会员ID',
                          `session_id` varchar(64) DEFAULT NULL COMMENT '会话ID',
                          `owner_id` int(11) NOT NULL,
                          `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
                          `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '修改时间',
                          `init_update_time` int(11) NOT NULL DEFAULT 0 COMMENT '添加时产品的更新时间',
                          `init_price` float(11,2) NOT NULL DEFAULT 0.00 COMMENT '添加时产品的价格',
                          `init_origin_price` float(11,2) DEFAULT 0.00 COMMENT '添加时产品原价',
                          `init_title` varchar(200) NOT NULL COMMENT '产品标题',
                          `init_img_url` varchar(200) NOT NULL COMMENT '产品图网址',
                          `init_sku_json` varchar(200) DEFAULT NULL COMMENT '产品SKU',
                          PRIMARY KEY (`id`),
                          KEY `owner_id` (`owner_id`,`product_id`),
                          KEY `member_id` (`member_id`,`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='购物车';


-- ----------------------------
-- Table structure for t_product_desc
-- ----------------------------
DROP TABLE IF EXISTS `t_product_desc`;
CREATE TABLE `t_product_desc` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `product_id` int(11) NOT NULL COMMENT '对应产品的id',
                                  `content` mediumtext DEFAULT NULL COMMENT '产品描述',
                                  `mobile_content` mediumtext DEFAULT NULL COMMENT '手机版产品描述',
                                  PRIMARY KEY (`id`) USING BTREE,
                                  UNIQUE KEY `product_id` (`product_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COMMENT='产品描述表';

-- ----------------------------
-- Table structure for t_product_imgs
-- ----------------------------
DROP TABLE IF EXISTS `t_product_imgs`;
CREATE TABLE `t_product_imgs` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `product_id` int(11) NOT NULL COMMENT '对应产品的id',
                                  `is_first` tinyint(11) NOT NULL COMMENT '封面',
                                  `img_url` varchar(200) NOT NULL DEFAULT '' COMMENT '图片网址',
                                  `img_alt` varchar(255) NOT NULL DEFAULT '' COMMENT '图片ALT文字',
                                  `video_url` varchar(200) DEFAULT '' COMMENT '视频网址',
                                  PRIMARY KEY (`id`) USING BTREE,
                                  KEY `product_id` (`product_id`,`is_first`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COMMENT='产品缩略图';


-- ----------------------------
-- Table structure for t_product_skus
-- ----------------------------
DROP TABLE IF EXISTS `t_product_skus`;
CREATE TABLE `t_product_skus` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `product_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品id',
                                  `sku_json` varchar(700) DEFAULT '' COMMENT '商品某一条SKU规格',
                                  `price` float(11,2) DEFAULT 0.00 COMMENT '售价',
                                  `cost` float(11,2) DEFAULT 0.00 COMMENT '成本',
                                  `origin_price` varchar(64) DEFAULT '' COMMENT '原价',
                                  `sku_sn` varchar(100) DEFAULT NULL COMMENT 'SKU编码',
                                  `img_id` int(11) DEFAULT NULL COMMENT '对应图片ID',
                                  `barcode` varchar(100) DEFAULT NULL COMMENT '条码',
                                  `weight` float(11,2) DEFAULT NULL COMMENT '重量，克重',
                                  `hs_code` varchar(255) DEFAULT NULL COMMENT '海关编码',
                                  PRIMARY KEY (`id`) USING BTREE,
                                  KEY `product_id` (`product_id`) USING BTREE,
                                  KEY `sku_sn` (`sku_sn`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COMMENT='商品SKU表';

-- ----------------------------
-- Table structure for t_product_store
-- ----------------------------
DROP TABLE IF EXISTS `t_product_store`;
CREATE TABLE `t_product_store` (
                                   `id` int(11) NOT NULL AUTO_INCREMENT,
                                   `product_id` int(11) NOT NULL COMMENT '产品ID',
                                   `store_id` int(11) NOT NULL COMMENT '店仓ID',
                                   PRIMARY KEY (`id`),
                                   UNIQUE KEY `product_id` (`product_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='产品存放店仓';

-- ----------------------------
-- Table structure for t_products
-- ----------------------------
DROP TABLE IF EXISTS `t_products`;
CREATE TABLE `t_products` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `title` varchar(180) NOT NULL DEFAULT '' COMMENT '品名',
                              `is_online` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否上架,1是,0不是',
                              `has_variant` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否多变体',
                              `product_type` varchar(100) DEFAULT '' COMMENT '产品类型',
                              `vendor` varchar(100) DEFAULT '' COMMENT '供应商',
                              `sort_weight` int(11) DEFAULT 0 COMMENT '排序权重',
                              `is_deleted` tinyint(4) DEFAULT 0 COMMENT '是否删除',
                              `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
                              `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '修改时间',
                              `owner_id` int(11) DEFAULT NULL COMMENT '所有者ID',
                              `tags` varchar(255) DEFAULT NULL COMMENT '标签',
                              `inventory_tracked` tinyint(1) DEFAULT 0 COMMENT '是否跟踪库存数',
                              `allow_overselling` tinyint(1) DEFAULT 0 COMMENT '是否允许超售',
                              `requires_shipping` tinyint(1) DEFAULT 1 COMMENT '是否需要货运',
                              `origin_country_code` varchar(255) DEFAULT 'CN' COMMENT '发货国家代码',
                              `weight_unit` varchar(255) DEFAULT 'g' COMMENT '重量单位',
                              `sku_option_json` varchar(255) DEFAULT NULL,
                              PRIMARY KEY (`id`) USING BTREE,
                              UNIQUE KEY `owner_id` (`owner_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COMMENT='商品档案';