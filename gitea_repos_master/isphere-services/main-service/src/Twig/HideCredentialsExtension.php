<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Request;
use App\Model\Response;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HideCredentialsExtension extends AbstractExtension
{
    public function getFilters(): iterable
    {
        yield new TwigFilter('hide_credentials', [$this, 'hideCredentials']);
    }

    public function hideCredentials(mixed $subject): mixed
    {
        $subject = clone $subject;

        if ($subject instanceof Request) {
            if (!empty($subject->getPassword())) {
                $subject->setPassword('***');
            }
        } elseif ($subject instanceof Response) {
            foreach ($subject->getRequests() ?? [] as $request) {
                if (!empty($request->getPassword())) {
                    $request->setPassword('***');
                }
            }
        }

        return $subject;
    }
}
