<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class UploadBulkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Реестр для обработки',
                'help' => '(Excel или CSV не менее 100 строк и размером не более 30 Мб)',
                'constraints' => [
                    new File([
                        'maxSize' => '30M',
                        'mimeTypes' => [
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            'text/plain',
                        ],
                    ]),
                ],
            ])
            ->add('sources', SourceListType::class, [
                'label' => 'Источники',
                'required' => false,
                'attr' => [
                    'class' => 'inline',
                    'data-list-helper' => true,
                ],
                'check_sources' => $this->getCheckSources(),
                'constraints' => [
                    new NotNull(),
                    new Count([
                        'min' => 1,
                    ]),
                ],
            ]);
    }

    private function getCheckSources(): array
    {
        return [
            'fssp_suspect' => ['ФССП розыск', 1, 0, 1],
            'fms' => ['ФМС', 1, 0, 0],
            'fmsdb' => ['ФМС БД', 1, 0, 0],
            'mvd' => ['МВД', 1, 0, 1],
            'gosuslugi_passport' => ['Госуслуги паспорт', 1, 0, 0],
            'gosuslugi_inn' => ['Госуслуги ИНН', 1, 0, 1],
            'gosuslugi_phone' => ['Госуслуги телефон', 1, 0, 0],
            'gosuslugi_email' => ['Госуслуги e-mail', 1, 0, 1],
            'fns' => ['ФНС', 1, 0, 0],
            'gisgmp' => ['ГИС ГМП', 1, 0, 0],
            'cbr' => ['ЦБ РФ', 1, 0, 0],
            'terrorist' => ['Террористы', 1, 0, 1],
            'reestrzalogov' => ['Реестр залогов', 1, 0, 0],
            'eaisto' => ['ЕАИСТО', 1, 0, 0],
            'avtokod' => ['Автокод', 0, 0, 1],
            'gibdd_history' => ['ГИБДД история', 1, 0, 0],
            'gibdd_aiusdtp' => ['ГИБДД дтп', 1, 0, 1],
            'gibdd_restricted' => ['ГИБДД ограничения', 1, 0, 0],
            'gibdd_wanted' => ['ГИБДД розыск', 1, 0, 1],
            'gibdd_diagnostic' => ['ГИБДД техосмотр', 1, 0, 0],
            'gibdd_fines' => ['ГИБДД штрафы', 0, 0, 1],
            'gibdd_driver' => ['ГИБДД права', 1, 0, 0],
            'rsa_kbm' => ['РСА КБМ', 1, 0, 1],
            'rsa_policy' => ['РСА авто', 1, 0, 0],
            'rsa_bsostate' => ['РСА бланк', 1, 0, 1],
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
            'smsc' => ['SMSC', 1, 1, 1],
            'hh' => ['HH', 1, 1, 0],
            'announcement' => ['Объявления', 1, 1, 0],
            'boards' => ['Boards', 1, 1, 1],
            'skype' => ['Skype', 1, 1, 0],
            'apple' => ['Apple', 1, 1, 1],
            'google' => ['Google', 1, 1, 0],
            'googleplus' => ['Google+', 1, 1, 0],
            'whatsapp' => ['WhatsApp', 1, 1, 0],
            'telegram' => ['Telegram', 1, 1, 0],
            'viber' => ['Viber', 1, 1, 1],
            'yamap' => ['Яндекс.Карты', 1, 1, 0],
            '2gis' => ['2ГИС', 1, 1, 0],
            'egrul' => ['ЕГРЮЛ', 1, 1, 1],
            'kad' => ['Арбитражный суд', 1, 0, 0],
            'zakupki' => ['Госзакупки', 1, 0, 1],
            'getcontactweb' => ['GetContact', 1, 1, 0],
            'getcontact' => ['GetContact', 1, 1, 0],
            'truecaller' => ['TrueCaller', 1, 1, 0],
            'emt' => ['EmobileTracker', 1, 1, 1],
            'callapp' => ['CallApp', 1, 1, 0],
            'simpler' => ['Simpler', 1, 1, 0],
            'numbuster' => ['NumBuster', 1, 1, 1],
            'names' => ['Имена', 1, 1, 0],
            'phones' => ['Телефоны', 1, 1, 1],
            'rzd' => ['РЖД', 1, 1, 0],
            'aeroflot' => ['Аэрофлот', 1, 1, 1],
            'avito' => ['Авито', 1, 1, 1],
            'papajohns' => ['Папа Джонс', 1, 1, 0],
        ];
    }
}
