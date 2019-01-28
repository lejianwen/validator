<?php
/**
 * Created by PhpStorm.
 * User: Lejianwen
 * Date: 2019/1/24
 * Time: 9:44
 */

namespace Ljw\Validator;

class Validator
{
    protected $data = [];
    protected $rules = [];
    protected $messages = [];
    protected $fails = [];

    public function __construct($data, $rules, $messages)
    {
        $this->data = $data;
        $this->rules = $this->parseRules($rules);
        $this->messages = $messages;
    }

    public static function make($data, $rules, $messages)
    {

    }

    public function run()
    {
        foreach ($this->rules as $attribute => $rules) {
            //当前数据的校验规则
            foreach ($rules as $rule => $params) {
                $this->validateRule($attribute, $rule, $params);
            }
        }
    }

    /**
     * 解析规则
     * @param $rules
     * @return array
     * @author Lejianwen
     */
    protected function parseRules($rules)
    {
        $new_rules = [];
        foreach ($rules as $attribute => $rule_group) {
            //当前数据的校验规则
            $current_rules = explode('|', $rule_group);
            if (empty($current_rules)) {
                continue;
            }
            foreach ($current_rules as $rule) {
                if (strstr($rule, ':')) {
                    list($method, $params) = explode(':', $rule);
                    $params = explode(',', $params);
                } else {
                    $method = $rule;
                    $params = [];
                }
                $new_rules[$attribute][$method] = $params;
            }
        }
        return $new_rules;
    }

    /**
     * 获取规则的验证参数
     * @param $attribute
     * @param $rule
     * @return string
     * @author Lejianwen
     */
    protected function getRule($attribute, $rule)
    {
        return (isset($this->rules[$attribute]) && isset($this->rules[$attribute][$rule])) ? $this->rules[$attribute][$rule] : null;
    }

    protected function getSize($attribute, $value)
    {
        $has_numeric = ($this->getRule($attribute, 'int') !== null || $this->getRule($attribute, 'num') !== null);
        if (is_numeric($value) && $has_numeric) {
            return $value;
        }
        if (is_array($value)) {
            return count($value);
        }
        return mb_strlen($value);
    }

    protected function getValue($attribute)
    {
        return isset($this->data[$attribute]) ? $this->data[$attribute] : null;
    }

    /**
     * 执行校验一条规则
     * @param $attribute
     * @param $value
     * @param $rule
     * @author Lejianwen
     */
    protected function validateRule($attribute, $rule, $params = [])
    {
        //解析rule，根据下划线分割并首字母大写
        $rules = array_map(function ($r) {
            return ucfirst($r);
        }, explode('_', $rule));
        $validate_method = 'validate' . implode('', $rules);
        if (is_callable([$this, $validate_method])) {
            $value = $this->getValue($attribute);
            if (!$this->$validate_method($attribute, $value, $params)) {
                $this->addFailMessage($attribute, $rule, $params);
            }
        }
    }

    /**
     * 添加验证失败信息
     * @param $attribute
     * @param $rule
     * @param $params
     * @author Lejianwen
     */
    protected function addFailMessage($attribute, $rule, $params)
    {
        $message = "{$attribute} is invalid!";
        if (isset($this->messages["{$attribute}.{$rule}"])) {
            $message = $this->messages["{$attribute}.{$rule}"];
        } elseif (isset($this->messages["{$rule}"])) {
            $message = $this->messages["{$rule}"];
        } elseif (isset($this->messages["{$attribute}"])) {
            $message = $this->messages["{$attribute}"];
        }
        $value = $this->getValue($attribute);
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $message = str_replace([':attribute', ':value', ':params'], [$attribute, (string)$value, implode(',', $params)], $message);
        $this->fails[] = $message;
    }

    public function getFails()
    {
        return $this->fails;
    }

    public function validateRequired($attribute, $value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif (is_array($value) && count($value) < 1) {
            return false;
        }
        return true;
    }

    public function validateMax($attribute, $value, $params)
    {
        return $this->getSize($attribute, $value) <= $params[0];
    }

    public function validateMin($attribute, $value, $params)
    {
        return $this->getSize($attribute, $value) >= $params[0];
    }

    public function validateSize($attribute, $value, $params)
    {
        return $this->getSize($attribute, $value) == $params[0];
    }

    public function validateBetween($attribute, $value, $params)
    {
        $size = $this->getSize($attribute, $value);
        return $size >= $params[0] && $size <= $params[1];
    }

    public function validateNum($attribute, $value, $params)
    {
        return is_numeric($value);
    }

    public function validateInteger($attribute, $value, $params)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public function validateSame($attribute, $value, $params)
    {
        $other = $this->getValue($params[0]);
        return $value === $other;
    }

    public function validateRequiredWith($attribute, $value, $params)
    {
        $other = $this->getValue($params[0]);
        if ($other) {
            return $this->validateRequired($attribute, $value, '');
        }
        return true;
    }

    public function validateString($attribute, $value)
    {
        return is_string($value);
    }

    public function validateArray($attribute, $value)
    {
        return is_array($value);
    }

    public function validateDifferent($attribute, $value, $params)
    {
        foreach ($params as $param) {
            $other = $this->getValue($attribute);
            if (is_null($other) || $value === $other) {
                return false;
            }
        }
        return true;
    }

    public function validateConfirmed($attribute, $value, $params)
    {
        return $this->validateSame($attribute, $value, $params);
    }

    public function validateIp($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    public function validateIpv4($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function validateIpv6($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function validateJson($attribute, $value)
    {
        if (!is_scalar($value) && !method_exists($value, '__toString')) {
            return false;
        }
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function validateIn($attribute, $value, $params)
    {
        //二维数据直接false
        if (is_array($value) && $this->getRule($attribute, 'array') !== null) {
            foreach ($value as $element) {
                if (is_array($element)) {
                    return false;
                }
            }
            return count(array_diff($value, $params)) == 0;
        }
        return !is_array($value) && in_array((string)$value, $params);
    }

    public function validateNotIn($attribute, $value, $params)
    {
        return !$this->validateIn($attribute, $value, $params);
    }
}