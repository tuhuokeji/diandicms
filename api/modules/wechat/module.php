<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-08 03:04:55
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2023-07-14 17:16:29
 */

namespace api\modules\wechat;

use common\helpers\FileHelper;
use common\helpers\StringHelper;
use common\models\DdCorePaylog;
use diandi\addons\models\Bloc;
use Yii;

/**
 * 小程序接口
 */
class module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'api\modules\wechat\controllers';

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        $logPath = Yii::getAlias('@runtime/wechat/payparameters' . date('ymd') . '.log');
        parent::init();

        /* 加载语言包 */
        if (!isset(Yii::$app->i18n->translations['wechat'])) {
            Yii::$app->i18n->translations['wechat'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en',
                'basePath' => '@api/modules/wechat/messages',
            ];
        }

        $config = require __DIR__ . '/config.php';
        // 获取应用程序的组件
        $components = Yii::$app->getComponents();

        // 遍历子模块独立配置的组件部分，并继承应用程序的组件配置
        foreach ($config['components'] as $k => $component) {
            if (isset($component['class']) && !isset($components[$k])) {
                continue;
            }
            $config['components'][$k] = array_merge($components[$k], $component);
        }

        // 微信回调跟进订单初始化

        $input = file_get_contents('php://input');
        FileHelper::writeLog($logPath, '入口配置回来的值' . $input);
        FileHelper::writeLog($logPath, '入口配置回来的值0-5' . substr($input, 0, 5));
        if (str_starts_with($input, '<xml>')) {
            FileHelper::writeLog($logPath, '准备处理');
            $xmlData = StringHelper::getXml($input);
            FileHelper::writeLog($logPath, 'xml解析后' . $xmlData['trade_type'] . '/' . json_encode($xmlData));
            if ($xmlData['trade_type'] == 'JSAPI') {
                $out_trade_no = $xmlData['out_trade_no'];
                FileHelper::writeLog($logPath, '入口配置回来的订单编号：' . $out_trade_no);
                $DdCorePayLog = new DdCorePaylog();
                $orderInfo = $DdCorePayLog->find()->where([
                    'uniontid' => trim($out_trade_no),
                ])->select(['bloc_id', 'store_id', 'module'])->asArray()->one();
                FileHelper::writeLog($logPath, '入口配置回来的xml值订单日志sql' . $DdCorePayLog->find()->where([
                        'uniontid' => trim($out_trade_no),
                    ])->select(['bloc_id', 'store_id', 'module'])->createCommand()->getRawSql());
                FileHelper::writeLog($logPath, '入口配置回来的xml值订单日志' . json_encode($orderInfo));
                $pay_bloc_id = Bloc::find()->where(['bloc_id' => $orderInfo['bloc_id']])->select('group_bloc_id')->scalar();
                $pay_bloc_id = (int)($pay_bloc_id?:$orderInfo['bloc_id']);
                FileHelper::writeLog($logPath, '支付对应的参数公司' . json_encode([
                    'pay_bloc_id'=>$pay_bloc_id
                ]));
                Yii::$app->service->commonGlobalsService->initId($pay_bloc_id, (int)$orderInfo['store_id']??0);
                Yii::$app->service->commonGlobalsService->getConf($pay_bloc_id);
                Yii::$app->params['bloc_id'] = $pay_bloc_id;
                Yii::$app->params['store_id'] = $orderInfo['store_id'];
            }

            FileHelper::writeLog($logPath, '_W_W_W' . json_encode(Yii::$app->params['bloc_id']));
        }

        $params = Yii::$app->params;
        $conf = $params['conf'];

        $Wechatpay = $conf['wechatpay'];
        $Wxapp = $conf['wxapp'];


        $apiclient_certUrl = !empty($Wechatpay['apiclient_cert']) && !empty($Wechatpay['apiclient_cert']['url']) ? $Wechatpay['apiclient_cert']['url'] : '';
        $apiclient_keyUrl = !empty($Wechatpay['apiclient_key']) && !empty($Wechatpay['apiclient_key']['url']) ? $Wechatpay['apiclient_key']['url'] : '';
        $apiclient_cert = Yii::getAlias('@attachment/' . $apiclient_certUrl);
        $apiclient_key = Yii::getAlias('@attachment/' . $apiclient_keyUrl);


        // 支付参数设置
        $config['params']['wechatPaymentConfig'] = [
            'app_id' => $Wxapp['AppId'] ?? '',
            'mch_id' => $Wechatpay['mch_id'] ?? '',
            'key' => $Wechatpay['key'] ?? '',  // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => $apiclient_cert, // XXX: 绝对路径！！！！
            'key_path' => $apiclient_key, // XXX: 绝对路径！！！！
            'notify_url' => Yii::$app->request->hostInfo . '/api/wechat/basics/notify',
        ];

        FileHelper::writeLog($logPath, '入口配置' . json_encode($config['params']['wechatPaymentConfig']));
        FileHelper::writeLog($logPath, '总配置' . json_encode($conf));
        FileHelper::writeLog($logPath, '小程序配置' . json_encode($Wxapp));

        // 小程序参数设置
        $config['params']['wechatMiniProgramConfig'] = [
            'app_id' => $Wxapp['AppId'] ?? '',
            'secret' => $Wxapp['AppSecret'] ?? '',
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => Yii::getAlias('@runtime/miniprogram'),
            ],
            //必须添加部分
            'guzzle' => [ // 配置
                'verify' => false,
                'timeout' => 4.0,
            ],
        ];

        $params = Yii::$app->params;

        foreach ($params as $key => $value) {
            if (!isset($config['params'][$key])) {
                $config['params'][$key] = $value;
            }
        }

        // 将新的配置设置到应用程序
        // 很多都是写 Yii::configure($this, $config)，但是并不适用子模块，必须写 Yii::$App
        Yii::configure(Yii::$app, $config);
    }
}
