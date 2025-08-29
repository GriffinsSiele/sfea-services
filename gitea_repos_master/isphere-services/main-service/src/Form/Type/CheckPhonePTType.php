<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\CheckPhonePT;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckPhonePTType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof SystemUser) {
            return;
        }

        $rowAttr = [
            'row_attr' => [
                'class' => 'form-floating mb-3',
            ],
        ];

        $builder
            ->add('mobilePhone', TextType::class, [
                'label' => 'Мобильный телефон',
                'attr' => [
                    'data-imask' => true,
                    'data-imask-phone' => true,
                    'data-imask-phone-regions' => \json_encode(['PT'], \JSON_THROW_ON_ERROR),
                    'placeholder' => 'Мобильный телефон',
                    'autofocus' => true,
                ],
                ...$rowAttr,
            ])
            ->add('sources', SourceListType::class, [
                'label' => 'Источники',
                'required' => false,
                'attr' => [
                    'class' => 'inline',
                    'data-list-helper' => true,
                ],
                'check_sources' => $this->getCheckSources(),
                'data' => $this->getDefaultCheckSources(),
            ])
            ->add('async', CheckboxType::class, [
                'label' => 'Подгружать информацию по мере получения',
                'required' => false,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'data' => true,
            ])
            ->add('format', FormatType::class, [
                'label' => 'Формат ответа',
                'attr' => [
                    'class' => 'inline',
                ],
                'data' => 'html',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CheckPhonePT::class,
        ]);
    }

    private function getCheckSources(): array
    {
        return [
            'hlr' => ['HLR', 1, 1, 0],
            //  'infobip'=>array('Infobip',1,1,0),
            'smsc' => ['SMSC', 1, 1, 1],
            'vk' => ['VK', 1, 1, 0],
            'ok' => ['OK', 1, 1, 0],
            'facebook' => ['Facebook', 1, 1, 0],
            'instagram' => ['Instagram', 1, 1, 1],
            'skype' => ['Skype', 1, 1, 0],
            'whatsapp' => ['WhatsApp', 1, 1, 0],
            'telegram' => ['Telegram', 1, 1, 0],
            //  'telegramweb'=>array('Telegram',1,1,0),
            'viber' => ['Viber', 1, 1, 1],
            'google' => ['Google', 1, 1, 0],
            'google_name' => ['Google имя', 1, 1, 0],
            'googleplus' => ['Google+', 1, 1, 1],
            'boards' => ['Boards', 1, 1, 0],
            'getcontactweb' => ['GetContact', 1, 1, 0],
            'getcontact' => ['GetContact', 1, 1, 0],
            'truecaller' => ['TrueCaller', 1, 1, 0],
            'emt' => ['EmobileTracker', 1, 1, 1],
            'callapp' => ['CallApp', 1, 1, 0],
            'simpler' => ['Simpler', 1, 1, 0],
            'numbuster' => ['NumBuster', 1, 1, 1],
        ];
    }

    private function getDefaultCheckSources(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof SystemUser) {
            return [];
        }

        return \array_keys(
            \array_filter(
                $this->getCheckSources(),
                static fn (array $v, string $k) => 1 === $v[1]
                    && $user->hasAccessSourceBySourceName($k),
                \ARRAY_FILTER_USE_BOTH,
            )
        );
    }
}
