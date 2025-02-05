<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-13 01:01:58
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-03-30 11:59:00
 */

namespace addons\diandi_integral\models\enums;

use yii2mod\enum\helpers\BaseEnum;

class ReceiptStatus extends BaseEnum
{

    // 未收货
    const NONPAYMENT = 10;
    // 已收货
    const ACCOUNTPAID = 20;
   
 
    
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
        self::NONPAYMENT => '未收货',
        self::ACCOUNTPAID => '已收货',

    ];
}
