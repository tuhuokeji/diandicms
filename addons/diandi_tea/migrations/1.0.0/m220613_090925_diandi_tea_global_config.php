<?php

use yii\db\Migration;

class m220613_090925_diandi_tea_global_config extends Migration
{
    public function up()
    {
        /* 取消外键约束 */
        $this->execute('SET foreign_key_checks = 0');
        
        /* 创建表 */
        $this->createTable('{{%diandi_tea_global_config}}', [
            'id' => "int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '人脸招聘'",
            'bloc_id' => "int(11) NOT NULL COMMENT '人脸库组id'",
            'store_id' => "int(11) NOT NULL",
            'create_time' => "datetime NULL",
            'update_time' => "datetime NULL",
            'mumber_scale' => "varchar(255) NOT NULL COMMENT '会员积分比例'",
            'vip_scale' => "varchar(255) NOT NULL COMMENT 'vip积分比例'",
            'store_introduce' => "text NULL COMMENT '商户简介'",
            'PRIMARY KEY (`id`)'
        ], "ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='全局配置表'");
        
        /* 索引设置 */
        
        
        /* 表数据 */
        $this->insert('{{%diandi_tea_global_config}}',['id'=>'2','bloc_id'=>'30','store_id'=>'79','create_time'=>'2022-03-22 10:23:26','update_time'=>'2022-03-22 10:23:26','mumber_scale'=>'3','vip_scale'=>'5','store_introduce'=>'共享茶室，为您提供惬意的空间与服务，欢迎您的到来！']);
        
        /* 设置外键约束 */
        $this->execute('SET foreign_key_checks = 1;');
    }

    public function down()
    {
        $this->execute('SET foreign_key_checks = 0');
        /* 删除表 */
        $this->dropTable('{{%diandi_tea_global_config}}');
        $this->execute('SET foreign_key_checks = 1;');
    }
}

