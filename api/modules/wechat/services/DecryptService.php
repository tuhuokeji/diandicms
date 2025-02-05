<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2023-07-11 13:06:01
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2023-07-11 16:29:40
 */

namespace api\modules\wechat\services;

use common\helpers\loggingHelper;
use common\helpers\ResultHelper;
use common\services\BaseService;
use Yii;

class DecryptService extends BaseService
{
    /**
     * 小程序通过code解密数据
     * @param $encryptedData
     * @param $iv
     * @param $code
     * @return array|object[]|string[]|void
     * @date 2022-08-09
     * @author Radish
     */
    public static function decryptWechatData($encryptedData, $iv, $code)
    {
        if (empty($encryptedData)) {
            return ResultHelper::json(400, 'encryptedData is requred');
        }

        if (empty($iv)) {
            return ResultHelper::json(400, 'iv is requred');
        }

        if (empty($code)) {
            return ResultHelper::json(400, 'code is requred');
        }

        $miniProgram = Yii::$app->wechat->miniProgram;
        $user = $miniProgram->auth->session($code);
        loggingHelper::writeLog('DecryptService', 'decryptWechatData', '解密准备', $user);
        if (isset($user['session_key'])) {
            $decryptData = $miniProgram->encryptor->decryptData($user['session_key'], $iv, urldecode($encryptedData));
            loggingHelper::writeLog('DecryptService', 'decryptWechatData', '解密结果', $decryptData);

            return $decryptData;
        } else {
            return ResultHelper::json(400, 'session_key 不存在', $user);
        }
    }
}
