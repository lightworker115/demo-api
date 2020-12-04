<?php
namespace Kuga\Api\Demo;
use Kuga\Module\Demo\Constants;
use Kuga\Module\Demo\Model\InventoryModel;
use Kuga\Module\Demo\Model\ProductModel;
use Kuga\Module\Demo\Model\ProductSkuModel;
use Kuga\Module\Demo\Model\PropKeyModel;
use Kuga\Api\Demo\Exception as ApiException;
use Kuga\Module\Demo\Model\StoreModel;
use Qing\Lib\Utils;

abstract class ShopBaseApi extends BaseApi {
    private $defaultStoreId = null;
    protected function _removeKeys($data,$keys=[]){
        if(!empty($keys)){
            foreach($keys as $k){
                unset($data[$k]);
            }
        }
        return $data;
    }


    /**
     * 检查默认发货的店仓，库存数-预出数=实际库存数
     * @param $skuId
     * @param int $qty 数量，必须小于实际库存数
     * @param int $isOnline
     * @return bool
     */
    protected function isQtyAvailable($skuId,$qty=1,$isOnline=1){
        $isOnline = intval($isOnline);
        $joinCondition = 'skuId=:id: and storeId=store.id and store.isDefaultShippingOrigin=1';
        $searcher = InventoryModel::query();
        $searcher->join(StoreModel::class,$joinCondition,'store');
        $searcher->where('(stockQty - preoutQty)>=:qty:');
        $searcher->bind(['id'=>$skuId,'qty'=>$qty]);
        $searcher->columns('count(0) as total');
        $result = $searcher->execute();
        return $result->getFirst()->total>0;
    }

    /**
     * 在途库存反应到实际库存中
     * @param StockOrderModel $storeOrder
     * @param StockOrderItemsModel $item
     * @param $transaction
     * @throws ApiException
     */
    protected function _transferPreStockToRealStock($storeOrder, $item, $transaction){
        $stock = InventoryModel::findFirst([
            'storeId=:stid: and skuSn=:sid: and inventoryType=:it:',
            'bind'=>['stid'=>$storeOrder->storeId,'sid'=>$item->skuSn,'it'=>$item->inventoryType]
        ]);
        if(!$stock){
            throw new ApiException($this->translator->_('库存记录不存在'));
        }
        $stock->setTransaction($transaction);
        $stock->stockQty += $storeOrder->orderType == Constants::STOCK_OUT ? (-1 * $item->qty):$item->qty;
        if($storeOrder->orderType == Constants::STOCK_OUT){
            $stock->preoutQty -=$item->qty;
        }else{
            $stock->preinQty -=$item->qty;
        }
        $result = $stock->save();
        if(!$result){
            $transaction->rollback($this->translator->_('实际库存变化失败'));
        }
    }

    /**
     * 在途库存预变化
     * @param  Array $option
     *  $option['storeId'] integer 店ID
     *  $option['skuId'] integer  SKU ID
     *  $option['skuSn'] integer SKU SN
     *  $option['qty'] integer 数量
     *  $option['inventoryType'] String 库存类型
     *  $option['orderType'] integer 出库还是入库
     *  $option['canOverSale'] 是否可超过库存量变化
     * @param \Phalcon\Mvc\Model\Transaction $transaction
     */
    protected function _changePreStockQty($option, $transaction){
        //库存在途的要有变化
        $stock = InventoryModel::findFirst([
            'storeId=:stid: and skuId=:sid: and inventoryType=:it:',
            'bind'=>['stid'=>$option['storeId'],'sid'=>$option['skuId'],'it'=>$option['inventoryType']]
        ]);
        if(!$stock){
            $stock = new InventoryModel();
            $stock->productId = $option['productId'];
            $stock->skuId     = $option['skuId'];
            $stock->skuSn     = $option['skuSn'];
            $stock->storeId   = $option['storeId'];
            $stock->inventoryType = $option['inventoryType'];
            $stock->stockQty  = 0;
            $stock->preinQty  = 0;
            $stock->preoutQty = 0;
        }
        if($option['orderType'] == Constants::STOCK_IN){
            $stock->preinQty += $option['qty'];
        }else{
            $stock->preoutQty += $option['qty'];
        }
        if(!$option['canOverSale']
            && $option['orderType'] == Constants::STOCK_OUT
            && (!$stock->id || $stock->preoutQty >$stock->stockQty)){
            throw new ApiException(ApiException::$STOCK_QTY_OUT);
        }

        $stock->setTransaction($transaction);
        $result3 = $stock->save();
        if(!$result3){
            $transaction->rollback($this->translator->_('库存在途变化失败'));
        }
    }

    /**
     *
     * 更改实际库存
     * @param $option
     *
     *  $option['storeId'] integer 店ID
     *  $option['skuId'] integer  SKU ID
     *  $option['skuSn'] integer SKU SN
     *  $option['qty'] integer 数量
     *  $option['inventoryType'] String 库存类型
     *  $option['orderType'] integer 出库还是入库
     *  $option['canOverSale'] 是否可超过库存量变化
     *  $option['isChangePreQty'] 是否变化在途库存
     *  $option['autoCreateInventory']
     * @param $transaction
     * @throws Exception
     */
    protected function _changeRealStockQty($option,$transaction){
        //库存在途的要有变化
        $stock = InventoryModel::findFirst([
            'storeId=:stid: and skuId=:sid: and inventoryType=:it:',
            'bind'=>['stid'=>$option['storeId'],'sid'=>$option['skuId'],'it'=>$option['inventoryType']]
        ]);

        if(!$stock){
            if(!$option['autoCreateInventory'])
                throw new ApiException($this->translator->_('库存无记录'));
            else{
                $skuObj = ProductSkuModel::findFirstById($option['skuId']);
                if($skuObj){
                    $stock = new InventoryModel();
                    $stock->storeId = $option['storeId'];
                    $stock->skuId   = $option['skuId'];
                    $stock->inventoryType = $option['inventoryType'];
                    $stock->productId = $skuObj->productId;
                    $stock->skuSn     = $skuObj->skuSn;
                    $stock->stockQty  = 0;
                    $stock->preinQty  = 0;
                    $stock->stockQty  = 0;
                }
            }
        }
        if($option['orderType'] == Constants::STOCK_IN){
            $stock->stockQty = $stock->stockQty + $option['qty'];
        }else{
            $stock->stockQty = $stock->stockQty - $option['qty'];
        }
        if(!$option['canOverSale']
            && $option['orderType'] == Constants::STOCK_OUT
            && $stock->stockQty<0){
            throw new ApiException(ApiException::$STOCK_QTY_OUT);
        }
        if($option['isChangePreQty']){
            if($option['orderType'] == Constants::STOCK_IN){
                $stock->preinQty  = $stock->preinQty  - $option['qty'];
            }else{
                $stock->preoutQty = $stock->preoutQty - $option['qty'];
            }
        }

        $stock->setTransaction($transaction);
        $result3 = $stock->save();
        if(!$result3){
            $transaction->rollback($this->translator->_('实际库存变化失败'));
        }
    }
    /**
     * 建出入库单
     * @return int
     * @throws ApiException
     */
    protected function _createStockOrder($data,$transaction){
        if($data['orderType'] !=Constants::STOCK_IN && $data['orderType']!=Constants::STOCK_OUT){
            throw new ApiException($this->translator->_('单据orderType不正确'));
        }
        $model = new StockOrderModel();
        $model->initData($data, ['id','createTime']);
        $model->userId = $this->getUserMemberId();
        $model->status = Constants::STATUS_FLOW_PENDING;
        if(!$model->code){
            $prefix      = $data['orderType'] == Constants::STOCK_IN?'IN':'OUT';
            $model->code = $this->generateCode($prefix);
        }
        $model->remark  = Utils::shortWrite($data['remark'],100);
        $model->ownerId = $this->getOwnerId();
        if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/',$data['orderTime'])){
            $model->orderTime = $data['orderTime'];
        }
        $model->setTransaction($transaction);
        $result = $model->create();
        if (!$result) {
            $transaction->rollback($model->getMessages()[0]->getMessage());
        }else{
            if(!empty($data['items'])){
                $success = 0;
                $resultItems= [];
                foreach($data['items'] as $item){
                    $item['qty']   = intval($item['qty']);
                    $item['skuSn'] = trim($item['skuSn']);
                    if(!$item['qty'] || !$item['skuSn']){
                        continue;
                    }
                    //查一下skuSn有没存在
                    $searcher = ProductSkuModel::query();
                    $searcher->join(ProductModel::class,'productId=pro.id','pro','left');
                    $searcher->columns([
                        'skuJson','title','productId',
                        ProductSkuModel::class.'.id',
                        ProductSkuModel::class.'.skuSn'
                    ]);
                    $searcher->where('skuSn=:sn:');
                    $searcher->bind(['sn'=>$item['skuSn']]);
                    $searcher->limit(1);
                    $row = $searcher->execute();
                    $skuSnObj = $row->getFirst();
                    if(!$skuSnObj){
                        $transaction->rollback($this->translator->_('原材料%s%不存在',['s'=>$item['skuSn']]));
                    }
                    $itemModel = new StockOrderItemsModel();
                    $itemModel->setTransaction($transaction);
                    $itemModel->skuSn = $item['skuSn'];
                    $itemModel->inventoryType = $item['inventoryType'];
                    $itemModel->remark = $item['remark']?Utils::shortWrite($item['remark'],100):'';
                    $itemModel->qty   = $item['qty'];
                    $itemModel->orderId = $model->id;
                    $itemModel->ownerId = $model->ownerId;
                    $result2 = $itemModel->create();
                    if(!$result2){
                        $transaction->rollback($this->translator->_('单据明细创建失败'));
                    }
                    //$this->_changePreStock($model,$skuSnObj,$itemModel,$transaction);

//                    $option['storeId'] = $model->storeId;
//                    $option['orderType'] = $model->orderType;
//                    $option['skuId']     = $skuSnObj->id;
//                    $option['skuSn']     = $skuSnObj->skuSn;
//                    $option['qty']       = $itemModel->qty;
//                    $option['qtyBeforeUpdated'] = 0;
//                    $option['inventoryType'] = $itemModel->inventoryType;
//                    $option['canOverSale']   = $this->canOverSale();
//                    if($this->isStockOrderChangePreStock()){
//                        $this->_changePreStockQty($option,$transaction);
//                    }else{
//                        $this->_changeRealStockQty($option,$transaction);
//                    }
                    $success++;
                    $resultItems[] = [
                        'id'=>$itemModel->id,
                        'qty'=>$item['qty'],
                        'skuId'=>$skuSnObj->id,
                        'skuSn'=>$item['skuSn'],
                        'skuString'=>$this->_fetchSkuString($skuSnObj->skuJson),
                        'title'=>$skuSnObj->title,
                        'inventoryType'=>$item['inventoryType'],
                    ];
                }
                if($success == 0){
                    $transaction->rollback($this->translator->_('单据明细创建失败'));
                }
            }else{
                $transaction->rollback($this->translator->_('单据明细为空，无法创建'));
            }
        }
        return [
            'stockOrder'=>$model,
            'items'=> $resultItems
        ];
    }
    /**
     * 审核出入库单
     * @param $stockOrder
     * @param $transaction
     * @return bool
     * @throws ApiException
     */
    protected function _approvedStockOrder($stockOrder,$transaction,$isChangePreQty=false){
        $obj = $stockOrder;
        if (!$obj) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $obj->status = Constants::STATUS_FLOW_APPROVED;
        $obj->setTransaction($transaction);
        $result = $obj->update();
        if($result){
            //明细入库
            $searcher  = StockOrderItemsModel::query();
            $searcher->join(ProductSkuModel::class,'sku.skuSn='.StockOrderItemsModel::class.'.skuSn','sku');
            $searcher->columns([
                'sku.id',
                'sku.productId',
                'sku.skuSn',
                'qty',
                'inventoryType'
            ]);
            $searcher->where('orderId=:id:');
            $searcher->bind(['id'=>$obj->id]);
            $itemList   = $searcher->execute();
            //$itemList = $result->toArray();
            if($itemList){
                foreach($itemList as $item){
                    $option['storeId']   = $obj->storeId;
                    $option['orderType'] = $obj->orderType;
                    $option['skuId']     = $item->id;
                    $option['skuSn']     = $item->skuSn;
                    $option['qty']       = $item->qty;
                    $option['inventoryType'] = $item->inventoryType;
                    $option['canOverSale']   = $this->canOverSale();
                    $option['isChangePreQty']  = $isChangePreQty;
                    $option['autoCreateInventory'] = true;
                    $this->_changeRealStockQty($option,$transaction);
                    //$this->_transferPreStockToRealStock($obj,$item,$transaction);
                }
            }
        }else{
            $transaction->rollback('单据审核失败');
        }
        return true;
    }


    /**
     * 根据单号或ID取得出入库单
     * @param $code
     * @param $id
     * @return array
     * @throws ApiException
     */
    public function _fetchStockOrder($code,$id=0){
        $row = StockOrderModel::findFirst([
            '(id=:id: or code=:code:) and ownerId=:oid:',
            'bind'=>['id'=>$id,'code'=>$code,'oid'=>$this->getOwnerId()]
        ]);
        if(!$row){
           return null;
        }
        $storeRow = StoreModel::findFirst([
            'id=:id:',
            'bind'=>['id'=>$row->storeId],
            'column'=>['name']
        ]);
        $itemRows = StockOrderItemsModel::find([
            'orderId=:sid:',
            'bind'=>['sid'=>$row->id]
        ]);
        $itemList = [];
        $skuSnList = [];
        if($itemRows){
            foreach($itemRows as $item){
                $skuSnList[] = $item->skuSn;
                //$itemList[$item->skuSn+$item->inventoryType]['qty'] = $item->qty;
                //$itemList[$item->skuSn+$item->inventoryType]['type'] = $item->inventoryType;
            }
        }
        $skuInfoList = $this->_fetchSkuInfoBySns(join(',',$skuSnList));
        $returnDataItems = [];
        if($skuInfoList){
            foreach($itemRows as $item){
                $sn = $item->skuSn;
                if(array_key_exists($sn,$skuInfoList)){
                    $r = $skuInfoList[$sn];
                    $r['qty'] = $item->qty;
                    $r['inventoryType'] = $item->inventoryType;
                    $returnDataItems[] = $r;
                }
            }

//            foreach($skuInfoList as &$sku){
//                if(in_array($sku['skuSn'],$skuSnList)){
//                    $sku['qty'] = $itemList[$sku['skuSn']]['qty'];
//                    $sku['inventoryType'] = $itemList[$sku['skuSn']]['type'];
//                }
//            }
        }
        $returnData = $row->toArray();
        if($storeRow){
            $returnData['storeName'] = $storeRow->name;
        }
        $returnData['items'] = $returnDataItems;
        return $returnData;
    }



    /**
     * 取得SKU字串
     * @param $skuJson
     * @return string
     */
    protected function _fetchSkuString($skuJson){
        $skuJson = json_decode($skuJson,true);
        if(!empty($skuJson)) {
            $d = [];
            foreach ($skuJson as $sku) {
                $d[] = $sku['option'] . ':' . $sku['value'];
            }
            return join(';', $d);
        }else {
            return $this->translator->_('未知');
        }
    }

    /**
     * 根据一串sku id，取得相关的SKU信息
     * @return array
     * @throws ApiException
     */
    protected function _fetchSkuInfoByIds($skuIds){
        $searcher = ProductSkuModel::query();
        $searcher->join(ProductModel::class,'productId=p.id','p');
        $searcher->where(ProductSkuModel::class.'.id in ({ids:array})');
        $searcher->bind(['ids' => explode(',',$skuIds)]);
        //最多1000个
        $searcher->limit(1000,0);
        $searcher->columns([
            ProductSkuModel::class.'.id',
            ProductSkuModel::class.'.price',
            ProductSkuModel::class.'.cost',
            ProductSkuModel::class.'.originalSkuId',
            ProductSkuModel::class.'.skuJson',
            ProductSkuModel::class.'.skuSn',
            ProductSkuModel::class.'.productId',
            'p.title'
        ]);
        $result = $searcher->execute();
        $resultData = $result->toArray();
        if($resultData){
            foreach($resultData as &$sku){
                $sku['cost'] = round($sku['cost'],2);
                $sku['price'] = round($sku['price'],2);
                $sku['skuString'] = $this->_fetchSkuString($sku['skuJson']);
            }
        }
        return $resultData;
    }

    /**
     * 取得产品SKU对象
     * @param $skuId SKU ID
     * @param $storeId 仓库ID
     * @param $inventoryType 库存类型
     * @return InventoryModel
     */
    protected  function getProductInventory($skuId, $storeId,$inventoryType=Constants::INVENTORY_ZP){

        $inventory = InventoryModel::findFirst([
            'skuId=:skuId: AND  storeId=:storeId: and inventoryType=:it:',
            'bind' => [
                'skuId' => $skuId,
                'storeId' => $storeId,
                'it'=>$inventoryType
            ]
        ]);
        return $inventory;
    }
    /**
     * 根据一串SKU SN取得相关Sku信息
     * @param $skuSns
     * @return array
     */
    protected function _fetchSkuInfoBySns($skuSns){
        $searcher = ProductSkuModel::query();
        $searcher->join(ProductModel::class,'productId=p.id','p');
        $searcher->where(ProductSkuModel::class.'.skuSn in ({ids:array})');
        $searcher->bind(['ids' => explode(',',$skuSns)]);
        //最多1000个
        $searcher->limit(1000,0);
        $searcher->columns([
            ProductSkuModel::class.'.id',
            ProductSkuModel::class.'.price',
            ProductSkuModel::class.'.cost',
            ProductSkuModel::class.'.originalSkuId',
            ProductSkuModel::class.'.skuJson',
            ProductSkuModel::class.'.skuSn',
            ProductSkuModel::class.'.productId',
            'p.title'
        ]);
        $result = $searcher->execute();
        $resultData = $result->toArray();
        $list = [];
        if($resultData){
            foreach($resultData as &$sku){
                $sku['cost'] = round($sku['cost'],2);
                $sku['price'] = round($sku['price'],2);
                $sku['skuString'] = $this->_fetchSkuString($sku['skuJson']);
                $list[$sku['skuSn']] = $sku;
            }
        }
        return $list;
    }


    /**
     * 是否允许超售
     * @return bool
     */
    protected function canOverSale()
    {
        // TODO: 读取配置 是否允许超售
        return false;
    }

    /**
     * 出入库单是否要进行在途库存量变化
     */
    protected function isStockOrderChangePreStock(){
        return false;
    }

    /**
     * 取得默认店仓ID
     * @return integer
     * @throws Exception
     */
    protected function getDefaultStoreId(){
        if(!$this->defaultStoreId){
            $store = StoreModel::findFirst([
                'ownerId=:oid: and isDefault=1',
                'bind'=>['oid'=>$this->getOwnerId()]
            ]);
            if($store) {
                $this->defaultStoreId = $store->id;
            }else{
                throw new ApiException($this->translator->_('管理员未设定默认店仓'));
            }
        }
        return $this->defaultStoreId;
    }

    /**
     * 根据状态码取得状态名称
     * @param $v
     * @return mixed
     */
    public function getStatusText($v){
        switch($v){
            case Constants::ORDER_STATUS_UNPAID:
                return $this->translator->_('待付款');
            case Constants::ORDER_STATUS_DRAFT:
                return $this->translator->_('草稿');
            case Constants::ORDER_STATUS_PAID:
                return $this->translator->_('已付款');
            case Constants::ORDER_STATUS_DISCARD:
                return $this->translator->_('已取消');
            case Constants::ORDER_STATUS_MANUFACTURE:
                return $this->translator->_('生产中');
            case Constants::ORDER_STATUS_RECEIVE:
                return $this->translator->_('已签收');
            case Constants::ORDER_STATUS_SHIPPED:
                return $this->translator->_('已发货');
        }
    }

    /**
     * 随机生成单号，由 前辍+"-" + YmdHis + 5位随机数  组成
     * @param $prefix
     * @return string
     */
    protected function generateCode($prefix){
        return $prefix.'-'.date('YmdHis').rand(10000,99999);
    }

    /**
     * 找到发货仓
     * @param $ownerId
     * @return \Phalcon\Mvc\Model
     */
    protected function getShippingStore($ownerId){

        //找出哪个是发货仓
        $defaultStore = StoreModel::findFirst([
            'isDefaultShippingOrigin=1 and ownerId=:oid:',
            'bind'=>['oid'=>$ownerId]
        ]);
        return $defaultStore;
    }
}