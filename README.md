# 一个简单的验证工具

## 安装

~~~
composer require "ljw/validator": "dev-master"
~~~

### 示例： 

***还在开发中，慢慢完善***

~~~php

//待验证的数据
$data = [
    'nickname' => 'aa',
    'username' => ['a', 'b'],
    'date_1'   => date('d-m-Y'),
    'date_2'   => '20190129',
    'test_zdy' => 'abc'
];
//验证规则
$rules = [
    'username' => 'in:a,b,c',
    'nickname' => 'max:2',
    'date_1'   => 'date|date_format:d-m-Y',
    'date_2'   => 'equal:date_1',
    'test_zdy' => 'zdy:a,b'
];
//验证不通过提示
$messages = [
    'username.required_with' => 'username is required with nickname',
    'username.between'       => 'username <> between',
    'username.in'            => 'username is not in :params',
    'username.array'         => 'username is not array',
    'same'                   => ':attribute is :value  ;same is  :params ',
    'date_1.date_format'     => ' date1 date_format is error',
    'date_2.date_format'     => 'date2 date_format is error',
    'date_1.date'            => ' date1  is error',
    'date_2.date'            => 'date2  is error',
    'date_2.after'           => 'date2  is after error',
    'test_zdy.zdy'           => 'zdy error'
];
$register_functions = [
    'zdy' => function ($attribute, $value, $params) {
        var_dump($attribute, $value, $params);
        return false;
    }
];
//$validator = new \Ljw\Validator\Validator($data, $rules, $messages, $register_functions);
//$validator->run();
//var_dump($validator->getFails());


$v = \Ljw\Validator\Validator::make($data, $rules, $messages, $register_functions);
var_dump($v->fistFail());


~~~
