<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-26 00:09:42
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2020-08-11 23:23:58
 */
echo "<?php\n";
?>

namespace <?= $generator->getControllerNamespace(); ?>;

use Yii;
use yii\db\Migration;
use common\interfaces\AddonWidget;

/**
 * 升级数据库
 *
 * Class Upgrade
 * @package addons\Merchants
 */
class Upgrade extends Migration implements AddonWidget
{
    /**
     * @var array
     */
    public array $versions = [
        '1.0.0', // 默认版本
        '1.0.1',
        '1.0.2',
    ];

    /**
     * @param $addon
     * @return mixed|void
     * @throws \yii\db\Exception
     */
    public function run($addon)
    {
        switch ($addon->version) {
            case '1.0.1':
                // 增加测试 - 冗余的字段
                // $this->addColumn('{{%addon_example_curd}}', 'field1', 'varchar(48)');
                break;
            case '1.0.2':
                // 删除测试 - 冗余的字段
                // $this->dropColumn('{{%addon_example_curd}}', 'field2');
                break;
        }
    }
}
