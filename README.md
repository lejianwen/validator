# validator
validate for request

~~~php
$data = [
    'username' => 'fff'
];
$rules = [
    'username' => 'required|requiredWith:nickname',
    'nickname' => 'max:2',
];
$messages = [
    'username.requiredWith' => 'username is required with nickname',
    'username.between'  => 'username <> between',
    'username.num'      => 'username is not num',
    'same'              => ':attribute is :value  ;same is  :params '
];
$validator = new \Ljw\Validator\Validator($data, $rules, $messages);
$validator->run();
var_dump($validator->getFails());
~~~
