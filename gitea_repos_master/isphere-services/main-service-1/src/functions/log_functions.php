<?php

declare(strict_types=1);

use App\Logger\Logger;

function log_debug($message, array $context = []): void
{
    Logger::getLogger()->debug($message, $context);
}

function log_info($message, array $context = []): void
{
    Logger::getLogger()->info($message, $context);
}

function log_notice($message, array $context = []): void
{
    Logger::getLogger()->notice($message, $context);
}

function log_warning($message, array $context = []): void
{
    Logger::getLogger()->warning($message, $context);
}

function log_error($message, array $context = []): void
{
    Logger::getLogger()->error($message, $context);
}

function log_critical($message, array $context = []): void
{
    Logger::getLogger()->critical($message, $context);
}

function log_emergency($message, array $context = []): void
{
    Logger::getLogger()->emergency($message, $context);
}
