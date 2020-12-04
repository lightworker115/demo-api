<?php
namespace Kuga\Module\Demo\Model;
use Kuga\Core\Base\AbstractModel;

/**
 * 库存
 * Class InventoryModel
 * @package Kuga\Module\Demo\Model
 */
class InventoryModel extends AbstractModel {

    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 商品ID
     * @var Integer
     */
    public $productId;

    /**
     * 店仓ID
     * @var Integer
     */
    public $storeId;

    /**
     * 库存量
     * @var integer
     */
    public $stockQty =0;
    /**
     * 在途出单数
     * @var integer
     */

    public $preoutQty =0;
    /**
     * 在途入单数
     * @var integer
     */
    public $preinQty= 0;
    /**
     * SKU ID
     * @var integer
     */
    public $skuId;
    /**
     * Sku JSON信息
     * @var String
     */
    public $skuJson;


    public function getSource() {
        return 't_products_inventory';
    }
    public function initialize(){
        parent::initialize();
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return  array (
            'id' => 'id',
            'store_id' => 'storeId',
            'product_id' => 'productId',
            'sku_id' => 'skuId',
            'preout_qty'=>'preoutQty',
            'prein_qty'=>'preinQty',
            'stock_qty'=>'stockQty',
            'sku_json'=>'skuJson'
        );

    }
}
