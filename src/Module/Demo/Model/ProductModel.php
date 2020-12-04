<?php
namespace Kuga\Module\Demo\Model;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\DataExtendTrait;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Uniqueness;

/**
 * 商品Model
 * Class ProductModel
 * @package Kuga\Module\Demo\Model\Product
 */
class ProductModel extends AbstractModel {
    use DataExtendTrait;

    /**
     *
     * @var integer
     */
    public $id;
    /**
     * 商品标题名
     * @var string
     */
    public $title;
    /**
     * 是否上架
     * @var int
     */
    public $isOnline = 1;

    /**
     * 排序权重
     * @var int
     */
    public $sortWeight = 0;

    /**
     * @var \Kuga\Module\Demo\Model\ProductImgModel
     */
    public $imgObject;
    /**
     * @var \Kuga\Module\Demo\Model\ProductDescModel
     */
    public $contentObject;
    /**
     * 所有者ID
     * @var integer
     */
    public $ownerId;
    /**
     * 产品类型
     * @var String
     */
    public $productType;
    /**
     * 供应商
     * @var String
     */
    public $vendor;
    /**
     * 是否多变体
     * @var int
     */
    public $hasVariant = 0;
    public $skuOptionJson;

    /**
     * 重量单位
     * @var enum('oz','lb','g','kg')
     */
    public $weightUnit;
    /**
     * 是否跟踪库存数量，1是，0不是，当为1时，库存相当于不限了
     * @var int
     */
    public $inventoryTracked = 0;
    /**
     * 当inventoryTracked=1时，是否允许超售，1是，0不是
     * @var int
     */
    public $allowOverselling = 0;
    /**
     * 货物是否是实体，需要配送，1是，0不是
     * @var int
     */
    public $requiresShipping = 0;
    /**
     * 发货国家/地区编码
     * @var String
     */
    public $originCountryCode;

    /**
     * 标签
     * @var String
     */
    public $tags;
    public function getSource() {
        return 't_products';
    }
    public function initialize(){
        parent::initialize();
        $this->hasMany('id','ProductImgModel','productId',[
            ['foreignKey' => ['action' => Relation::ACTION_CASCADE], 'namespace' => 'Kuga\\Module\\Demo\\Model']
        ]);
        $this->hasMany('id','ProductDescModel','productId',[
            ['foreignKey' => ['action' => Relation::ACTION_CASCADE], 'namespace' => 'Kuga\\Module\\Demo\\Model']
        ]);
        $this->hasMany('id','ProductSkuModel','productId',[
            ['foreignKey' => ['action' => Relation::ACTION_CASCADE], 'namespace' => 'Kuga\\Module\\Demo\\Model']
        ]);
        $this->hasMany('id','InventoryModel','productId',[
            ['foreignKey' => ['action' => Relation::ACTION_CASCADE], 'namespace' => 'Kuga\\Module\\Demo\\Model']
        ]);
        $this->hasMany('id','ProductSaleChannelModel','productId',[
            ['foreignKey' => ['action' => Relation::ACTION_CASCADE], 'namespace' => 'Kuga\\Module\\Demo\\Model']
        ]);


    }
    private $beforeSaveData;
    public function beforeSave(){
        if(is_array($this->skuOptionJson)){
            $this->beforeSaveData['skuOptionJson'] = $this->skuOptionJson;
            $this->skuOptionJson = json_encode($this->skuOptionJson,JSON_UNESCAPED_UNICODE);
        }
        if(is_array($this->tags)){
            $this->beforeSaveData['tags'] = $this->tags;
            $this->tags   = join(',',$this->tags);
        }
        return true;
    }
    public function afterSave(){
        //恢复保存时改变的数据
        if($this->beforeSaveData['skuOptionJson']){
            $this->skuOptionJson = $this->beforeSaveData['skuOptionJson'];
        }
        if($this->beforeSaveData['tags']){
            $this->tags = $this->beforeSaveData['tags'];
        }
    }
    public function validation()
    {

        $validator = new Validation();
        $validator->add('title',new PresenceOf([
            'model'=>$this,
            'message'=>$this->translator->_('商品名称必须填写')
        ]));
        $validator->add('ownerId',new PresenceOf([
            'model'=>$this,
            'message'=>$this->translator->_('未指定所有者，无法保存')
        ]));
        $this->validate($validator);
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        $data =  array (
            'id' => 'id',
            'title'=>'title',
            'sort_weight' =>'sortWeight',
            'has_variant' =>'hasVariant',
            'vendor' =>'vendor',
            'is_online' =>'isOnline',
            'product_type'=>'productType',
            'owner_id'=>'ownerId',
            'sku_option_json'=>'skuOptionJson',
            'tags'=>'tags',


            'inventory_tracked'=>'inventoryTracked',
            'allow_overselling'=>'allowOverselling',
            'requires_shipping'=>'requiresShipping',
            'origin_country_code'=>'originCountryCode',
            'weight_unit'=>'weightUnit'
        );
        return array_merge($data,$this->extendColumnMapping());
    }
}
