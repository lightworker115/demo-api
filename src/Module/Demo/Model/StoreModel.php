<?php
namespace Kuga\Module\Demo\Model;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\DataExtendTrait;
use Kuga\Core\Base\RegionTrait;
use Phalcon\Mvc\Model\Message;
use Phalcon\Validation;

/**
 * 店仓
 * Class StoreModel
 * @package Kuga\Module\Shop\Model
 */
class StoreModel extends AbstractModel {
    use DataExtendTrait;
    use RegionTrait;

    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 名称
     * @var string
     */
    public $name;

    /**
     * 具体地址
     * @var
     */

    public $address;
    /**
     * 启用或禁用
     * @var int 1禁用，0启用
     */
    public $disabled = 0;
    /**
     * 是否在线销售
     * @var int
     */
    public $sellOnLine = 0;
    /**
     * 摘要
     * @var String
     */
    public $summary;
    /**
     * @var integer
     */
    public $ownerId;
    /**
     * 默认发货地
     * @var int
     */
    public $isDefaultShippingOrigin;
  
    public function getSource() {
        return 't_stores';
    }
    public function initialize(){
        parent::initialize();
    }
    public function validation()
    {
        $validator = new Validation();
        $validator->add('name',new Validation\Validator\PresenceOf([
            'model' => $this,
            'message' => $this->translator->_('请输入店仓名称')
        ]));
        $validator->add('ownerId',new Validation\Validator\PresenceOf([
            'model' => $this,
            'message' => $this->translator->_('店仓所有者未指定')
        ]));

        return $this->validate($validator);
    }
    public function beforeCreate(){
        //IMPORTANT:只有一个店仓时，定为默认发货地
        $cnt = self::count([
            'ownerId=:oid: ',
            'bind'=>['oid'=>$this->ownerId]
        ]);
        if($cnt == 0){
            $this->isDefaultShippingOrigin = 1;
        }
        return true;
    }
    public function beforeRemove(){
        if($this->isDefaultShippingOrigin){
            throw new \Exception($this->translator->_('默认发货地，不可删除'));
        }
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        $data = array (
            'id' => 'id',
            'name' => 'name',
            'disabled'=>'disabled',
            'sell_online'=>'sellOnline',
            'address'=>'address',
            'summary'=>'summary',
            'owner_id'=>'ownerId',
            'is_default_shipping_origin'=>'isDefaultShippingOrigin'
        );
        return array_merge($data,$this->extendColumnMapping(),$this->regionColumnMapping());
    }
}
