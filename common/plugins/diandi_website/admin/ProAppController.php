<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-06-06 14:51:40
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-06-06 18:24:07
 */

namespace common\plugins\diandi_website\admin;

use admin\controllers\AController;
use common\helpers\ErrorsHelper;
use common\helpers\ImageHelper;
use common\helpers\ResultHelper;
use common\plugins\diandi_website\models\searchs\WebsiteProApp as WebsiteProAppSearch;
use common\plugins\diandi_website\models\WebsiteProApp;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * ProAppController implements the CRUD actions for WebsiteProApp model.
 */
class ProAppController extends AController
{
    public string $modelSearchName = 'WebsiteProAppSearch';

    public $modelClass = '';

    /**
     * @SWG\Get(path="/diandi_website/pro-App/index",
     *    tags={"应用中心"},
     *    summary="列表详情",
     *     @SWG\Response(
     *         response = 200,
     *         description = "列表详情",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     * )
     */
    public function actionIndex(): array
    {
        $searchModel = new WebsiteProAppSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return ResultHelper::json(200, '获取成功', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @SWG\Get(path="/diandi_website/pro-App/view",
     *    tags={"应用中心"},
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
            $view['logo'] = ImageHelper::tomedia($view['logo']);

            return ResultHelper::json(200, '获取成功', $view);
        } catch (NotFoundHttpException $e) {
            return ResultHelper::json(400, $e->getMessage(), (array)$e);
        }

    }

    /**
     * @SWG\Post(path="/diandi_website/pro-App/create",
     *    tags={"应用中心"},
     *    summary="添加",
     *     @SWG\Response(
     *         response = 200,
     *         description = "添加",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     *    @SWG\Parameter(
     *     in="query",
     *     name="title",
     *     type="string",
     *     description="标题",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="query",
     *     name="logo",
     *     type="string",
     *     description="logo",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="query",
     *     name="link",
     *     type="string",
     *     description="立即使用链接地址",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="query",
     *     name="content",
     *     type="string",
     *     description="内容",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="query",
     *     name="tip1",
     *     type="string",
     *     description="标签1",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="query",
     *     name="tip2",
     *     type="string",
     *     description="标签2",
     *     required=false,
     *   ),
     * )
     */
    public function actionCreate(): array
    {
        $model = new WebsiteProApp();

        $data = Yii::$app->request->post();

        if ($model->load($data, '') && $model->save()) {
            return ResultHelper::json(200, '创建成功', $model->toArray());
        } else {
            $msg = ErrorsHelper::getModelError($model);

            return ResultHelper::json(400, $msg);
        }
    }

    /**
     * @SWG\Post(path="/diandi_website/pro-App/update",
     *    tags={"应用中心"},
     *    summary="更新",
     *     @SWG\Response(
     *         response = 200,
     *         description = "更新",
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     *    @SWG\Parameter(
     *     in="query",
     *     name="title",
     *     type="string",
     *     description="标题",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="query",
     *     name="logo",
     *     type="string",
     *     description="logo",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="query",
     *     name="link",
     *     type="string",
     *     description="立即使用链接地址",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="query",
     *     name="content",
     *     type="string",
     *     description="内容",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="query",
     *     name="tip1",
     *     type="string",
     *     description="标签1",
     *     required=false,
     *   ),
     *    @SWG\Parameter(
     *     in="query",
     *     name="tip2",
     *     type="string",
     *     description="标签2",
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
     * @SWG\Get(path="/diandi_website/pro-App/delete",
     *    tags={"应用中心"},
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
     * Finds the WebsiteProApp model based on its primary key value.
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
        if (($model = WebsiteProApp::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
