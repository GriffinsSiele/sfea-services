<?php

interface PluginInterface
{
    public function getName();

    public function getTitle();

    public function prepareRequest(array $params, &$rContext);

    public function computeRequest(array $params, &$rContext);
}
