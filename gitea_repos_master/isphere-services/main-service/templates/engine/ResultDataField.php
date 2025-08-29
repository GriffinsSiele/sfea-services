<?php

class ResultDataField
{
    protected $type;
    protected $recursive;
    protected $name;
    protected $value;
    protected $title;
    protected $desc;

    public function __construct($type, $name, $value, $title = null, $desc = null)
    {
        $this->type = \strpos($type, ':') ? \substr($type, 0, \strpos($type, ':')) : $type;
        $this->recursive = (false !== \strpos($type, ':recursive'));
        $this->name = $name;
        $this->value = $value;
        $this->title = $title;
        $this->desc = $desc;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getRecursive()
    {
        return $this->recursive;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDesc()
    {
        return $this->desc;
    }
}
