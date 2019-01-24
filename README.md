# validator
validate for request

~~~php

//待验证的数据
$data = [
    'nickname' => 'aa',
    'username' => 'asdasd'
];
//验证规则
$rules = [
    'username' => 'requiredWith:nickname',
    'nickname' => 'max:2',
];
//验证不通过提示
$messages = [
    'username.requiredWith' => 'username is required with nickname',
    'username.between'      => 'username <> between',
    'username.num'          => 'username is not num',
    'same'                  => ':attribute is :value  ;same is  :params '
];
$validator = new \Ljw\Validator\Validator($data, $rules, $messages);
$validator->run();
var_dump($validator->getFails());

~~~
