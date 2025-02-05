<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-04-09 11:19:49
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2023-02-21 13:27:26
 */

namespace admin\controllers\file;

use admin\controllers\AController;
use common\components\FileUpload\models\UploadValidate;
use common\components\FileUpload\Upload;
use common\helpers\ResultHelper;
use yii\base\Exception;
use yii\base\ExitException;
use yii\filters\VerbFilter;
use yii\helpers\Json;

class UploadController extends AController
{
    public $modelClass = '';

    public $enableCsrfValidation = false;

    public int $searchLevel = 0;

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['POST'],
            ],
        ];
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            \Yii::$app->response->setStatusCode(204);
            try {
                \Yii::$app->end();
            } catch (ExitException $e) {
                throw new Exception($e->getMessage(),500);
            }
        }

        return $behaviors;
    }


    public function actionImages(): array
    {
        try {
            $model = new Upload();
            $info = $model->upImage();
            if($info && is_array($info)){
                return ResultHelper::json(200, '获取成功',$info);
            }else{
                return ResultHelper::json(400, '上传错误');
            }
        } catch (\Exception $e) {
            return ResultHelper::json(400, $e->getMessage(), (array)$e);
        }
    }


    public function actionFile(): array
   {

        try {
            $Upload = new Upload();
            //实例化上传验证类，传入上传配置参数项名称
            $model = new UploadValidate('uploadFile');
            $path =\Yii::$app->request->input('path');
            $is_chunk =\Yii::$app->request->input('is_chunk');
            $chunk_partSize =\Yii::$app->request->input('chunk_partSize');
            $chunk_partCount =\Yii::$app->request->input('chunk_partCount');
            $chunk_partIndex =\Yii::$app->request->input('chunk_partIndex');
            $md5 =\Yii::$app->request->input('md5');
            $chunk_md5 =\Yii::$app->request->input('chunk_md5');

            if (!empty($is_chunk)) {
                if (!$chunk_partSize) {
                    return  ResultHelper::json(400, '必须指明分片尺寸：chunk_partSize');
                }

                if (!$chunk_partCount) {
                    return  ResultHelper::json(400, '必须指明分片总数：chunk_partCount');
                }
            }

            //上传
            $info = $Upload::upFile($model, urldecode($path), $is_chunk, $chunk_partSize, $chunk_partCount, $chunk_partIndex, $md5, $chunk_md5);

            if ($info['status'] == 0) {
                return ResultHelper::json(200, $info['message'], $info['data']);
            } else {
                return ResultHelper::json(400, $info['message'], $info['data']);
            }
        } catch (\Exception $e) {
            return  ResultHelper::json(400, $e->getMessage());
        }
    }


    public function actionMerge()
   {
        try {
            $Upload = new Upload();
            //实例化上传验证类，传入上传配置参数项名称
//            $model = new UploadValidate('uploadFile');
            $file_name =\Yii::$app->request->input('file_name');
            $file_type =\Yii::$app->request->input('file_type');
            $file_size =\Yii::$app->request->input('file_size');
            $file_parts =\Yii::$app->request->input('file_parts');
            $chunk_partSize =\Yii::$app->request->input('chunk_partSize');
            //合并且进行云分片处理
            $info = $Upload::mergeFile($file_name, $file_type, $file_size, $file_parts, $chunk_partSize);

            if ($info['code'] == 0) {
                return ResultHelper::json(200, $info['message'], $info['data']);
            } else {
                $msg = json_decode($info['msg'], true);

                return ResultHelper::json(400, $msg['file'][0]);
            }
        } catch (\Exception $e) {
            exit(Json::htmlEncode([
                'code' => 1,
                'msg' => $e->getMessage(),
            ]));
        }
    }
}
