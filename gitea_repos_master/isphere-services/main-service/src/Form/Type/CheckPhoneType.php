<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\CheckPhone;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckPhoneType extends AbstractType
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
                    'data-imask-phone-regions' => \json_encode(['RU'], \JSON_THROW_ON_ERROR),
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
            'data_class' => CheckPhone::class,
        ]);
    }

    private function getCheckSources(): array
    {
        return [
            'gosuslugi_phone' => ['Госуслуги', 1, 0, 0],
            'rossvyaz' => ['Россвязь', 1, 1, 1],
            'hlr' => ['HLR', 1, 1, 0],
            //  'infobip'=>array('Infobip',1,1,0),
            'smsc' => ['SMSC', 1, 1, 1],
            //  'people'=>array('Соцсети',1,0,0),
            //  'beholder'=>array('Beholder',1,1,0),
            'vk' => ['VK', 1, 1, 0],
            'ok' => ['OK', 1, 1, 0],
            'mailru' => ['Mail.Ru', 1, 1, 1],
            'twitter' => ['Twitter', 1, 1, 0],
            'facebook' => ['Facebook', 1, 1, 0],
            'instagram' => ['Instagram', 1, 1, 1],
            //  'hh'=>array('HH',1,1,0),
            //  'commerce'=>array('Commerce',1,1,0),
            'announcement' => ['Объявления', 1, 1, 0],
            'boards' => ['Boards', 1, 1, 1],
            'skype' => ['Skype', 1, 1, 0],
            'google' => ['Google', 1, 1, 0],
            'google_name' => ['Google имя', 1, 1, 0],
            'googleplus' => ['Google+', 1, 1, 1],
            'whatsapp' => ['WhatsApp', 1, 1, 0],
            'telegram' => ['Telegram', 1, 1, 0],
            //  'telegramweb'=>array('Telegram',1,1,0),
            //  'icq'=>array('ICQ',1,1,0),
            'viber' => ['Viber', 1, 1, 1],
            'yamap' => ['Яндекс.Карты', 1, 1, 0],
            '2gis' => ['2ГИС', 1, 1, 0],
            'egrul' => ['ЕГРЮЛ', 1, 1, 1],
            'getcontactweb' => ['GetContact', 1, 1, 0],
            'getcontact' => ['GetContact', 1, 1, 0],
            'truecaller' => ['TrueCaller', 1, 1, 0],
            'emt' => ['EmobileTracker', 1, 1, 1],
            'callapp' => ['CallApp', 1, 1, 0],
            'simpler' => ['Simpler', 1, 1, 0],
            'numbuster' => ['NumBuster', 1, 1, 1],
            //  'numbusterapp'=>array('NumBuster',1,2,0),
            'names' => ['Имена', 1, 1, 0],
            'phones' => ['Телефоны', 1, 1, 1],
            //  'avinfo'=>array('AvInfo',1,1,0)),
            //  'phonenumber'=>array('PhoneNumber',1,1,0),
            //  'banks'=>array('Банки СБП',0,0,0),
            //  'tinkoff'=>array('Тинькофф',0,1,0),
            //  'alfabank'=>array('Альфа-Банк',0,1,0),
            //  'vtb'=>array('ВТБ',0,1,0),
            //  'openbank'=>array('Открытие',0,1,1),
            //  'psbank'=>array('Промсвязьбанк',0,1,0),
            //  'rosbank'=>array('Росбанк',0,1,0),
            //  'unicredit'=>array('Юникредит',0,1,0),
            //  'raiffeisen'=>array('Райффайзен',0,1,1),
            //  'sovcombank'=>array('Совкомбанк',0,1,0),
            //  'gazprombank'=>array('Газпромбанк',0,1,0),
            //  'mkb'=>array('МКБ',0,1,0),
            //  'rsb'=>array('Русский стандарт',0,1,1),
            //  'avangard'=>array('Авангард',0,1,0),
            //  'qiwibank'=>array('КИВИ Банк',0,1,0),
            //  'rnko'=>array('РНКО Платежный центр',0,1,1),
            //  'visa'=>array('VISA',0,1,0),
            //  'webmoney'=>array('WebMoney',1,1,0),
            //  'sber'=>array('Сбер Онлайн',0,0,0),
            //  'sbertest'=>array('Сбербанк тест',0,1,0),
            //  'sberbank'=>array('Сбербанк',0,1,1),
            //  'qiwi'=>array('Qiwi',1,1,0),
            //  'yamoney'=>array('Яндекс.Деньги',1,1,1),
            //  'elecsnet'=>array('Элекснет',1,1,1),
            'pochta' => ['Почта', 1, 1, 0],
            'aeroflot' => ['Аэрофлот', 1, 1, 0],
            //  'uralair'=>array('Уральские авиалинии',1,1,1),
            //  'biglion'=>array('Биглион',1,1,0),
            'papajohns' => ['Папа Джонс', 1, 1, 0],
            'avito' => ['Авито', 1, 1, 1],
            //  'rz'=>array('Реестр залогов',1,0,0),
            'reestrzalogov' => ['Реестр залогов', 1, 0, 0],
            'fssp' => ['ФССП', 1, 0, 0],
            'fssp_suspect' => ['ФССП розыск', 1, 0, 0],
            'gisgmp' => ['ГИС ГМП', 1, 0, 1],
            'bankrot' => ['Банкроты', 1, 0, 0],
            'terrorist' => ['Террористы', 1, 0, 0],
            'mvd' => ['МВД', 1, 0, 1],
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
