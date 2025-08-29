<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelResponseEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        foreach (\headers_list() as $header) {
            $components = \explode(': ', $header, 2);

            if (2 !== \count($components)) {
                continue;
            }

            [$key, $value] = $components;

            $response->headers->set($key, $value);
        }

        if (!$response->headers->has('Content-Type')
            && $event->getRequest()->query->has('mode')
        ) {
            $contentType = match ($event->getRequest()->query->get('mode')) {
                'html' => 'text/html',
                'json' => 'application/json',
                'xml' => 'application/xml',
                default => null,
            };

            if (null !== $contentType) {
                $response->headers->set('Content-Type', $contentType);
            }
        }
    }
}
