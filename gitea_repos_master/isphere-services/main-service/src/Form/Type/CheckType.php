<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\Check;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckType extends AbstractType
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
            ->add('lastName', TextType::class, [
                'label' => 'Фамилия',
                'required' => false,
                'attr' => [
                    'autofocus' => true,
                    'placeholder' => 'Фамилия',
                ],
                ...$rowAttr,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Имя',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Имя',
                ],
                ...$rowAttr,
            ])
            ->add('patronymic', TextType::class, [
                'label' => 'Отчество',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Отчество',
                ],
                ...$rowAttr,
            ])
            ->add('date', BirthdayType::class, [
                'label' => 'Дата рождения',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Дата рождения',
                ],
                ...$rowAttr,
            ])
            ->add('passportSeries', TextType::class, [
                'label' => 'Серия паспорта',
                'required' => false,
                'attr' => [
                    'data-imask' => true,
                    'data-imask-mask' => '00 00',
                    'placeholder' => 'Серия паспорта',
                ],
                ...$rowAttr,
            ])
            ->add('passportNumber', TextType::class, [
                'label' => 'Номер паспорта',
                'required' => false,
                'attr' => [
                    'data-imask' => true,
                    'data-imask-range-min' => 10000,
                    'data-imask-range-max' => 999999,
                    'placeholder' => 'Номер паспорта',
                ],
                ...$rowAttr,
            ])
            ->add('issueDate', DateType::class, [
                'label' => 'Дата выдачи паспорта',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Дата выдачи паспорта',
                ],
                ...$rowAttr,
            ])
            ->add('inn', TextType::class, [
                'label' => 'ИНН',
                'required' => false,
                'attr' => [
                    'data-imask' => true,
                    'data-imask-mask' => '000000000000',
                    'placeholder' => 'ИНН',
                ],
                ...$rowAttr,
            ])
            ->add('snils', TextType::class, [
                'label' => 'СНИЛС',
                'required' => false,
                'attr' => [
                    'data-imask' => true,
                    'data-imask-mask' => '000-000-000 00',
                    'placeholder' => 'СНИЛС',
                ],
                ...$rowAttr,
            ])
            ->add('driverNumber', TextType::class, [
                'label' => 'Номер в/у',
                'required' => false,
                'attr' => [
                    'data-imask' => true,
                    'data-imask-mask' => '** ** 000000',
                    'placeholder' => 'Номер в/у',
                ],
                ...$rowAttr,
            ])
            ->add('driverDate', DateType::class, [
                'label' => 'Дата выдачи в/у',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Дата выдачи в/у',
                ],
                ...$rowAttr,
            ])
            ->add('mobilePhone', TextType::class, [
                'label' => 'Мобильный телефон',
                'required' => false,
                'attr' => [
                    'data-imask' => true,
                    'data-imask-mask' => '+{7} (000) 000-00-00',
                    'placeholder' => 'Мобильный телефон',
                ],
                ...$rowAttr,
            ])
            ->add('homePhone', TextType::class, [
                'label' => 'Домашний телефон',
                'required' => false,
                'attr' => [
                    'data-imask' => true,
                    'data-imask-mask' => '+{7} (000) 000-00-00',
                    'placeholder' => 'Домашний телефон',
                ],
                ...$rowAttr,
            ])
            ->add('workPhone', TextType::class, [
                'label' => 'Рабочий телефон',
                'required' => false,
                'attr' => [
                    'data-imask' => true,
                    'data-imask-mask' => '+{7} (000) 000-00-00',
                    'placeholder' => 'Рабочий телефон',
                ],
                ...$rowAttr,
            ])
            ->add('additionalPhone', TextType::class, [
                'label' => 'Дополнительный телефон',
                'required' => false,
                'attr' => [
                    'data-imask' => true,
                    'data-imask-mask' => '+{7} (000) 000-00-00',
                    'placeholder' => 'Дополнительный телефон',
                ],
                ...$rowAttr,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Email',
                ],
                ...$rowAttr,
            ])
            ->add('additionalEmail', EmailType::class, [
                'label' => 'Дополнительный Email',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Дополнительный Email',
                ],
                ...$rowAttr,
            ])
            ->add('regionId', RussianRegionType::class, [
                'label' => 'Регион',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Регион',
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
            ]);

        if ($user->getAccessRules()?->count() > 0) {
            $builder->add('rules', RuleListType::class, [
                'label' => 'Правила',
                'required' => false,
                'attr' => [
                    'data-list-helper' => true,
                ],
                'check_rules' => $this->getCheckRules(),
                'data' => $this->getDefaultCheckRules(),
            ]);
        }

        $builder
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
            'data_class' => Check::class,
        ]);
    }

    private function getCheckSources(): array
    {
        return [
            // Источники (название,выбран,рекурсивный,конец строки)
            'fssp' => ['ФССП', 1, 0, 0],
            // 'fsspapi'=>array('ФССП (API)',1,0,0),
            // 'fsspsite'=>array('ФССП (сайт)',1,0,1),
            'fssp_suspect' => ['ФССП розыск', 1, 0, 1],
            'fms' => ['ФМС', 1, 0, 0],
            'fmsd1b' => ['ФМС БД', 1, 0, 0],
            'mvd' => ['МВД', 1, 0, 1],
            // 'gosuslugi'=>array('Госуслуги',1,0,0),
            'gosuslugi_passport' => ['Госуслуги паспорт', 1, 0, 0],
            'gosuslugi_snils' => ['Госуслуги СНИЛС', 1, 0, 0],
            'gosuslugi_inn' => ['Госуслуги ИНН', 1, 0, 1],
            'gosuslugi_phone' => ['Госуслуги телефон', 1, 0, 0],
            'gosuslugi_email' => ['Госуслуги e-mail', 1, 0, 1],
            'fns' => ['ФНС', 1, 0, 0],
            // 'fns_inn'=>array('ФНС ИНН',1,0,0),
            'gisgmp' => ['ГИС ГМП', 1, 0, 0],
            'notariat' => ['Нотариат', 1, 0, 1],
            'bankrot' => ['Банкроты', 1, 0, 0],
            'cbr' => ['ЦБ РФ', 1, 0, 0],
            'terrorist' => ['Террористы', 1, 0, 1],
            // 'rz'=>array('Реестр залогов',1,0,0),
            'reestrzalogov' => ['Реестр залогов', 1, 0, 0],
            // 'avtokod'=>array('Автокод',0,0,0),
            'rsa_kbm' => ['РСА КБМ', 0, 0, 1],
            'gibdd_fines' => ['ГИБДД штрафы', 0, 0, 0],
            'gibdd_driver' => ['ГИБДД права', 0, 0, 1],
            // 'people'=>array('Соцсети',1,0,0),
            // 'beholder'=>array('Beholder',1,1,0),
            'vk' => ['VK', 1, 1, 0],
            'vk_person' => ['VK', 1, 1, 0],
            'ok' => ['OK', 1, 1, 0],
            'ok_person' => ['OK', 1, 1, 0],
            'mailru' => ['Mail.Ru', 1, 1, 1],
            'twitter' => ['Twitter', 1, 1, 0],
            'facebook' => ['Facebook', 1, 1, 0],
            'instagram' => ['Instagram', 1, 1, 1],
            'rossvyaz' => ['Россвязь', 1, 1, 0],
            'hlr' => ['HLR', 1, 1, 0],
            // 'infobip'=>array('Infobip',1,1,0),
            'smsc' => ['SMSC', 1, 1, 1],
            'hh' => ['HH', 1, 1, 0],
            // 'commerce'=>array('Commerce',1,1,0),
            'announcement' => ['Объявления', 1, 1, 0],
            'boards' => ['Boards', 1, 1, 1],
            'skype' => ['Skype', 1, 1, 0],
            'google' => ['Google', 1, 1, 0],
            'google_name' => ['Google имя', 1, 1, 0],
            'googleplus' => ['Google+', 1, 1, 0],
            'apple' => ['Apple', 1, 1, 1],
            'whatsapp' => ['WhatsApp', 1, 1, 0],
            'telegram' => ['Telegram', 1, 1, 0],
            // 'telegramweb'=>array('Telegram',1,1,0),
            // 'icq'=>array('ICQ',1,1,0),
            'viber' => ['Viber', 1, 1, 1],
            'yamap' => ['Яндекс.Карты', 1, 1, 0],
            '2gis' => ['2ГИС', 1, 1, 0],
            'egrul' => ['ЕГРЮЛ', 1, 1, 1],
            // 'kad'=>array('Арбитражный суд',1,0,0),
            'zakupki' => ['Госзакупки', 1, 0, 1],
            'getcontactweb' => ['GetContact', 1, 1, 0],
            'getcontact' => ['GetContact', 1, 1, 0],
            'truecaller' => ['TrueCaller', 1, 1, 0],
            'emt' => ['EmobileTracker', 1, 1, 1],
            'callapp' => ['CallApp', 1, 1, 0],
            'simpler' => ['Simpler', 1, 1, 0],
            'numbuster' => ['NumBuster', 1, 1, 1],
            // 'numbusterapp'=>array('NumBuster',1,2,0),
            'names' => ['Имена', 1, 1, 0],
            'phones' => ['Телефоны', 1, 1, 1],
            // 'avinfo'=>array('AvInfo',1,1,0)),
            // 'phonenumber'=>array('PhoneNumber',1,1,0),
            // 'banks'=>array('Банки',0,0,0),
            // 'tinkoff'=>array('Тинькофф',0,1,0),
            // 'alfabank'=>array('Альфа-Банк',0,1,0),
            // 'vtb'=>array('ВТБ',0,1,0),
            // 'openbank'=>array('Открытие',0,1,1),
            // 'psbank'=>array('Промсвязьбанк',0,1,0),
            // 'rosbank'=>array('Росбанк',0,1,0),
            // 'unicredit'=>array('Юникредит',0,1,0),
            // 'raiffeisen'=>array('Райффайзен',0,1,1),
            // 'sovcombank'=>array('Совкомбанк',0,1,0),
            // 'gazprombank'=>array('Газпромбанк',0,1,0),
            // 'mkb'=>array('МКБ',0,1,0),
            // 'rsb'=>array('Русский стандарт',0,1,1),
            // 'avangard'=>array('Авангард',0,1,0),
            // 'qiwibank'=>array('КИВИ Банк',0,1,0),
            // 'rnko'=>array('РНКО Платежный центр',0,1,1),
            // 'visa'=>array('VISA',1,1,0),
            // 'webmoney'=>array('WebMoney',1,1,0),
            // 'sber'=>array('Сбер Онлайн',0,0,0),
            // 'sbertest'=>array('Сбербанк тест',0,1,0),
            // 'sberbank'=>array('Сбербанк',0,1,1),
            // 'qiwi'=>array('Qiwi',1,1,0),
            // 'yamoney'=>array('Яндекс.Деньги',1,1,1),
            // 'elecsnet'=>array('Элекснет',1,1,1),
            'pochta' => ['Почта', 1, 1, 0],
            'rzd' => ['РЖД', 1, 1, 0],
            'aeroflot' => ['Аэрофлот', 1, 1, 1],
            // 'uralair'=>array('Уральские авиалинии ',1,1,1),
            // 'biglion'=>array('Биглион',1,1,0),
            'papajohns' => ['Папа Джонс', 1, 1, 0],
            'avito' => ['Авито', 1, 1, 1],
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

    private function getCheckRules(): array
    {
        return [
            // Правила (название,выбран,конец строки)
            'fms_passport_decline_not_valid' => ['Отказ при недействительном паспорте', 1, 1],
            'fns_inn_approve_found' => ['Одобрение при найденном ИНН', 1, 1],
            'vk_person_approve_found' => ['Одобрение при найденном профиле VK', 1, 1],
            'ok_person_approve_found' => ['Одобрение при найденном профиле OK', 1, 1],
            'fssp_person_approve_found' => ['Одобрение при найденном ИП в ФССП', 1, 1],
            'decline_other' => ['Отказ в остальных случаях', 1, 1],
        ];
    }

    private function getDefaultCheckRules(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof SystemUser) {
            return [];
        }

        return \array_keys(
            \array_filter(
                $this->getCheckRules(),
                static fn (array $v, string $k) => 1 === $v[1]
                    && $user->hasAccessRuleByRuleName($v[0]),
                \ARRAY_FILTER_USE_BOTH,
            )
        );
    }
}
