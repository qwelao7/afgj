<?php

namespace mobile\modules\v2\models;

/**
 * @SWG\Definition(
 *   @SWG\Xml(name="##default")
 * )
 */
class ApiResponse
{

    /**
     * 状态码
     * @SWG\Property(format="int32")
     * @var int
     */
    public $ret;

    /**
     * 业务数据
     * @var ApiData
     * @SWG\Property()
     */
    public $data;

    /**
     * 错误信息
     * @SWG\Property
     * @var string
     */
    public $error;

    public function __construct($ret=200,$error=''){
        $this->ret = $ret;
        $this->error = $error;
    }
}
