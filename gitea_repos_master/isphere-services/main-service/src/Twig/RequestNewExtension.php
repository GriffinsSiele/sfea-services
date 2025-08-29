<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\RequestNew;
use App\Model\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RequestNewExtension extends AbstractExtension implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly string $xmlpath,
    ) {
    }

    public function getFilters(): iterable
    {
        yield new TwigFilter('join_reqs', [$this, 'joinReqs']);
    }

    public function getFunctions(): iterable
    {
        yield new TwigFunction('get_request', [$this, 'getRequest']);
    }

    public function getRequest(RequestNew $requestNew): ?Request
    {
        $titles = $this->makeTitles($requestNew);
        $contents = $this->downloadFile($titles);

        if (null === $contents) {
            return null;
        }

        try {
            return $this->serializer->deserialize($contents, Request::class, XmlEncoder::FORMAT);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());

            return null;
        }
    }

    public function makeTitles(RequestNew $requestNew): array
    {
        $numName = \str_pad((string) $requestNew->getId(), 9, '0', \STR_PAD_LEFT);

        return \str_split($numName, 3);
    }

    public function joinReqs(iterable $reqs): iterable
    {
        foreach ($reqs as $req) {
            try {
                $getters = \array_filter(\get_class_methods($req), static fn ($method) => \str_starts_with($method, 'get'));
            } catch (\Throwable $e) {
                $this->logger->error('cannot get class methods on $req', [
                    'req' => $req,
                    'exception' => $e,
                ]);

                continue;
            }

            foreach ($getters as $getter) {
                $propertyPath = \str_replace('get', '', $getter);
                $value = $req->$getter();

                if (!empty($value)) {
                    if (!\is_scalar($value)) {
                        $this->logger->warning('Unsupported req value', [
                            'value' => $value,
                        ]);

                        continue;
                    }

                    yield $propertyPath => $value;
                }
            }
        }
    }

    public function downloadFile(array $titles, string $suffux = 'req'): ?string
    {
        $filename = $this->xmlpath.\implode('/', $titles).'_'.$suffux.'.xml';
        if (\file_exists($filename)) {
            return \file_get_contents($filename);
        }

        $filename = $this->xmlpath.\implode('/', [$titles[0], $titles[1]]).'.tar.gz';
        if (\file_exists($filename)) {
            return \shell_exec('tar xzfO '.$filename.' '.$titles[2].'_'.$suffux.'.xml');
        }

        return null;
    }
}
