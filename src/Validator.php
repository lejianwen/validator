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
    protected $register_functions = [];

    public function __construct($data, $rules, $messages, $register_functions = [])
    {
        $this->data = $data;
        $this->rules = $this->parseRules($rules);
        $this->messages = $messages;
        if (!empty($register_functions)) {
            $this->registerFunctions($register_functions);
        }
    }

    /**
     * make
     * @param $data
     * @param $rules
     * @param $messages
     * @param array $register_functions
     * @return Validator
     * @author Lejianwen
     */
    public static function make($data, $rules, $messages, $register_functions = [])
    {
        $validator = new Validator($data, $rules, $messages, $register_functions);
        return $validator->run();
    }

    public function run()
    {
        foreach ($this->rules as $attribute => $rules) {
            //当前数据的校验规则
            foreach ($rules as $rule => $params) {
                $this->validateRule($attribute, $rule, $params);
            }
        }
        return $this;
    }

    /**
     * 注册自定义方法
     * @param $functions
     * @author Lejianwen
     */
    public function registerFunctions($functions)
    {
        $this->register_functions = array_merge($this->register_functions, $functions);
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
        $rule_names = implode('', array_map(function ($r) {
            return ucfirst($r);
        }, explode('_', $rule)));
        $validate_method = 'validate' . $rule_names;
        $value = $this->getValue($attribute);
        if (is_callable([$this, $validate_method])) {
            if (!$this->$validate_method($attribute, $value, $params)) {
                $this->addFailMessage($attribute, $rule, $params);
            }
        } elseif (isset($this->register_functions[$rule]) && $this->register_functions[$rule] instanceof \Closure) {
            if (!$this->register_functions[$rule]($attribute, $value, $params)) {
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

    public function fistFail()
    {
        return empty($this->fails) ? '' : $this->fails[0];
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
        if (is_array($value)) {
            //二维数据直接false
            foreach ($value as $element) {
                if (is_array($element)) {
                    return false;
                }
            }
            return count(array_diff($value, $params)) == 0;
        } else {
            return in_array((string)$value, $params);
        }
    }

    public function validateNotIn($attribute, $value, $params)
    {
        return !$this->validateIn($attribute, $value, $params);
    }

    public function validateDate($attribute, $value)
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }
        if ((!is_string($value) && !is_numeric($value)) || strtotime($value) === false) {
            return false;
        }
        $date = date_parse($value);
        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * 验证日期格式
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     * @author Lejianwen
     */
    public function validateDateFormat($attribute, $value, $params)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }
        $format = $params[0];
        $date = \DateTime::createFromFormat('!' . $format, $value);
        return $date && $date->format($format) == $value;
    }

    public function validateEqual($attribute, $value, $params)
    {
        return $this->compareDates($attribute, $value, $params, '=');
    }

    public function validateBefore($attribute, $value, $params)
    {
        return $this->compareDates($attribute, $value, $params, '<');
    }

    public function validateBeforeOrEqual($attribute, $value, $params)
    {
        return $this->compareDates($attribute, $value, $params, '<=');
    }

    public function validateAfter($attribute, $value, $params)
    {
        return $this->compareDates($attribute, $value, $params, '>');
    }

    public function validateAfterOrEqual($attribute, $value, $params)
    {
        return $this->compareDates($attribute, $value, $params, '>=');
    }

    protected function compareDates($attribute, $value, $params, $operator)
    {
        if (!is_string($value) && !is_numeric($value) && !$value instanceof \DateTimeInterface) {
            return false;
        }
        $format = $this->getRule($attribute, 'date_format');
        if ($format !== null) {
            // 如果当前attribute规定了时间格式， 则根据时间格式来获取时间戳
            $first_timestamp = $this->getTimeStampByFormat($format[0], $value);
        } else {
            $first_timestamp = $this->getTimeStamp($value);
        }
        // 第二个比较的值可能是数据的字段名
        $second = $this->getValue($params[0]) ?: $params[0];
        if ($this->getValue($params[0]) && $this->getRule($params[0], 'date_format') !== null) {
            // 如果比较的参数是已存在的字段，则根据对应字段的时间格式获取时间戳
            $second_format = $this->getRule($params[0], 'date_format');
            $second_timestamp = $this->getTimeStampByFormat($second_format[0], $second);

        }/* elseif ($format !== null) {
            // 根据当前attribute的时间格式获取时间戳
            $second_timestamp = $this->getTimeStampByFormat($format[0], $second);
        } */ else {
            $second_timestamp = $this->getTimeStamp($second);
        }

        return $this->compare($first_timestamp, $second_timestamp, $operator);
    }

    protected function getTimeStamp($value)
    {
        return $value instanceof \DateTime ? $value->getTimestamp() : strtotime($value);
    }

    /**
     * 根据格式获取日期时间
     * @param $format
     * @param $value
     * @return \DateTime|null
     * @author Lejianwen
     */
    protected function getDateTimeByFormat($format, $value)
    {
        if ($date = \DateTime::createFromFormat('!' . $format, $value)) {
            return $date;
        }

        try {
            return new \DateTime($value);
        } catch (Exception $e) {
            //
        }
    }

    protected function getTimeStampByFormat($format, $value)
    {
        $date = $this->getDateTimeByFormat($format, $value);
        return $date ? $date->getTimestamp() : null;
    }

    protected function compare($first, $second, $operator)
    {
        switch ($operator) {
            case '<':
                return $first < $second;
            case '>':
                return $first > $second;
            case '<=':
                return $first <= $second;
            case '>=':
                return $first >= $second;
            case '=':
                return $first == $second;
            default:
                throw new InvalidArgumentException;
        }
    }

    public function validateEmail($attribute, $value, $params)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validateRegex($attribute, $value, $params)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }
        return preg_match($params[0], $value) > 0;
    }
}