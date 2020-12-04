<?php

namespace Kuga\Api\Demo;


class Exception extends \Kuga\Core\Api\Exception
{

    /**
     * 无有效数据（数据库数据不全等。。。）
     * @var int
     */
    public static $INVALID_DATA = 70000;
    /**
     * 找不到SKU
     * @var int
     */
    public static $SKU_NOT_FOUND = 70001;
    /**
     * 没有库存
     * @var int
     */
    public static $STOCK_QTY_OUT = 70002;

    /**
     * 找不到订单
     * @var int
     */
    public static $ORDER_NOT_FOUND = 70010;
    public static $ORDER_EXIST = 70011;
    /**
     * 找不到 订单 子项目
     * @var int
     */
    public static $ORDER_ITEM_NOT_FOUND = 70012;
    /**
     * 库存不足
     * @var int
     */
    public static $INVENTORY_OUT = 70020;
    /**
     * 未登录
     * @var int
     */
    public static $USER_NOT_SIGNIN = 60000;

    public static function getExceptionList()
    {
        $di = \Phalcon\DI::getDefault();
        $t = $di->getShared('translator');
        return [
            self::$INVALID_DATA => $t->_('无有效数据'),
            self::$SKU_NOT_FOUND => $t->_('SKU不存在'),
            self::$STOCK_QTY_OUT => $t->_('库存不足'),
            self::$ORDER_NOT_FOUND => $t->_('找不到订单'),
            self::$ORDER_EXIST => $t->_('订单已存在'),
            self::$ORDER_ITEM_NOT_FOUND => $t->_('找不到订单子项目'),
            self::$INVENTORY_OUT => $t->_('库存不足'),
            self::$USER_NOT_SIGNIN => $t->_('未登录'),
        ];
    }
}