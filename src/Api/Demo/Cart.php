<?php
/**
 * 购物车API
 */

namespace Kuga\Api\Demo;

use Kuga\Module\Demo\Model\CartModel;
use Kuga\Module\Demo\Model\InventoryModel;
use Kuga\Module\Demo\Model\ProductImgModel;
use Kuga\Module\Demo\Model\ProductModel;
use Kuga\Module\Demo\Model\ProductSkuModel;
use Kuga\Api\Demo\Exception as ApiException;
use Kuga\Module\Demo\Model\StoreModel;

class Cart extends ShopBaseApi{
    const MAX_LIMIT = 100;
    /**
     * 将传参返回出去
     */
    public function test(){
        $data = $this->_toParamObject($this->getParams());
        return [
            'requestData'=>$data->toArray(),
            'word'=>$this->translator->_('这是测试')
        ];
    }
    /**
     * @param skuId
     * @param qty
     * @return bool|\Phalcon\Mvc\Model\ResultsetInterface
     * @throws ApiException
     */
    public function setQty(){
        $uid = $this->_userMemberId;
        $sid = $this->getSessionId();

        $data = $this->_toParamObject($this->getParams());
        if(!$data['qty']){
            throw new ApiException($this->translator->_('产品数量未指定'));
        }
        $searcher = ProductSkuModel::query();
        $searcher->join(ProductModel::class,ProductSkuModel::class.'.productId=pro.id','pro');
        $searcher->join(ProductImgModel::class,ProductSkuModel::class.'.imgId=img.id','img','left');
        $searcher->where(ProductSkuModel::class.'.id=:id: ',['id'=>$data['skuId']]);
        $searcher->columns([
            'pro.id as productId',
            'pro.ownerId',
            'pro.updateTime',
            'pro.allowOverselling',
            'pro.inventoryTracked',
            'pro.title',
            ProductSkuModel::class.'.originPrice',
            ProductSkuModel::class.'.price',
            ProductSkuModel::class.'.skuJson',
            'img.imgUrl',
            '(select imgUrl from '.ProductImgModel::class.' where '.ProductImgModel::class.'.productId=pro.id and isFirst=1) as firstImgUrl'
        ]);
        $searcher->limit(1);
        $result = $searcher->execute();
        $productInfos = $result->toArray();
        if(empty($productInfos)){
            throw new ApiException($this->translator->_('产品不存在'));
        }
        if($productInfos[0]['inventoryTracked']){
            //查验库存
            if(!$this->isQtyAvailable($data['skuId'],$data['qty'])){
                throw new ApiException($this->translator->_('库存不足'));
            }
        }
        $oid = $productInfos[0]['ownerId'];
        if($uid){
            $total= CartModel::count([
                'memberId=:mid: and ownerId=:oid:',
                'bind'=>['mid'=>$uid,'oid'=>$oid]
            ]);
            $row = CartModel::findFirst([
                'memberId=:mid: and skuId=:skuId: and ownerId=:oid:',
                'bind'=>['mid'=>$uid,'skuId'=>$data['skuId'],'oid'=>$oid]
            ]);
        }else{
            $total= CartModel::count([
                'sessionId=:sid: and ownerId=:oid:',
                'bind'=>['sid'=>$sid,'oid'=>$oid]
            ]);
            $row = CartModel::findFirst([
                'sessionId=:sid: and skuId=:skuId: and ownerId=:oid:',
                'bind'=>['sid'=>$sid,'skuId'=>$data['skuId'],'oid'=>$oid]
            ]);
        }
        if($total>=self::MAX_LIMIT){
            throw new ApiException($this->translator->_('购物车最多只能存放%max%条记录',['max'=>self::MAX_LIMIT]));
        }
        if(!$row){
            $row = new CartModel();
            $row->memberId = $uid;
            $row->sessionId = $sid;
            $row->skuId     = $data['skuId'];
            $row->ownerId   = $oid;
            $row->qty       = $data['qty'];
            $row->productId = $productInfos[0]['productId'];
        }
        //相关信息要更新
        $row->initPrice = $productInfos[0]['price'];
        $row->initUpdateTime = $productInfos[0]['updateTime'];
        $row->initTitle     = $productInfos[0]['title'];
        $row->initSkuJson   = $productInfos[0]['skuJson'];
        $row->initOriginPrice = $productInfos[0]['originPrice'];
        if($productInfos[0]['imgUrl'])
            $row->initImgUrl    = $productInfos[0]['imgUrl'];
        else
            $row->initImgUrl    = $productInfos[0]['firstImgUrl'];

        $row->qty+=$data['qty'];
        $result = $row->save();
        return $result;
    }

    /**
     * 删除购物车商品
     * @param id
     */
    public function remove(){
        $uid = $this->_userMemberId;
        $sid = $this->getSessionId();
        $data = $this->_toParamObject($this->getParams());
        if($uid){
            $row = CartModel::findFirst([
                'memberId=:mid: and id=:id:',
                'bind'=>['mid'=>$uid,'ud'=>$data['id']]
            ]);
        }else{
            $row = CartModel::findFirst([
                'sessionId=:sid: and id=:id:',
                'bind'=>['sid'=>$sid,'id'=>$data['id']]
            ]);
        }
        if($row){
            $row->delete();
        }
        return true;
    }
    /**
     * 购物车商品列表
     * @param limit
     */
    public function items(){
        $uid = $this->_userMemberId;
        $sid = $this->getSessionId();
        $oid = $this->getOwnerId();
        $data = $this->_toParamObject($this->getParams());
        if(!$uid||!$sid){
            throw new ApiException($this->translator->_('未指定会员'));
        }
        $searcher = CartModel::query();
        $searcher->join(ProductSkuModel::class,CartModel::class.'.skuId=sku.id','sku','left');
        $searcher->join(ProductModel::class,CartModel::class.'.productId=pro.id','pro','left');
        $searcher->join(ProductImgModel::class,'sku.imgId=img.id','img','left');
        $searcher->columns([
            CartModel::class.'.id',
            CartModel::class.'.productId',
            CartModel::class.'.skuId',
            CartModel::class.'.qty',
            CartModel::class.'.initPrice',
            //CartModel::class.'.initUpdateTime',
            CartModel::class.'.initTitle',
            CartModel::class.'.initImgUrl',
            CartModel::class.'.initSkuJson',
            CartModel::class.'.initOriginPrice',
            CartModel::class.'.createTime',
            CartModel::class.'.updateTime',
            'pro.id as originProductId',
            'sku.id as originSkuId',
            'img.id as originImgId',
            'sku.price as latestPrice',
            'pro.title as latestTitle',
            'sku.skuJson as latestSkuJson',
            'sku.originPrice as latestOriginPrice',
            'img.imgUrl',
            '(select imgUrl from '.ProductImgModel::class.' where '.ProductImgModel::class.'.productId=pro.id and isFirst=1) as firstImgUrl'
        ]);
        if($uid){
            $searcher->where('memberId=:mid:',['mid'=>$uid]);
        }else{
            $searcher->where('sessionId=:sid:',['sid'=>$sid]);
        }

        $max = intval($data['limit']);
        if($max<=0||$max>self::MAX_LIMIT){
            $max = self::MAX_LIMIT;
        }
        $searcher->limit($max);
        $result = $searcher->execute();
        $list   = $result->toArray();
        if($list){
            foreach($list as &$item){
                if(!$item['originProductId']){
                    //产品可能被删除了
                    $item['title'] = $item['initTitle'];
                }else{
                    //没删除取最新
                    $item['title'] = $item['latestTitle'];
                }
                if(!$item['originSkuId']){
                    //SKU被删除了
                    $item['skuJson'] = $item['initSkuJson'];
                    $item['price']   = $item['initPrice'];
                    $item['originPrice']   = $item['initOriginPrice'];
                }else{
                    //没删除取最新
                    $item['skuJson'] = $item['latestSkuJson'];
                    $item['price']   = $item['latestPrice'];
                    $item['originPrice']   = $item['latestOriginPrice'];
                }
                if(!$item['originImgId']){
                    //原来的图没了
                    $item['imgUrl'] = $item['initImgUrl'];
                }else{
                    $item['imgUrl'] = $item['imgUrl']?$item['imgUrl']:($item['firstImgUrl']?$item['firstImgUrl']:'');
                }

                unset($item['firstImgUrl'],$item['initImgUrl']);
                unset($item['latestSkuJson'],$item['initSkuJson']);
                unset($item['latestPrice'],$item['initPrice']);
                unset($item['latestOriginPrice'],$item['initOriginPrice']);
                unset($item['latestSkuJson'],$item['initSkuJson']);
                unset($item['latestTitle'],$item['initTitle']);
                unset($item['originImgId'],$item['originProductId'],$item['originSkuId']);
                $item['skuJson'] = json_decode($item['skuJson'],true);
            }
        }
        return $list;

    }
}