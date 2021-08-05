<?php

namespace Tower\Validation;

use Tower\DB;
use DateTime;

trait Rules
{
    protected function required(array $parameters): void
    {
        if (! $this->request->input($parameters['ruleName']) || empty($this->request->input($parameters['ruleName'])) || is_null($this->request->input($parameters['ruleName'])))
            array_push($this->messages , $parameters['message']);
    }

    protected function string(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && !is_string($this->request->input($parameters['ruleName'])))
            array_push($this->messages , $parameters['message']);
    }

    protected function integer(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && !is_int($this->request->input($parameters['ruleName'])))
            array_push($this->messages , $parameters['message']);
    }

    protected function boolean(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && !is_bool($this->request->input($parameters['ruleName'])))
            array_push($this->messages , $parameters['message']);
    }

    protected function array(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && !is_array($this->request->input($parameters['ruleName'])))
            array_push($this->messages , $parameters['message']);
    }

    protected function email(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && false === filter_var($this->request->input($parameters['ruleName']), FILTER_VALIDATE_EMAIL))
            array_push($this->messages , $parameters['message']);
    }

    protected function regex(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && !preg_match($parameters['value'], $this->request->input($parameters['ruleName'])))
            array_push($this->messages , $parameters['message']);
    }

    protected function max(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && is_string($this->request->input($parameters['ruleName'])) && $this->strlen($this->request->input($parameters['ruleName'])) > $parameters['value'])
            array_push($this->messages , $parameters['message']);

        if ($this->request->input($parameters['ruleName']) && is_int($this->request->input($parameters['ruleName'])) && $this->request->input($parameters['ruleName']) > $parameters['value'])
            array_push($this->messages , $parameters['message']);
    }

    protected function min(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && is_string($this->request->input($parameters['ruleName'])) && $this->strlen($this->request->input($parameters['ruleName'])) < $parameters['value'])
            array_push($this->messages , $parameters['message']);

        if ($this->request->input($parameters['ruleName']) && is_int($this->request->input($parameters['ruleName'])) && $this->request->input($parameters['ruleName']) < $parameters['value'])
            array_push($this->messages , $parameters['message']);
    }

    protected function size(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && is_string($this->request->input($parameters['ruleName'])) && $this->strlen($this->request->input($parameters['ruleName'])) != $parameters['value'])
            array_push($this->messages , $parameters['message']);

        if ($this->request->input($parameters['ruleName']) && is_int($this->request->input($parameters['ruleName'])) && $this->request->input($parameters['ruleName']) != $parameters['value'])
            array_push($this->messages , $parameters['message']);
    }

    protected function date(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && ! $this->validateDate($this->request->input($parameters['ruleName']) , is_null($parameters['value']) ? 'Y-m-d H:i:s' : $parameters['value']))
            array_push($this->messages , $parameters['message']);
    }

    protected function after(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && $this->request->input($parameters['value']) && $this->request->input($parameters['ruleName']) < $this->request->input($parameters['value']))
            array_push($this->messages , $parameters['message']);
    }

    protected function before(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName']) && $this->request->input($parameters['value']) && $this->request->input($parameters['ruleName']) > $this->request->input($parameters['value']))
            array_push($this->messages , $parameters['message']);
    }

    protected function validateDate($date, $format = 'Y-m-d H:i:s'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    protected function in(array $parameters): void
    {
        $array = explode(',' , $parameters['value']);

        if ($this->request->input($parameters['ruleName'])  && !in_array($this->request->input($parameters['ruleName']) , $array))
            array_push($this->messages , $parameters['message']);
    }

    protected function nationalCode(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName'])){
            if(! preg_match('/^[0-9]{10}$/' , $this->request->input($parameters['ruleName']))){
                array_push($this->messages , $parameters['message']);
                return;
            }

            for($i = 0; $i < 10; $i++)
                if(preg_match('/^'.$i.'{10}$/' , $this->request->input($parameters['ruleName']))){
                    array_push($this->messages , $parameters['message']);
                    return;
                }

            for($i = 0, $sum = 0; $i < 9; $i++)
                $sum += ((10-$i) * intval(substr($this->request->input($parameters['ruleName']) , $i ,1)));

            $ret = $sum % 11;

            $parity = intval(substr($this->request->input($parameters['ruleName']), 9,1));

            if(($ret < 2 && $ret == $parity) || ($ret >= 2 && $ret == 11 - $parity))
                return;

            array_push($this->messages , $parameters['message']);
        }
    }

    private function checkDB(array $parameters): bool
    {
        if (str_contains($parameters['value'] , ','))
            $table = explode(',' , $parameters['value']);

        if (isset($table))
            $check = DB::table($table[0])->where($table[1] , $this->request->input($parameters['ruleName']))->exists();
        else
            $check = DB::table($parameters['value'])->where('id' , $this->request->input($parameters['ruleName']))->exists();

        return $check;
    }

    protected function exists(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName'])){
            $check = $this->checkDB($parameters);

            if (! $check)
                array_push($this->messages , $parameters['message']);
        }
    }

    protected function unique(array $parameters): void
    {
        if ($this->request->input($parameters['ruleName'])){
            $check = $this->checkDB($parameters);

            if ($check)
                array_push($this->messages , $parameters['message']);
        }
    }


    protected function strlen($value): bool|int
    {
        if (!function_exists('mb_detect_encoding'))
            return strlen($value);

        if (false === $encoding = mb_detect_encoding($value))
            return strlen($value);

        return mb_strlen($value, $encoding);
    }
}
