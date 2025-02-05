<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-10 20:37:35
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2023-07-25 15:39:23
 */

namespace api\modules\wechat\components;

use api\models\DdApiAccessToken;
use api\models\DdMember;
use api\modules\wechat\models\DdWxappFans;
use common\helpers\ErrorsHelper;
use common\helpers\FileHelper;
use common\helpers\ResultHelper;
use common\helpers\StringHelper;
use common\services\api\RegisterLevel;
use Yii;
use yii\base\BaseObject;
use yii\base\ErrorException;
use yii\base\Exception;

class Fans extends BaseObject
{
    /**
     * 注册fans数据.
     *
     * @param array|null $users post
     *
     * @return array|object[]|string|string[]
     *
     * @throws ErrorException
     * @throws Exception
     */
    public function signup(?array $users): array|string
   {

        $logPath = Yii::getAlias('@runtime/wechat/login/' . date('ymd') . '.log');
        FileHelper::writeLog($logPath, '登录日志:用户信息sign' . json_encode($users));

        $openid = $users['openid'];
        $nickname = $users['nickName']??'';
        $keys = $openid . '_userinfo';
        $mobile = $users['mobile'] ?? '';
        FileHelper::writeLog($logPath, '登录日志:用户信息openid' . json_encode($openid));
        FileHelper::writeLog($logPath, '登录日志:用户信息缓存获取' . json_encode(Yii::$app->cache->get($keys)));

        if (Yii::$app->cache->get($keys)) { //如果有缓存数据则返回缓存数据，没有则从数据库取病存入缓存中
            // 获取缓存
            $res = Yii::$app->cache->get($keys);
            // 验证有效期
            $isPeriod = Yii::$app->service->apiAccessTokenService->isPeriod($res['access_token']);
            FileHelper::writeLog($logPath, '登录日志:有缓存验证有效期' . json_encode($isPeriod));

            if (!$isPeriod) {
                return Yii::$app->cache->get($keys);
            }
        }

        $DdMember = new DdMember([
            'scenario'=>'all'
        ]);

        // 校验openID是否存在
        $isHave = $this->checkByopenid($openid);
        FileHelper::writeLog($logPath, '登录日志:校验openid是否存在' . json_encode([
            'isHave' => $isHave,
            'bloc_id' =>\Yii::$app->request->input('bloc_id',0),
            'isRegister' => RegisterLevel::isRegister($isHave,\Yii::$app->request->input('bloc_id',0)),
        ]));


        if ($isHave) {
            FileHelper::writeLog($logPath, '登录日志:已有数据');

            $fans = $this->fansByopenid($openid);
            $member = $DdMember::findIdentity($fans['user_id']);
            if(empty($member)){
                return ResultHelper::json(400,'用户不存在',$fans);
            }
            $userinfo = Yii::$app->service->apiAccessTokenService->getAccessToken($member);

            Yii::$app->cache->set($keys, $userinfo);
            FileHelper::writeLog($logPath, '登录日志:有缓存数据' . json_encode($userinfo));
            $DdWxappFans = new DdWxappFans();
            $DdWxappFans->updateAll([
                'session_key' => $users['session_key'],
            ], [
                'fanid' => $fans['fanid'],
                'openid' => $users['openid'],
            ]);
            // 更新后获取
            $fans = $this->fansByopenid($openid);

            $userinfo['fans'] = $fans;
            return $userinfo;
        } else {
            $password = StringHelper::randomNum();

            FileHelper::writeLog($logPath, '登录日志:昵称去除特殊字符' . json_encode($this->removeEmoji($nickname)));

            $nickname = $this->removeEmoji($nickname);

            $nickname = $this->filterEmoji($nickname);
            // 去除斜杠后的数据

            FileHelper::writeLog($logPath, '登录日志:处理好以后的昵称：' . $nickname);

            if (empty($nickname)) {
                $max_member_id = $DdMember->find()->max('member_id');
                // 使用随机昵称
                $nickname = '游客' . ($max_member_id + 1);
            }

            FileHelper::writeLog($logPath, '登录日志:处理好以后的昵称：' . $nickname);

            $res = $DdMember->signup($nickname, $mobile, $password,$openid);

            if (isset($res['code']) && $res['code'] != 200) {
                return ResultHelper::json($res['code'], $res['message']);
            }

            FileHelper::writeLog($logPath, '登录日志:会员注册返回结果' . json_encode($res));

            // 更新openid
            $member_id = isset($res['data']['member']) ? $res['data']['member']['member_id']:0;
            FileHelper::writeLog($logPath, '登录日志:获取用户id' . json_encode($member_id));

            DdApiAccessToken::updateAll(['openid' => $openid], ['member_id' => $member_id]);
            FileHelper::writeLog($logPath, '登录日志:注册fans' . json_encode($member_id));

            // 注册fans
            // 生成随机的加密键
            $secretKey = Yii::$app->getSecurity()->generateRandomString();
            $dataFans = [
                'user_id' => $member_id,
                'avatarUrl' => $users['avatarUrl'],
                'openid' => $users['openid'],
                'nickname' => $nickname,
                'groupid' =>isset($res['member'])? $res['member']['group_id']:1,
                'fans_info' => $users['openid'],
                'unionid' => !empty($users['unionid']) ? $users['unionid'] : '',
                'gender' => $users['gender'],
                'country' => $users['country'],
                'city' => $users['city'],
                'province' => $users['province'],
                'secretKey' => $secretKey,
                'session_key' => $users['session_key'],
            ];
            FileHelper::writeLog($logPath, '登录日志:组装fans' . json_encode($dataFans));

            // 加密fans的所有资料
            // $dataFans['fans_info'] = $this->encrypt($dataFans, $secretKey);
            FileHelper::writeLog($logPath, '登录日志:组装fans001' . json_encode($dataFans));

            $DdWxappFans = new DdWxappFans();
            if ($DdWxappFans->load($dataFans, '') && $DdWxappFans->save()) {
                $arr = $res['data'];//数组返回200，取data
                $arr['fans'] = $dataFans;
                $arr['wxappFans'] = $dataFans;
                FileHelper::writeLog($logPath, '登录日志:组装fans002' . json_encode($arr));
                Yii::$app->cache->set($keys, $arr);

                return $arr;
            } else {
                $errors = ErrorsHelper::getModelError($DdWxappFans);
                FileHelper::writeLog($logPath, '登录日志：写入错误' . json_encode($errors));

                return $errors;
            }
        }
    }

    public function checkByopenid($openid): array|\yii\db\ActiveRecord|null
   {
        return DdWxappFans::find()->where(['openid' => $openid, 'store_id' =>\Yii::$app->request->input('store_id',0)])->asArray()->one();
    }

    public function fansByopenid($openid): array|\yii\db\ActiveRecord|null
   {

        return DdWxappFans::find()->where(['openid' => $openid, 'store_id' =>\Yii::$app->request->input('store_id',0)])->asArray()->one();
    }

    public function removeEmoji($nickname): array|string|null
    {
        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text = preg_replace($regexEmoticons, '', $nickname);
        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text = preg_replace($regexSymbols, '', $clean_text);
        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text = preg_replace($regexTransport, '', $clean_text);
        // Match Miscellaneous Symbols
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, '', $clean_text);
        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        return preg_replace($regexDingbats, '', $clean_text);
    }

    public function filterEmoji($str): array|string|null
    {
        return preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str
        );
    }

    /**
     * @param $data
     * @param string $key 密钥
     *
     * @return string
     */
    public static function encrypt($data, string $key): string
    {
        $string = base64_encode(json_encode($data));
        // openssl_encrypt 加密不同Mcrypt，对秘钥长度要求，超出16加密结果不变
        $data = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

        return strtolower(bin2hex($data));
    }

    /**
     * @param string $string 需要解密的字符串
     * @param string $key    密钥
     *
     * @return string
     */
    public static function decrypt(string $string, string $key): string
    {
        $decrypted = openssl_decrypt(hex2bin($string), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

        return json_decode(base64_decode($decrypted));
    }
}
