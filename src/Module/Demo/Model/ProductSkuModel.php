<?php
namespace Kuga\Module\Demo\Model;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\ModelException;

/**
 * 商品SKU Model
 * Class ProductSkuModel
 * @package Kuga\Module\Demo\Model
 */
class ProductSkuModel extends AbstractModel {
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
     * SKU规格JSON
     * @var string
     */
    public $skuJson;
    /**
     * 售价
     * @var float
     */
    public $price;
    /**
     * 成本，不填时为''
     * @var String
     */
    public $cost = '';
    /**
     * 原价，不填时为''
     * @var String
     */
    public $originPrice = '';
    /**
     * 海关编码
     * @var String
     */
    public $hsCode;
    /**
     * 重量
     * @var float
     */
    public $weight;
    /**
     * 国际条码，如UPC
     * @var
     */
    public $barcode;
    /**
     * SKU编码
     * @var String
     */
    public $skuSn;
    /**
     * 对应的图片ID
     * @var
     */
    public $imgId;


    public function getSource() {
        return 't_product_skus';
    }
    public function initialize(){
        parent::initialize();
        $this->belongsTo('productId', 'ProductModel', 'id');
    }
    public function beforeSave(){
        $cnt = 0;
        if($this->id){
            $cnt = self::count([
                'productId=:pid: and skuSn!="" and skuSn=:osid: and id!=:id:',
                'bind'=>['pid'=>$this->productId,'osid'=>$this->skuSn,'id'=>$this->id]
            ]);
        }else{
            $cnt = self::count([
                'productId=:pid: and skuSn!="" and skuSn=:osid:',
                'bind'=>['pid'=>$this->productId,'osid'=>$this->skuSn]
            ]);
        }
        if($cnt>0){
            throw new ModelException($this->translator->_('同款产品的SKU编码不可重复'));
        }
        if(!is_numeric($this->cost)){
            $this->cost = null;
        }
        if(!is_numeric($this->originPrice)){
            $this->originPrice = null;
        }
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return [
            'id' => 'id',
            'product_id'=>'productId',
            'sku_json' =>'skuJson',
            'price' =>'price',
            'cost' =>'cost',
            'origin_price'=>'originPrice',
            'hs_code'=>'hsCode',
            'weight'=>'weight',
            'img_id'=>'imgId',
            'sku_sn'=>'skuSn',
            'barcode'=>'barcode'
        ];
    }
}
