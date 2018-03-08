<?php

namespace mobile\modules\v2\models;
/**
 * @SWG\Definition(
 *   @SWG\Xml(name="ApiData")
 * )
 */
class ApiData
{

    /**
     * 业务代码
     * @SWG\Property(format="int32")
     * @var int
     */
    public $code;

    /**
     * @SWG\Property
     * @var object
     */
    public $info;

    /**
     * 业务提示
     * @SWG\Property
     * @var string
     */
    public $message;

    public function __construct($code=0,$message=''){
        $this->code = $code;
        $this->message = $message;
        $this->info=[];
    }
}
