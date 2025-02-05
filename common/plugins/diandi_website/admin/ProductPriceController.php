<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-06-27 18:50:49
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-06-28 11:09:48
 */


namespace common\plugins\diandi_website\admin;

use Throwable;
use Yii;
use common\plugins\diandi_website\models\ProductPrice;
use common\plugins\diandi_website\models\searchs\ProductPriceSearch;
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
 * ProductPriceController implements the CRUD actions for ProductPrice model.
 */
class ProductPriceController extends AController
{
    public string $modelSearchName = "ProductPriceSearch";

    public $modelClass = '';


    /**
     * @SWG\Get(path="/diandi_website/product-price/index",
     *    tags={"产品价格 - 202206"},
     *    summary="列表",
     *     @SWG\Response(
     *         response = 200,
     *         description = "产品价格列表",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     * )
     */
    public function actionIndex(): array
    {
        $searchModel = new ProductPriceSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return ResultHelper::json(200, '获取成功', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @SWG\Get(path="/diandi_website/product-price/view/{id}",
     *    tags={"产品价格 - 202206"},
     *    summary="详情",
     *     @SWG\Response(
     *         response = 200,
     *         description = "产品价格详情",
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
     * @SWG\Post(path="/diandi_website/product-price/create",
     *    tags={"产品价格 - 202206"},
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
     *     type="string",
     *     description="解決方案ID(只在需要时填写)",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="name",
     *     type="string",
     *     description="产品名称",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="des",
     *     type="string",
     *     description="描述",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="price",
     *     type="integer",
     *     description="产品价格",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="show_price",
     *     type="string",
     *     description="展示价格",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="drift",
     *     type="integer",
     *     description="价格浮动（1：不变，2：增加，3：减少）",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="fun",
     *     type="string",
     *     description="产品功能",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="back_color",
     *     type="string",
     *     description="背景色",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="is_recommend",
     *     type="string",
     *     description="是否是推荐（-1:否，1：是）",
     *     required=true,
     *   ),
     * )
     */
    //[{"name":"商城所有功能","state":1},{"name":"商城功能","state":1},{"name":"分销功能","state":1},{"name":"会员等级功能","state":-1}]
    public function actionCreate(): array
    {
        $model = new ProductPrice();

        $data = Yii::$app->request->post();

        if ($model->load($data, '') && $model->save()) {

            return ResultHelper::json(200, '创建成功', $model->toArray());
        } else {
            $msg = ErrorsHelper::getModelError($model);
            return ResultHelper::json(400, $msg);
        }
    }

    /**
     * @SWG\Post(path="/diandi_website/product-price/update/{id}",
     *    tags={"产品价格 - 202206"},
     *    summary="编辑",
     *     @SWG\Response(
     *         response = 200,
     *         description = "编辑",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="ProductPrice_id",
     *     type="string",
     *     description="解決方案ID(只在需要时填写)",
     *     required=true,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="name",
     *     type="string",
     *     description="产品名称",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="des",
     *     type="string",
     *     description="描述",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="price",
     *     type="integer",
     *     description="产品价格",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="show_price",
     *     type="string",
     *     description="展示价格",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="drift",
     *     type="integer",
     *     description="价格浮动（1：不变，2：增加，3：减少）",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="fun",
     *     type="string",
     *     description="产品功能",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="back_color",
     *     type="string",
     *     description="产品功能",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="formData",
     *     name="is_recommend",
     *     type="string",
     *     description="是否是推荐（-1:否，1：是）",
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
     * @SWG\Delete(path="/diandi_website/product-price/delete/{id}",
     *    tags={"产品价格 - 202206"},
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
        } catch (Throwable $e) {
            return ResultHelper::json(400, $e->getMessage(), (array)$e);

        }

    }

    /**
     * Finds the ProductPrice model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return array|ActiveRecord the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id): array|ActiveRecord
    {
        if (($model = ProductPrice::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
