<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2020-10-27 14:36:10
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-08-08 14:53:43
 */

namespace admin\controllers\member;

use admin\controllers\AController;
use common\helpers\ErrorsHelper;
use common\helpers\ResultHelper;
use common\models\DdMemberGroup;
use common\models\searchs\DdMemberGroupSearch;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * DdMemberGroupController implements the CRUD actions for DdMemberGroup model.
 */
class DdMemberGroupController extends AController
{
    public $modelClass = '';
    public string $modelSearchName = 'DdMemberGroupSearch';

    /**
     * Lists all DdMemberGroup models.
     *
     * @return array
     */
    public function actionIndex(): array
    {
        $searchModel = new DdMemberGroupSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return ResultHelper::json(200, '获取成功', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single DdMemberGroup model.
     *
     * @param string $id
     *
     * @return array
     *
     */
    public function actionView($id): array
    {
        return ResultHelper::json(200, '获取成功', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new DdMemberGroup model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return array
     */
    public function actionCreate(): array
    {
        $model = new DdMemberGroup();

            $data = Yii::$app->request->post();

            if ($model->load($data, '') && $model->save()) {
                return ResultHelper::json(200, '创建成功', $model->toArray());
            } else {
                $msg = ErrorsHelper::getModelError($model);

                return ResultHelper::json(400, $msg);
            }

    }

    /**
     * Updates an existing DdMemberGroup model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param string $id
     *
     * @return array
     *
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);

        $data = Yii::$app->request->post();

        if ($model->load($data, '') && $model->save()) {
            return ResultHelper::json(200, '编辑成功', $model);
        } else {
            $msg = ErrorsHelper::getModelError($model);

            return ResultHelper::json(400, $msg);
        }

    }

    /**
     * Deletes an existing DdMemberGroup model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param string $id
     *
     * @return array
     *
     * @throws NotFoundHttpException if the model cannot be found
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($id): array
    {
        $this->findModel($id)->delete();

        return ResultHelper::json(200, '删除成功');
    }

    /**
     * Finds the DdMemberGroup model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param string $id
     *
     * @return array|ActiveRecord the loaded model
     *
     */
    protected function findModel($id): array|\yii\db\ActiveRecord
    {
        if (($model = DdMemberGroup::findOne($id)) !== null) {
            return $model;
        }

        return ResultHelper::json(500, '请检查数据是否存在');
    }
}
