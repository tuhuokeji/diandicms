<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-13 01:01:58
 * @Last Modified by:   Wang Chunsheng 2192138785@qq.com
 * @Last Modified time: 2020-03-13 03:29:00
 */

namespace addons\diandi_integral\models\enums;

use yii2mod\enum\helpers\BaseEnum;

class GoodsStatus extends BaseEnum
{
    // 上架
    const PUTAWAY = 0;
    // 下架
    const   OUT = 1;
    
    /**
     * @var string message category
     * You can set your own message category for translate the values in the $list property
     * Values in the $list property will be automatically translated in the function `listData()`
     */
    public static $messageCategory = 'App';

    /**
     * @var array
     */
    public static $list = [
        self::PUTAWAY => '上架',
        self::OUT => '下架',
    ];
}
