<?php

/**
 * @Author: Radish <minradish@163.com>
 * @Date:   2022-10-09 15:34:46
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2023-03-14 17:49:34
 */

namespace admin\models\message;

use Codeception\Lib\Console\Message;
use common\models\DdUser;
use common\models\UserBloc;
use common\models\UserStore;
use Yii;
use yii\db\Exception;

/**
 * This is the model class for table "dd_messages".
 *
 * @public int $id ID
 * @public int $bloc_id 企业ID
 * @public int $store_id 商户ID
 * @public int $category_id 分类ID
 * @public string $title 标题
 * @public string $content 内容
 * @public string $admin_ids 接收者IDS
 * @public string $publish_at 发布时间
 * @public int $view 查看次数
 * @public int $status 状态
 * @public string $created_at 创建时间
 * @public string $updated_at 更新时间
 */
class HubMessages extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%messages}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['bloc_id', 'store_id', 'category_id', 'view', 'status'], 'integer'],
            [['category_id', 'title', 'content', 'publish_at'], 'required'],
            [['content'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 45],
            [['admin_ids'], 'string', 'max' => 450],
            ['admin_ids', 'checkAdminIds'],
            [['publish_at'], 'date', 'format' => 'php:Y-m-d H:i:s'],
            ['category_id', 'exist', 'targetClass' => 'admin\models\message\HubMessagesCategory', 'targetAttribute' => 'id', 'message' => '指定分类不存在！'],
        ];
    }

    public function checkAdminIds($field, $scenario, $validator, $value)
    {
        $ids = explode(',', $value);
        if ($ids) {
            $data = DdUser::find()->where(['id' => $ids])->select('id')->asArray()->all();
            sort($ids);
            $dataIds = array_column($data, 'id');
            sort($dataIds);
            if ($dataIds != $ids) {
                $this->addError('admin_ids', '无效的管理员ID:' . implode(',', array_diff($ids, $dataIds)));
                return false;
            }
        }
        return true;
    }

    /**
     * 行为.
     */
    public function behaviors()
    {
        /*自动添加创建和修改时间*/
        return [
            [
                'class' => \common\behaviors\SaveBehavior::className(),
                'updatedAttribute' => 'updated_at',
                'createdAttribute' => 'created_at',
                'time_type' => 'datetime',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'bloc_id' => '企业ID',
            'store_id' => '商户ID',
            'category_id' => '分类ID',
            'title' => '标题',
            'content' => '内容',
            'admin_ids' => '接收者IDS',
            'publish_at' => '发布时间',
            'view' => '查看次数',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    public function getCurrentUserRead(): \yii\db\ActiveQuery
    {
        return $this->hasOne(HubMessagesRead::class, ['message_id' => 'id'])->where(['admin_id' => Yii::$app->user->identity->user_id]);
    }

    /**
     * 统计管理员未读数
     * @date 2022-10-11 周二
     * @param int $adminId 管理员ID
     * @return int
     * @throws Exception
     * @author Radish <minradish@163.com>
     */
    public static function countUnread($adminId): int
    {
        // 查找我授权的
        $bloc_ids = UserBloc::find()->where(['user_id' => Yii::$app->user->identity->user_id])->andWhere(['>','bloc_id',0])->select('bloc_id')->column();
        $bloc_ids_str = $bloc_ids ? implode(',', $bloc_ids) : '';

        $store_ids = UserStore::find()->where(['user_id' => Yii::$app->user->identity->user_id])->andWhere(['>','store_id',0])->select('store_id')->column();
        
        $store_ids_str = $store_ids ? implode(',', $store_ids) : '';

        $sql = <<<SQL
        SELECT
            count( 1 ) as num
        FROM
            `dd_messages`
            LEFT JOIN ( SELECT * FROM `dd_messages_read` WHERE admin_id = {$adminId} ) AS b ON b.message_id = dd_messages.id
        WHERE
            ( dd_messages.admin_ids = '' OR find_in_set( {$adminId}, dd_messages.admin_ids ) )
            AND
            b.id IS NULL
SQL;
        if ($bloc_ids_str) {
            $sql .= <<<SQL
    AND
    dd_messages.bloc_id IN ({$bloc_ids_str})
SQL;
        }

        if ($store_ids_str) {
            $sql .= <<<SQL
    AND
    dd_messages.store_id IN ({$store_ids_str})
SQL;
        }

        $count = Yii::$app->getDb()->createCommand($sql)->queryOne();
        $count = $count['num'] ?? 0;
        return $count;
    }
}
