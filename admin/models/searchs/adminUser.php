<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-12-22 23:06:50
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-10-28 20:08:02
 */

namespace admin\models\searchs;

use admin\models\User;
use common\components\DataProvider\ArrayDataProvider;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;

/**
 * adminUser represents the model behind the search form of `admin\models\User`.
 * @property mixed|null $id
 * @property mixed $store_id
 * @property mixed|null $is_login
 * @property int|mixed $status
 * @property mixed $bloc_id
 * @property $username
 * @property $email
 */
class adminUser extends User
{
    public mixed $group_id = 0;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'store_id', 'bloc_id', 'status'], 'integer'],
            [['username', 'email', 'mobile', 'group_id'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with a search query applied.
     *
     * @param array $params
     *
     * @return ArrayDataProvider|bool|ActiveDataProvider
     */
    public function search(array $params): ArrayDataProvider|bool|ActiveDataProvider
   {
        $query = User::find()->joinWith('userGroup as userGroup');

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return false;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'is_login' => $this->is_login
        ]);

        if (!empty($this->group_id)) {
            $query->andFilterWhere([
                'userGroup.group_id' => $this->group_id
            ]);
        }

        if (!empty($this->store_id)) {
            $query->andFilterWhere([
                'store_id' => $this->store_id
            ]);
        }

        if (!empty($this->bloc_id)) {
            $query->andFilterWhere([
                'bloc_id' => $this->bloc_id
            ]);
        }

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'email', $this->email]);
        $count = $query->count();
        $pageSize =\Yii::$app->request->input('pageSize') ?? 10;
        $page =\Yii::$app->request->input('page') ?? 1;
        // 使用总数来创建一个分页对象
        $pagination = new Pagination([
            'totalCount' => $count,
            'pageSize' => $pageSize,
            'page' => $page - 1,
            // 'pageParam'=>'page'
        ]);

        $list = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->asArray()
            ->all();

        //foreach ($list as $key => &$value) {
        //    $value['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
        //    $value['update_time'] = date('Y-m-d H:i:s',$value['update_time']);
        //}

        return new ArrayDataProvider([
            'key' => 'id',
            'allModels' => $list,
            'totalCount' => $count ?? 0,
            'total' => $count ?? 0,
            'sort' => [
                'attributes' => [
                    //'member_id',
                ],
                'defaultOrder' => [
                    //'member_id' => SORT_DESC,
                ],
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
    }
}
