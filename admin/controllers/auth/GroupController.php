<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2020-05-04 17:44:12
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2023-03-18 14:04:10
 */

namespace admin\controllers\auth;

use admin\controllers\AController;
use common\helpers\ArrayHelper;
use common\helpers\ErrorsHelper;
use common\helpers\ResultHelper;
use diandi\addons\models\BlocStore;
use diandi\admin\acmodels\AuthItem as AcmodelsAuthItem;
use diandi\admin\acmodels\AuthItemChild;
use diandi\admin\components\Configs;
use diandi\admin\components\Item;
use diandi\admin\components\Route;
use diandi\admin\models\AuthItem;
use diandi\admin\models\Route as ModelsRoute;
use diandi\admin\models\searchs\UserGroupSearch;
use diandi\admin\models\UserGroup;
use Yii;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * GroupController implements the CRUD actions for UserGroup model.
 */
class GroupController extends AController
{
    public $modelClass = 'UserGroup';

    public int $searchLevel = 0;

    /**
     * Lists all UserGroup models.
     *
     * @return array
     */
    public function actionIndex(): array
    {
        $searchModel = new UserGroupSearch();

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return ResultHelper::json(200, '获取成功', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single UserGroup model.
     *
     * @param int $id
     *
     * @return array
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id): array
    {
        $model = $this->getGroups($id);
        $manager = Configs::authManager();

        $list = $manager->getAuths($model->item_id);
        $all = [];
        $assigneds = [];
        $assigned = $list['assigned'];

        $available = $list['available'];

        foreach ($list['all'] as $key => $value) {
            $value = ArrayHelper::toArray($value);
            foreach ($value as $k => &$val) {
                $val['key'] = $val['id'];
                $val['label'] = $val['name'];
                if ($key == 'role' && $val['id'] == $id) {
                    unset($value[$k]);
                }
            }
            $all[$key] = array_values($value);
        }

        foreach ($available as $key => $value) {
            $value = ArrayHelper::toArray($value);
            foreach ($value as &$val) {
                $val['key'] = $val['id'];
                $val['label'] = $val['name'];
            }

            $available[$key] = array_values($value);
            unset($value);
        }

        if (!empty($available['permission'])) {
            $available['permission'] = ArrayHelper::itemsMerge($available['permission'], 0, 'id', 'parent_id', 'children');
        }

        foreach ($assigned as $key => &$value) {
            $value = ArrayHelper::toArray($value);

            foreach ($value as &$val) {
                $val['key'] = $val['id'];
                $val['label'] = $val['name'];
                $assigneds[$key][] = $val['item_id'];
            }

            $assigned[$key] = array_values($value);
            unset($value);
        }

        return ResultHelper::json(200, '获取成功', [
            'all' => $all,
            'assigneds' => $assigned,
            'assignedKey' => $assigneds,
            'availables' => $available,
        ]);
    }

    public function actionChange(): array
   {
        $id =\Yii::$app->request->input('id');
        $items =\Yii::$app->request->input('items');

        if (empty($id)) {
            return ResultHelper::json(400, '参数ID不能为空');
        }

        if (empty($items)) {
            return ResultHelper::json(400, '参数items不能为空');
        }

        try {
            $model = $this->getGroups($id);
        } catch (NotFoundHttpException $e) {
            return ResultHelper::json(500,$e->getMessage());
        }

        if (key_exists('route', $items)) {
            $list = $items['route'];
            $remove_ids = AuthItemChild::find()->where([
                'parent_id' => $id,
                'child_type' => 0,
            ])->andWhere(['not in', 'item_id', $list])->select('item_id')->asArray()->column();
            if (!empty($remove_ids)) {
                $item = new Route([
                    'name' => $model['name'],
                    'title' => '',
                    'item_id' => $id,
                    'is_sys' => $model['is_sys'],
                    'child_type' => 0,
                    'description' => $model['description'],
                    'data' => '',
                    'pid' => 0,
                ]);
                $route = new ModelsRoute($item);
                $route->removeChildren([
                    'route' => $remove_ids,
                ]);
            }

            $have_ids = AuthItemChild::find()->where([
                'parent_id' => $id,
                'child_type' => 0,
            ])->select('item_id')->asArray()->column();

            $add_ids = array_diff($list, $have_ids);

            if (!empty($add_ids)) {
                $item = new Route([
                    'id' => $id,
                    'name' => $model['name'],
                    'item_id' => $id,
                    'title' => '',
                    'is_sys' => $model['is_sys'],
                    'child_type' => 0,
                    'description' => $model['description'],
                    'data' => '',
                    'pid' => 0,
                ]);
                $route = new ModelsRoute($item);
                $route->addChildren([
                    'route' => $add_ids,
                ], 2);
            }

            return ResultHelper::json(200, '操作成功');
        } elseif (key_exists('permission', $items)) {
            $list = $items['permission'];
            $remove_ids = AuthItemChild::find()->where([
                'parent_id' => $id,
                'child_type' => 1,
            ])->andWhere(['not in', 'item_id', $list])->select('item_id')->asArray()->column();
            if (!empty($remove_ids)) {
                $item = new Item([
                    'name' => $model['name'],
                    'is_sys' => $model['is_sys'],
                    'parent_id' => $id,
                    'item_id' => $id,
                    'child_type' => 1,
                    'ruleName' => '',
                    'description' => $model['description'],
                    'data' => '',
                ]);
                $permission = new AuthItem($item);
                $permission->removeChildren([
                    'permission' => $remove_ids,
                ]);
            }

            $have_ids = AuthItemChild::find()->where([
                'parent_id' => $id,
                'child_type' => 1,
            ])->select('item_id')->asArray()->column();
            
            $add_ids = array_diff($list, $have_ids);

            if (!empty($add_ids)) {
                $item = new Item([
                    'id' => $id,
                    'item_id' => $id,
                    'name' => $model['name'],
                    'is_sys' => $model['is_sys'],
                    'parent_id' => null,
                    'child_type' => 1,
                    'ruleName' => '',
                    'description' => $model['description'],
                    'data' => '',
                ]);
                $permission = new AuthItem($item);
                $permission->addChildren([
                    'permission' => $add_ids,
                ], 2);
            }

            return ResultHelper::json(200, '操作成功');
        } elseif (key_exists('role', $items)) {
            $list = $items['role'];
            $group = UserGroup::findOne($id);
            $group->item_id = $group->id;
            $model = new UserGroup($group);

            $remove_ids = AuthItemChild::find()->where([
                'parent_id' => $id,
                'child_type' => 2,
            ])->andWhere(['not in', 'item_id', $list])->select('item_id')->asArray()->column();
            if (!empty($remove_ids)) {
                $model->removeChildren(['group' => $remove_ids]);
            }

            $have_ids = AuthItemChild::find()->where([
                'parent_id' => $id,
                'child_type' => 2,
            ])->select('item_id')->asArray()->column();

            $add_ids = array_diff($list, $have_ids);

            if (!empty($add_ids)) {
                $model->addChildren(['group' => $add_ids]);
            }

            return ResultHelper::json(200, '操作成功');
        }
        return ResultHelper::json(200, '操作成功');

    }

    /**
     * Assign items.
     *
     * @param string $id
     *
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionAssign(string $id): array
    {
        $manager = Configs::authManager();

        $items = Yii::$app->getRequest()->post('items', []);
        $model = $this->getGroups($id);

        $success = 0;

        // 用户组
        if ($items['role']) {
            $success += $model->addChildren($items['role']);
        }

        // 权限
        if ($items['permission']) {
            $item = new Item([
                'id' => $id,
                'item_id' => $id,
                'name' => $model['name'],
                'is_sys' => $model['is_sys'],
                'parent_id' => null,
                'child_type' => 1,
                'ruleName' => '',
                'description' => $model['description'],
                'data' => '',
            ]);
            $permission = new AuthItem($item);
            $success += $permission->addChildren($items, 2);
        }

        // 路由
        if ($items['route']) {
            $item = new Route([
                'id' => $id,
                'name' => $model['name'],
                'item_id' => $model['item_id'],
                'title' => '',
                'is_sys' => $model['is_sys'],
                'child_type' => 0,
                'description' => $model['description'],
                'data' => '',
                'pid' => 0,
            ]);
            $route = new ModelsRoute($item);
            $success += $route->addChildren($items['route'], 2);
        }

        Yii::$app->getResponse()->format = 'json';

        $items = $manager->getAuths($model['name']);

        return array_merge($items, ['success' => $success]);
    }

    /**
     * Assign or remove items.
     *
     * @param string $id
     *
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionRemove(string $id): array
    {
        $items = Yii::$app->getRequest()->post('items', []);
        $model = $this->getGroups($id);
        $success = 0;

        // 规则
        if ($items['group']) {
            $success += $model->removeChildren($items);
        }

        // 权限
        if ($items['permission']) {
            $item = new Item([
                'name' => $model['name'],
                'is_sys' => $model['is_sys'],
                'parent_id' => null,
                'child_type' => 1,
                'ruleName' => '',
                'description' => $model['description'],
                'data' => '',
            ]);
            $permission = new AuthItem($item);
            $success += $permission->removeChildren($items);
        }

        // 路由
        if ($items['route']) {
            $item = new Route([
                'name' => $model['name'],
                'title' => '',
                'is_sys' => $model['is_sys'],
                'child_type' => 0,
                'description' => $model['description'],
                'data' => '',
                'pid' => 0,
            ]);
            $route = new ModelsRoute($item);
            $success += $route->removeChildren($items);
        }

        Yii::$app->getResponse()->format = 'json';
        $manager = Configs::authManager();

        $items = $manager->getAuths($model['name']);

        return array_merge($items, ['success' => $success]);
    }

    /**
     * Creates a new UserGroup model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return array
     */
    public function actionCreate(): array
    {
        $model = new UserGroup();

            $data = Yii::$app->request->post();
            $data['bloc_id'] = $data['store_id'] ? BlocStore::find()->where(['store_id' => $data['store_id']])->select('bloc_id')->scalar() : 0;
            if ($model->load($data, '') && $model->save()) {
                // 给item同步添加数据
                $AcmodelsAuthItem = new AcmodelsAuthItem();
                $items = [
                    'permission_type' => 2,
                    'name' => $model->name,
                    'is_sys' => $model->is_sys,
                    'parent_id' => 0,
                    'permission_level' => 0,
                ];

                if ($AcmodelsAuthItem->load($items, '') && $AcmodelsAuthItem->save()) {
                    $model->updateAll([
                        'item_id' => $AcmodelsAuthItem->id,
                    ], [
                        'id' => $model->id,
                    ]);
                }

                return ResultHelper::json(200, '创建成功', $model->toArray());
            } else {
                $msg = ErrorsHelper::getModelError($model);

                return ResultHelper::json(400, $msg);
            }

    }

    /**
     * Updates an existing UserGroup model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param int $id
     *
     * @return array
     *
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionUpdate($id): array
   {
        $model = UserGroup::findOne($id);

        $old_parent = $model->name;


            $data = Yii::$app->request->post();

            $data['bloc_id'] = $data['store_id'] ? BlocStore::find()->where(['store_id' => $data['store_id']])->select('bloc_id')->scalar() : 0;

            if ($model->load($data, '') && $model->save()) {
                if ($old_parent !=\Yii::$app->request->input('name')) {
                    AuthItemChild::updateAll([
                        'parent' =>\Yii::$app->request->input('name'),
                    ], [
                        'parent_type' => 2,
                        'parent_id' => $model->item_id,
                    ]);
                }

                // 给item同步添加数据
                $AcmodelsAuthItem = new AcmodelsAuthItem();
                $items = [
                    'permission_type' => 2,
                    'name' => $model->name,
                    'is_sys' => $model->is_sys,
                    'parent_id' => 0,
                    'permission_level' => 0,
                ];
                // 首先查询是否存在
                $isHave = $AcmodelsAuthItem->find()->where(['id' => $model->item_id])->one();
                if ($isHave) {
                    $AcmodelsAuthItem->updateAll($items, [
                        'id' => $model->item_id,
                    ]);
                } else {
                    $AcmodelsAuthItem->load($items, '') && $AcmodelsAuthItem->save();
                    // 修复item_id被删除的情况
                    $model->item_id = $AcmodelsAuthItem->id;
                    $model->update();
                    // 修复子权限不对应的情况
                    AuthItemChild::updateAll([
                        'parent_type' => 2,
                        'parent_id' => $model->item_id,
                        'parent' =>\Yii::$app->request->input('name'),
                    ], [
                        'parent' => $old_parent,
                    ]);
                }

                return ResultHelper::json(200, '编辑成功', $model->toArray());
            } else {
                $msg = ErrorsHelper::getModelError($model);

                return ResultHelper::json(400, $msg);
            }
    }

    /**
     * Deletes an existing UserGroup model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param int $id
     *
     * @return array
     *
     * @throws StaleObjectException
     * @throws \Throwable
     */
    public function actionDelete($id): array
    {
        UserGroup::findOne($id)->delete();

        return ResultHelper::json(200, '删除成功');
    }



    /**
     * Finds the UserGroup model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return UserGroup the loaded model
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function getGroups(int $id): UserGroup
    {
        if (($model = UserGroup::findOne($id)) !== null) {
            return new UserGroup($model);
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
