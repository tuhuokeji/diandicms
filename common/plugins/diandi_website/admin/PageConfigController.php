<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-04-20 19:11:18
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-05-05 10:33:43
 */

namespace common\plugins\diandi_website\admin;

use common\plugins\diandi_website\models\searchs\WebsitePageConfig as WebsitePageConfigSearch;
use common\plugins\diandi_website\models\WebsitePageConfig;
use admin\controllers\AController;
use common\helpers\ErrorsHelper;
use common\helpers\ResultHelper;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * PageConfigController implements the CRUD actions for WebsitePageConfig model.
 */
class PageConfigController extends AController
{
    public string $modelSearchName = 'WebsitePageConfig';

    public $modelClass = '';

    /**
     * @SWG\Get(path="/diandi_website/page-config/index",
     *    tags={"页面配置"},
     *    summary="数据列表",
     *     @SWG\Response(
     *         response = 200,
     *         description = "数据列表",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     * )
     */
    public function actionIndex(): array
    {
        $searchModel = new WebsitePageConfigSearch();

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return ResultHelper::json(200, '获取成功', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @SWG\Get(path="/diandi_website/page-config/view",
     *    tags={"页面配置"},
     *    summary="详情",
     *     @SWG\Response(
     *         response = 200,
     *         description = "详情",
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
     * @SWG\Post(path="/diandi_website/page-config/create",
     *    tags={"页面配置"},
     *    summary="创建",
     *     @SWG\Response(
     *         response = 200,
     *         description = "创建",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     *     @SWG\Parameter(
     *     in="formData",
     *     name="title",
     *     type="string",
     *     description="标题",
     *     required=true,
     *   ),
     *     @SWG\Parameter(
     *     in="formData",
     *     name="template",
     *     type="string",
     *     description="模板",
     *     required=true,
     *   ),
     *     @SWG\Parameter(
     *     in="formData",
     *     name="type",
     *     type="integer",
     *     description="分类",
     *     required=true,
     *   ),
     * )
     */
    public function actionCreate(): array
    {
        $model = new WebsitePageConfig();
        $data = Yii::$app->request->post();

        if ($model->load($data, '') && $model->save()) {
            return ResultHelper::json(200, '创建成功', $model->toArray());
        } else {
            $msg = ErrorsHelper::getModelError($model);

            return ResultHelper::json(400, $msg);
        }
    }

    /**
     * @SWG\Post(path="/diandi_website/page-config/update",
     *    tags={"页面配置"},
     *    summary="更新",
     *     @SWG\Response(
     *         response = 200,
     *         description = "更新",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     *     @SWG\Parameter(
     *     in="formData",
     *     name="title",
     *     type="string",
     *     description="标题",
     *     required=true,
     *   ),
     *     @SWG\Parameter(
     *     in="formData",
     *     name="template",
     *     type="string",
     *     description="模板",
     *     required=true,
     *   ),
     *     @SWG\Parameter(
     *     in="formData",
     *     name="type",
     *     type="integer",
     *     description="分类",
     *     required=true,
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
     * @SWG\Get(path="/diandi_website/page-config/delete",
     *    tags={"页面配置"},
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
     * Finds the WebsitePageConfig model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return array|ActiveRecord the loaded model
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id): array|ActiveRecord
    {
        if (($model = WebsitePageConfig::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
