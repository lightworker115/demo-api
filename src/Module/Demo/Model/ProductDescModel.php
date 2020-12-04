<?php
namespace Kuga\Module\Demo\Model;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;

/**
 * 商品简介Model
 * Class ProductDescModel
 * @package Kuga\Module\Demo\Model
 */
class ProductDescModel extends AbstractModel {
    /**
     *
     * @var integer
     */
    public $id;
    /**
     * 商品ID
     * @var integer
     */
    public $productId;
    /**
     * 手机版内容介绍
     * @var string
     */
    public $mobileContent;
    /**
     * PC内容介绍
     * @var string
     */
    public $content;


    public function getSource() {
        return 't_product_desc';
    }
    public function initialize(){
        parent::initialize();
        $this->belongsTo('productId', 'ProductModel', 'id');
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return [
            'id' => 'id',
            'product_id'=>'productId',
            'content' =>'content',
            'mobile_content' =>'mobileContent'
        ];
    }
}
