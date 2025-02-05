<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2020-05-11 15:07:52
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2023-07-14 15:53:51
 */

namespace admin\controllers\addons;

use admin\controllers\AController;
use admin\models\addons\models\Bloc;
use admin\models\enums\StoreStatus;
use admin\services\StoreService;
use admin\services\UserService;
use common\helpers\ArrayHelper;
use common\helpers\ErrorsHelper;
use common\helpers\LevelTplHelper;
use common\helpers\ResultHelper;
use common\models\DdRegion;
use diandi\addons\models\Bloc as ModelsBloc;
use diandi\addons\models\BlocStore;
use diandi\addons\models\searchs\BlocStoreSearch;
use diandi\addons\models\searchs\StoreCategory;
use diandi\addons\models\StoreLabel;
use diandi\addons\models\StoreLabelLink;
use Yii;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

/**
 * StoreController implements the CRUD actions for BlocStore model.
 */
class StoreController extends AController
{
    public string $modelSearchName = 'BlocStore';

    public $modelClass = '';

    public $bloc_id;

    public $extras = [];

    public function actions(): array
    {
        $this->bloc_id = Yii::$app->request->get('bloc_id', 0);
        $actions = parent::actions();
        $actions['get-region'] = [
            'class' => \diandi\region\RegionAction::className(),
            'model' => DdRegion::className(),
        ];

        return $actions;
    }

    /**
     * Lists all BlocStore models.
     *
     * @return array
     */
    public function actionIndex(): array
    {
        $bloc_id = $this->bloc_id ?? Yii::$app->params['bloc_id'];

        $searchModel = new BlocStoreSearch();

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return ResultHelper::json(200, '获取成功', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @return array
     */
    public function actionChildcate(): array
    {
            $data = Yii::$app->request->post();
            $parent_id = $data['parent_id'];
            $cates = StoreCategory::findAll(['parent_id' => $parent_id]);

            return ResultHelper::json(200, '获取成功', $cates);
    }

    public function actionCategory(): array
    {
        $cates = StoreCategory::find()->select(['parent_id', 'category_id', 'name as label', 'category_id as value'])->asArray()->all();
        $list = ArrayHelper::itemsMerge($cates, 0, 'category_id', 'parent_id', 'children');

        return ResultHelper::json(200, '获取成功', $list);
    }

    /**
     * Displays a single BlocStore model.
     *
     * @param int $id
     *
     * @return array
     *
     */
    public function actionView($id): array
    {
        $BlocStore = new BlocStore([
            'extras' => $this->extras,
        ]);
        $lables = StoreLabel::find()->indexBy('id')->asArray()->all();
        $detail = $BlocStore::find()->where(['store_id' => $id])->with(['label'])->asArray()->one();
        if(empty($detail)){
            return ResultHelper::json(400, '商户不存在');
        }
        $detail['extra'] = !empty($detail['extra']) ? unserialize($detail['extra']):[];
        $detail['county'] = (int) $detail['county'];
        $detail['province'] = (int) $detail['province'];
        $detail['provinceCityDistrict'] = [
            (int) $detail['province'], (int) $detail['city'], (int) $detail['county'],
        ];
        $detail['category'] = [
            $detail['category_pid'],
            $detail['category_id'],
        ];

        $detail['address'] = [
            'address' => $detail['address'],
            'lat' => $detail['latitude'],
            'lng' => $detail['longitude'],
        ];

        if (!empty($detail['label']) && is_array($detail['label'])) {
            foreach ($detail['label'] as $key => $value) {
                if (!empty($lables[$value['label_id']])) {
                    $detail['label_link'][] = $lables[$value['label_id']]['id'];
                }
            }
        }

        $oss = Yii::$app->params['conf']['oss'];
        $storage = $oss ? $oss['remote_type'] : '';
        $url = match ($storage) {
            'alioss' => $oss ? $oss['Aliyunoss_url'] : '',
            'qiniu' => $oss ? $oss['Qiniuoss_url'] : '',
            'cos' => $oss ? $oss['Tengxunoss_url'] : '',
            default => Yii::$app->request->hostInfo,
        };
        $detail['config'] = [
            'attachmentUrl' => $url . '/attachment',
        ];

        return ResultHelper::json(200, '获取成功', $detail);
    }

    /**
     * Creates a new BlocStore model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return array
     * @throws HttpException
     */
    public function actionCreate(): array
   {

        $model = new BlocStore([
            'extras' => $this->extras,
        ]);

        $modelcate = new StoreCategory();

        $Helper = new LevelTplHelper([
            'pid' => 'parent_id',
            'cid' => 'category_id',
            'title' => 'name',
            'model' => $modelcate,
            'id' => 'category_id',
        ]);

        $link = new StoreLabelLink();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            $data['lng_lat'] = json_encode([
                'lng' => $data['longitude'],
                'lat' => $data['latitude'],
            ]);

            if ($model->load($data, '') && $model->save()) {
                $StoreLabelLink = Yii::$app->request->input('label_link');
                if (!empty($StoreLabelLink) && is_array($StoreLabelLink)) {

                    foreach ($StoreLabelLink as $key => $label_id) {
                        if (!empty($label_id) && is_numeric($label_id)) {
                            $_link = clone  $link;
                            $bloc_id = $model->bloc_id;
                            $store_id = $model->store_id;
                            $data = [
                                'bloc_id' => $bloc_id,
                                'store_id' => $store_id,
                                'label_id' => $label_id,
                            ];
                            $_link->setAttributes($data);
                            $_link->save();
                        }
                    }
                }

                return ResultHelper::json(200, '获取成功',['id' => $model->store_id, 'bloc_id' => $model->bloc_id]);
            } else {
                $msg = ErrorsHelper::getModelError($model);

                throw new HttpException(400, $msg);
            }
        }

        $labels = StoreLabel::find()->select(['id', 'name'])->indexBy(
            'id'
        )->asArray()->all();

        $linkValue = [];

        return ResultHelper::json(200, '获取成功', [
            'link' => $link,
            'linkValue' => $linkValue,
            'labels' => $labels,
            'model' => $model,
            'Helper' => $Helper,
            'bloc_id' => $this->bloc_id,
        ]);
    }

    /**
     * Updates an existing BlocStore model.
     * If the update is successful, the browser will be redirected to the 'view' page.
     *
     * @param int $id
     *
     * @return array
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id): array
   {

        $model = $this->findModel($id);
        $model['extra'] = unserialize($model['extra']);
        $link = new StoreLabelLink();

        $bloc_id = $model->bloc_id;
        $store_id = $model->store_id;
        $data = Yii::$app->request->post();

        $data['category_pid'] = $data['category'][0];
        $data['category_id'] = $data['category'][1];

        $data['province'] = $data['provinceCityDistrict'][0];
        $data['city'] = $data['provinceCityDistrict'][1];
        $data['county'] = $data['provinceCityDistrict'][2];

        if ($model->load($data, '') && $model->save()) {
            $StoreLabelLink = Yii::$app->request->input('label_link');
            $link->deleteAll([
                'store_id' => $store_id,
            ]);
            if (!empty($StoreLabelLink) && is_array($StoreLabelLink)) {

                foreach ($StoreLabelLink as $key => $label_id) {
                    if (!empty($label_id) && is_numeric($label_id)) {
                        $_link = clone  $link;
                        $data = [
                            'bloc_id' => $bloc_id,
                            'store_id' => $store_id,
                            'label_id' => $label_id,
                        ];
                        $_link->setAttributes($data);
                        $_link->save();
                    }
                }
            }

            return ResultHelper::json(200, '更新成功');
        } else {
            $error = ErrorsHelper::getModelError($model);

            return ResultHelper::json(401, $error);
        }
    }

    public function actionStorelabel(): array
    {
        $label = new StoreLabel();
        $lists = $label->find()->select(['name as text', 'id as value'])->asArray()->all();

        return ResultHelper::json(200, '获取成功', $lists);
    }

    public function actionBlocs(): array
    {
        $model = new Bloc();

        $lists = $model->find()->select(['bloc_id', 'pid', 'business_name as label', 'bloc_id as id'])->asArray()->all();

        $list = ArrayHelper::itemsMerge($lists, 0, 'bloc_id', 'pid', 'children');

        return ResultHelper::json(200, '获取成功', $list);
    }

    public function actionStorestatus(): array
    {
        $list = StoreStatus::listData();

        $lists = [];

        foreach ($list as $key => $value) {
            $lists[] = [
                'text' => $value,
                'value' => $key,
            ];
        }

        return ResultHelper::json(200, '获取成功', $lists);
    }

    /**
     * Deletes an existing BlocStore model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param int $id
     *
     * @return array
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id): array
    {
        $this->findModel($id)->delete();
        $bloc_id = $this->bloc_id;

        return ResultHelper::json(200, '删除成功');
    }

    /**
     * Finds the BlocStore model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return BlocStore the loaded model
     *
     */
    protected function findModel($id): array|\yii\db\ActiveRecord
    {
        $BlocStore = new BlocStore([
            'extras' => $this->extras,
        ]);
        if (($model = $BlocStore::findOne($id)) !== null) {
            return $model;
        }

        return ResultHelper::json(500, '请检查数据是否存在');
    }

    public function actionStoreCreate(): array
   {
       $data = Yii::$app->request->input();

        // 校验公司是否存储
        $bloc_id = (int)Yii::$app->request->input('bloc_id',0);
        $have_bloc = ModelsBloc::find()->where(['bloc_id' => $bloc_id])->asArray()->one();
        if (!$have_bloc) {
            return ResultHelper::json(400, '管理员授权公司不存在');
        }
        $store = StoreService::createStore($data, Yii::$app->request->input('mid'), Yii::$app->request->input('extras'));
        // 创建成功，重新返回用户权限数据
        $list = UserService::getUserMenus();
        $list['store'] = $store;

        return ResultHelper::json(200, '创建成功', $list);
    }
}
