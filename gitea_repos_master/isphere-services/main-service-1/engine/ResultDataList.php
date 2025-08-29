<?php

class ResultDataList
{
    protected $list;

    public function __construct()
    {
        $this->list = array();
    }

    public function addResult($result)
    {
        $this->list[]=$result;

        return $this;
    }

    public function getResultsCount()
    {
        return count($this->list);
    }

    public function getResults()
    {
        return $this->list;
    }
}

?>