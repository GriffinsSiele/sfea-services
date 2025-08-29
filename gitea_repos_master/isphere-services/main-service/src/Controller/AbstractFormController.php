<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SystemUser;
use App\Kernel;
use App\Model\Request as AppRequest;
use App\Model\Response as AppResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractFormController extends BaseController
{
    private Kernel $kernel;
    private SerializerInterface $serializer;
    private UrlGeneratorInterface $urlGenerator;
    private bool $debug;

    #[Required]
    public function setKernel(Kernel $kernel): void
    {
        $this->kernel = $kernel;
    }

    #[Required]
    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    #[Required]
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator): void
    {
        $this->urlGenerator = $urlGenerator;
    }

    #[Required]
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function __invoke(#[CurrentUser] SystemUser $systemUser, Request $request): Response
    {
        $form = $this->createForm($this->getFormClass());
        $form->add('_submit', SubmitType::class, [
            'label' => 'Найти',
        ]);

        $form->handleRequest($request);

        /** @var AppRequest $appRequest */
        $appRequest = null;

        /** @var AppResponse $appResponse */
        $appResponse = null;

        /** @var object $check */
        $check = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $check = $form->getData();

            $appRequest = $this->appRequestFactory($check)
                ->setRequestId(Uuid::v4()->toRfc4122())
                ->setUserIp($request->getClientIp())
                ->setUserId($systemUser->getUserIdentifier())
                ->setPassword($systemUser->getPassword())
                ->setRequestType($this->getName())
                ->setSources($check->getSources())
                ->setTimeout(60 * 5) // seconds
                ->setRecursive((int) $check->getRecursive())
                ->setAsync((int) $check->getAsync());

            $serialized = $this->serializer->serialize(
                $appRequest,
                XmlEncoder::FORMAT,
                [
                    AbstractObjectNormalizer::SKIP_NULL_VALUES,
                    XmlEncoder::ROOT_NODE_NAME => 'Request',
                    XmlEncoder::ENCODING => 'utf-8',
                    XmlEncoder::FORMAT_OUTPUT => $this->debug,
                ],
            );

            // skip_null_values не до конца чистит пустые значения
            $serialized = \preg_replace('~<\w+/>\s*~', '', $serialized);
            $subRequest = Request::create(
                $this->urlGenerator->generate(DefaultController::NAME),
                Request::METHOD_POST,
                server: [
                    'CONTENT_TYPE' => 'application/xml',
                ],
                content: $serialized,
            );

            $subRequest->setSession($request->getSession());

            $response = $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
            $appResponse = $this->serializer->deserialize($response->getContent(), AppResponse::class, XmlEncoder::FORMAT);
        }

        return $this->render(
            $this->getTemplateName(),
            [
                'title' => $this->getTemplateTitle(),
                'appRequest' => $appRequest,
                'appResponse' => $appResponse,
                'check' => $check,
                'form' => $form->createView(),
            ],
        );
    }

    protected function getTemplateName(): string
    {
        return 'check.html.twig';
    }

    abstract protected function getFormClass(): string;

    abstract protected function getName(): string;

    abstract protected function getTemplateTitle(): string;

    abstract protected function appRequestFactory(mixed $check): AppRequest;
}
