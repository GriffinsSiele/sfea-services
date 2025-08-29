<?php

class RequestContext_new
{
    protected $checktype;
    protected $parent;
    protected $path;
    protected $start;
    protected $param;
    protected $id;
    protected $level;
    protected $ch;
    protected $plugin;
    protected $initData;
    protected $swapData;
    protected $finished;
    protected $suspendTill;
    protected $error;
    protected $errortype;
    protected $prepared;
    protected $starttime;
    protected $endtime;

    public function __construct($source, $checktype, $requestid, $path, $start, $param, $level, $plugin_code, $initData)
    {
        global $plugin_interface;

        if (!isset($plugin_interface[$plugin_code])) {
            if (\file_exists(__DIR__.'/plugins/'.$plugin_code.'_new.php')) {
                require_once __DIR__.'/plugins/'.$plugin_code.'_new.php';
            } elseif (\file_exists(__DIR__.'/plugins/'.$plugin_code.'.php')) {
                require_once __DIR__.'/plugins/'.$plugin_code.'.php';
            }
            if (\class_exists($plugin_code)) {
                $plugin_interface[$plugin_code] = new $plugin_code();
            }
        }

        $this->source = $source;
        $this->checktype = $checktype;
        $this->level = $level;
        $this->start = $start ?: $param;
        $this->param = $param;
        $this->parent = $path;
        $this->path = ($path ? $path.'/' : '').$param;
        $this->id = $checktype.'_'.\strtr($param, ['[' => '', ']' => '']).'_'.$level; // $checktype.'_'.$requestid.'_'.$level;
        $this->ch = null;
        $this->plugin = isset($plugin_interface[$plugin_code]) ? $plugin_interface[$plugin_code] : null;
        $this->initData = \array_merge(['checktype' => $checktype], $initData);

        $this->swapData = [];
        $this->resultData = [];

        $this->finished = !isset($plugin_interface[$plugin_code]);
        $this->error = !isset($plugin_interface[$plugin_code]) ? 'Ошибка подключения источника' : null;

        $this->suspendTill = 0;
        $this->starttime = \microtime(true);
        $this->endtime = $this->starttime;
    }

    public function setCurlHandler($ch)
    {
        $this->ch = $ch;

        return $this;
    }

    public function initCurlHandler()
    {
        global $reqtime, $http_connecttimeout, $http_timeout, $http_agent;

        $ch = \curl_init();

        // curl_setopt($ch,CURLOPT_VERBOSE, 1);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        //        curl_setopt($ch,CURLOPT_SSLVERSION,3);
        //        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        //        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        \curl_setopt($ch, \CURLOPT_IPRESOLVE, \CURL_IPRESOLVE_V4);

        \curl_setopt($ch, \CURLOPT_USERAGENT, $http_agent);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 1);
        \curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, $http_connecttimeout);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, $http_timeout);

        $this->setCurlHandler($ch);

        return $ch;
    }

    public function isReady()
    {
        return \microtime(true) >= $this->suspendTill;
    }

    public function setSleep($sec)
    {
        $this->suspendTill = \microtime(true) + $sec;

        return $this;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getCheckType()
    {
        return $this->checktype;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getParam()
    {
        return $this->param;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCurlHandler()
    {
        return $this->ch;
    }

    public function getPlugin()
    {
        return $this->plugin;
    }

    public function getSourceName()
    {
        return isset($this->plugin) ? $this->plugin->getName($this->checktype) : '';
    }

    public function getSourceTitle()
    {
        return isset($this->plugin) ? $this->plugin->getTitle() : '';
    }

/*
    public function getCheckName()
    {
        return isset($this->plugin)?$this->plugin->getName($this->checktype):'';
    }
*/
    public function getCheckTitle()
    {
        return isset($this->plugin) ? $this->plugin->getTitle($this->checktype) : '';
    }

    public function getInitData()
    {
        return $this->initData;
    }

    public function setResultData($resultData)
    {
        $this->resultData = $resultData;

        return $this;
    }

    public function getResultData()
    {
        return $this->resultData;
    }

    public function setSwapData($swapData)
    {
        $this->swapData = $swapData;

        return $this;
    }

    public function getSwapData()
    {
        return $this->swapData;
    }

    public function setFinished(): void
    {
        $this->finished = true;
        $this->endtime = \microtime(true);
    }

    public function getError()
    {
        return $this->error;
    }

    public function getErrorType()
    {
        return $this->errortype;
    }

    public function setError($error, $errortype = 500): void
    {
        $this->error = $error;
        //        $this->error .= $error.'#';
        $this->errortype = $errortype;
    }

    public function isFinished()
    {
        return $this->finished;
    }

    public function processTime()
    {
        return \round(($this->finished ? $this->endtime : \microtime(true)) - $this->starttime, 2);
    }

    public function startTime()
    {
        return $this->starttime;
    }

    public function endTime()
    {
        return $this->endtime;
    }
}
