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
    'username' => 'asdasd'
];
//验证规则
$rules = [
    'username' => 'required_with:nickname',
    'nickname' => 'max:2',
];
//验证不通过提示
$messages = [
    'username.required_with' => 'username is required with nickname',
    'username.between'      => 'username <> between',
    'username.num'          => 'username is not num',
    'same'                  => ':attribute is :value  ;same is  :params '
];
$validator = new \Ljw\Validator\Validator($data, $rules, $messages);
$validator->run();
var_dump($validator->getFails());

~~~
