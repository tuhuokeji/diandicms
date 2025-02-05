<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-06-27 16:56:43
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-06-27 18:24:36
 */


namespace common\plugins\diandi_website\admin;

use Yii;
use common\plugins\diandi_website\models\SysWorth;
use common\plugins\diandi_website\models\searchs\SysWorthSearch;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\controllers\BaseController;
use admin\controllers\AController;
use common\helpers\ResultHelper;
use common\helpers\ErrorsHelper;


/**
 * SysWorthController implements the CRUD actions for SysWorth model.
 */
class SysWorthController extends AController
{
    public string $modelSearchName = "SysWorthSearch";

    public $modelClass = '';


    /**
     * @SWG\Get(path="/diandi_website/sys-worth/index",
     *    tags={"系统价值 - 202206"},
     *    summary="列表",
     *     @SWG\Response(
     *         response = 200,
     *         description = "系统价值列表",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     * )
     */
    public function actionIndex(): array
    {
        $searchModel = new SysWorthSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return ResultHelper::json(200, '获取成功', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @SWG\Get(path="/diandi_website/sys-worth/view/{id}",
     *    tags={"系统价值 - 202206"},
     *    summary="详情",
     *     @SWG\Response(
     *         response = 200,
     *         description = "系统价值详情",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     * )
     */
    public function actionView($id): array
    {

         try {
            $view = $this->findModel($id)->toArray();
        } catch (NotFoundHttpException $e) {
            return ResultHelper::json(400, $e->getMessage(), (array)$e);
        }

        return ResultHelper::json(200, '获取成功', $view);
    }

    /**
     * @SWG\Post(path="/diandi_website/sys-worth/create",
     *    tags={"系统价值 - 202206"},
     *    summary="添加",
     *     @SWG\Response(
     *         response = 200,
     *         description = "添加",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="solution_id",
     *     type="integer",
     *     description="解决方案ID",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="is_website",
     *     type="integer",
     *     description="是否是官网（-1：否，1：是）",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="title",
     *     type="string",
     *     description="标题",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="icon",
     *     type="string",
     *     description="ICON",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="des",
     *     type="string",
     *     description="描述",
     *     required=true,
     *   ),
     * )
     */
    public function actionCreate(): array
    {
        $model = new SysWorth();

        $data = Yii::$app->request->post();

        if ($model->load($data, '') && $model->save()) {

            return ResultHelper::json(200, '创建成功', $model->toArray());
        } else {
            $msg = ErrorsHelper::getModelError($model);
            return ResultHelper::json(400, $msg);
        }
    }

    /**
     * @SWG\Post(path="/diandi_website/sys-worth/update",
     *    tags={"客户案例"},
     *    summary="更新",
     *     @SWG\Response(
     *         response = 200,
     *         description = "更新",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="solution_id",
     *     type="integer",
     *     description="解决方案ID",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="is_website",
     *     type="integer",
     *     description="是否是官网（-1：否，1：是）",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="title",
     *     type="string",
     *     description="标题",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="icon",
     *     type="string",
     *     description="ICON",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="des",
     *     type="string",
     *     description="描述",
     *     required=false,
     *   ),
     * )
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);


        $data = Yii::$app->request->post();

        if ($model->load($data, '') && $model->save()) {

            return ResultHelper::json(200, '编辑成功', $model->toArray());
        } else {
            $msg = ErrorsHelper::getModelError($model);
            return ResultHelper::json(400, $msg);
        }
    }

    /**
     * @SWG\Delete(path="/diandi_website/sys-worth/delete/{id}",
     *    tags={"系统价值 - 202206"},
     *    summary="删除",
     *     @SWG\Response(
     *         response = 200,
     *         description = "删除",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     * )
     */
    public function actionDelete($id): array
    {
        try {
            $this->findModel($id)->delete();
            return ResultHelper::json(200, '删除成功');
        } catch (StaleObjectException|NotFoundHttpException $e) {
            return ResultHelper::json(400, $e->getMessage(), (array)$e);
        } catch (\Throwable $e) {
            return ResultHelper::json(400, $e->getMessage(), (array)$e);

        }

    }

    /**
     * Finds the SysWorth model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return array|ActiveRecord the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id): array|ActiveRecord
    {
        if (($model = SysWorth::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
