<?php

use yii\db\Migration;

class m231104_123104_diandi_hub_level_condition extends Migration
{
    public function up()
    {
        /* 取消外键约束 */
        $this->execute('SET foreign_key_checks = 0');
        
        /* 创建表 */
        $this->createTable('{{%diandi_hub_level_condition}}', [
            'id' => "int(11) NOT NULL AUTO_INCREMENT",
            'levelnum' => "int(11) NOT NULL DEFAULT '0' COMMENT '当前等级'",
            'levelcnum' => "int(11) NULL DEFAULT '0' COMMENT '对应等级'",
            'levelc_num' => "int(11) NULL DEFAULT '0' COMMENT '对应人数'",
            'levelc_saletotal' => "int(11) NULL DEFAULT '0' COMMENT '对应销售额'",
            'condition' => "int(11) NULL DEFAULT '0' COMMENT '人数与销售额的关系'",
            'create_time' => "int(11) NULL COMMENT '创建时间'",
            'update_time' => "int(11) NULL COMMENT '更新时间'",
            'PRIMARY KEY (`id`)'
        ], "ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=FIXED");
        
        /* 索引设置 */
        
        
        /* 表数据 */
        
        /* 设置外键约束 */
        $this->execute('SET foreign_key_checks = 1;');
    }

    public function down()
    {
        $this->execute('SET foreign_key_checks = 0');
        /* 删除表 */
        $this->dropTable('{{%diandi_hub_level_condition}}');
        $this->execute('SET foreign_key_checks = 1;');
    }
}

