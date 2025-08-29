<?php

declare(strict_types=1);

namespace App\OpenTracing;

use Jaeger\Config;
use OpenTracing\GlobalTracer;

use const Jaeger\SAMPLER_TYPE_CONST;

class Jaeger
{
    public static function init(): void
    {
        $jaegerAgentHostPort = getenv('JAEGER_AGENT_HOST_PORT');

        if (!empty($jaegerAgentHostPort)) {
            [$reportingHost, $reportingPort] = explode(':', $jaegerAgentHostPort, 2);

            $jaeger = array(
                'sampler' => array(
                    'type' => SAMPLER_TYPE_CONST,
                    'param' => true,
                ),
                'logging' => true,
                'local_agent' => array(
                    'reporting_host' => $reportingHost,
                    'reporting_port' => $reportingPort,
                ),
            );

            $config = new Config($jaeger, 'main-service');
            $config->initializeTracer();

            $tracer = GlobalTracer::get();

            $pathinfo = null;
            $queryArgs = null;

            if (isset($_SERVER['REQUEST_URI'])) {
                $components = parse_url($_SERVER['REQUEST_URI']);

                if (isset($components['path'])) {
                    $pathinfo = $components['path'];
                }

                if (isset($components['query'])) {
                    parse_str($components['query'], $queryArgs);
                }
            }

            $scope = $tracer->startActiveSpan($pathinfo ?? '[unknown]');

            if ($pathinfo !== null) {
                $scope->getSpan()->setTag('path', $pathinfo);
            }

            if (is_array($queryArgs)) {
                $scope->getSpan()->log($queryArgs);
            }

            register_shutdown_function(function () use ($tracer, $scope) {
                $scope->close();
                $tracer->flush();
            });
        }
    }
}
