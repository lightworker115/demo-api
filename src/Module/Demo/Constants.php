<?php
namespace Kuga\Module\Demo;
/**
 * 常量定义示例
 * Class Constants
 * @package Kuga\Module\Demo
 */
class Constants{
    const ORDER_STATUS_DRAFT = 0;
    /**
     * 订单状态：未付
     */
    const ORDER_STATUS_UNPAID = 10;
    /**
     * 订单状态：已付
     */
    const ORDER_STATUS_PAID   = 20;
    /**
     * 订单状态：取消
     */
    const ORDER_STATUS_DISCARD = 30;
    /**
     * 订单状态：生产中
     */
    const ORDER_STATUS_MANUFACTURE = 35;
    /**
     * 订单状态：已发货
     */
    const ORDER_STATUS_SHIPPED = 40;
    /**
     * 订单状态：已签收
     */
    const ORDER_STATUS_RECEIVE  = 50;


    /**
     * 订单财务状态：待付
     */
    const FINANCIAL_STATUS_PENDING = 60;
    /**
     * 订单财务状态：已付
     */
    const FINANCIAL_STATUS_PAID    = 61;
    /**
     * 订单财务状态：部分退款
     */
    const FINANCIAL_STATUS_PART_REFUNDED = 62;
    /**
     * 订单财务状态：已退款
     */
    const FINANCIAL_STATUS_REFUNDED = 63;
    /**
     * 订单财务状态：取消
     */
    const FINANCIAL_STATUS_VOIDED = 64;
    /**
     * 待配货
     */
    const FULLFILMENT_STATUS_NULL = 70;
    /**
     * 部分配货
     */
    const FULLFILMENT_STATUS_PART = 71;
    /**
     * 退回
     */
    const FULLFILMENT_STATUS_RESTOCKED = 72;
    /**
     * 配货完毕
     */
    const FULLFILMENT_STATUS_FULFILLED = 73;


    /**
     * 派送：待发货
     */
    const SHIPMENT_STATUS_PENDING = 80;
    /**
     * 派送：派送中
     */
    const SHIPMENT_STATUS_DELIVERY = 81;
    /**
     * 派送：已签收
     */
    const SHIPMENT_STATUS_DELIVERED = 82;
    /**
     * 派送：失败
     */
    const SHIPMENT_STATUS_FAILURE = 83;
}