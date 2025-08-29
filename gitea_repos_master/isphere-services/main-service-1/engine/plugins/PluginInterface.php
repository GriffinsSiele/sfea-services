<?php

interface PluginInterface
{
    public function getName();
    public function getTitle();

    public function prepareRequest(&$rContext);

    public function computeRequest(&$rContext);
}

?>