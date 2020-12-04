<?php
namespace Kuga\Api\Demo;
use Kuga\Core\Api\AbstractApi;
use Kuga\Core\Api\Request;
use Kuga\Core\Model\RegionModel;
use Kuga\Module\Shop\Model\OwnerUserModel;
use Phalcon\Mvc\Model\TransactionInterface;
use Phalcon\Http\Client\Request as HttpClientRequest;
use Kuga\Module\Acc\Service\Acl;
abstract class BaseApi extends AbstractApi{
    protected $_accessTokenUserIdKey = 'console.uid';
    protected $ownerId;
    protected function getSessionId(){
    return md5($this->_accessToken);
    }
    /**
     * 获取事物管理
     * @return TransactionInterface
     */
    protected function getTransaction(){
        $tx = $this->_di->getShared('transactions');
        $transaction = $tx->get();
        return $transaction;
    }
    /**
     * 取得所有者ID
     * @return int
     */
    protected function getOwnerId(){
        $ownerId = 0;
        if($this->_userMemberId){
            if($this->ownerId){
                return $this->ownerId;
            }else{
                $row = OwnerUserModel::findFirstByUid($this->_userMemberId);
                if($row){
                    $ownerId = $row->ownerId;
                }
            }
        }
        return $ownerId;
    }

    /**
     * 判断是否有权限
     * @param $resource
     * @param $code
     * @return bool
     */
    protected function isAllow($resource,$code){
        $list = $this->fetchPrivileges();
        if(!$list || !isset($list[$resource])){
            return false;
        }else{
            return in_array($code,$list[$resource]);
        }
    }
    /**
     * 取得当前用户的权限列表，并缓存起来
     * @param integer $lifetime 缓存时间，秒
     * @return array
     */
    private function fetchPrivileges($lifetime = 7200)
    {
        $cache   = $this->_di->getShared('cache');
        $cacheKey= 'ACC_'.$this->_appKey.':'.$this->_userMemberId;
        if(1!=1 && $data = $cache->get($cacheKey)){
            return $data;
        }else{
            $config  = $this->_di->getShared('config');
            $params['appkey'] = $config->app->acc->appKey;
            $secret = $config->app->acc->appSecret;
            $params['access_token'] = $this->_accessToken;
            $params['method'] = 'acc.privileges';
            $params['uid']    = $this->_userMemberId;
            $params['appId']  = $this->_appKey;
            $params['sign']   = Request::createSign($secret, $params);
            $provider = HttpClientRequest::getProvider();
            $provider->setBaseUri($config->app->acc->apiUri);
            $provider->header->set('Content-Type', 'application/json');
            $response = $provider->post('', json_encode($params,JSON_UNESCAPED_UNICODE));
            $result   = $response->body;
            $content  = json_decode($result,true);
            if($content['status'] === 0){
                $cache->set($cacheKey,$content['data'],$lifetime);
                return $content['data'];
            }
        }
        return [];
    }


}