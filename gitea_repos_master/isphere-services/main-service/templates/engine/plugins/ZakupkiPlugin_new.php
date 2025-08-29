<?php

class ZakupkiPlugin_new implements PluginInterface
{
    private $names = [
                           'Номер реестровой записи контракта' => ['contract_id', 'Реестровый номер контракта', 'Реестровый номер контракта'],
                           'Дата последнего изменения записи' => ['last_change_time'],
                           'Заказчик: наименование' => ['customer_name', 'Заказчик', 'Заказчик'],
                           'Заказчик: ИНН' => ['customer_inn', 'ИНН заказчика', 'ИНН заказчика'],
                           'Заказчик: КПП' => ['customer_kpp', 'КПП заказчика', 'КПП заказчика'],
                           'Уровень бюджета' => ['budget_level'],
                           'Источник финансирования контракта: наименование бюджета' => ['budget_name', 'Бюджетный источник финансирования', 'Бюджетный источник финансирования'],
                           'Источник финансирования контракта: наименование/вид внебюджетных средств' => ['extra_budget', 'Внебюджетный источник финансирования', 'Внебюджетный источник финансирования'],
                           'Способ размещения заказа' => ['order_placement'],
                           'Номер извещения о проведени торгов' => ['order_number', 'Номер закупки', 'Номер закупки'],
                           'Дата подведения результатов определения поставщика (подрядчика, исполнителя)' => ['selection_date', 'Дата подведения результатов', 'Дата подведения результатов'],
//                           'Реквизиты документа, подтверждающего основание заключения контракта' => array('selection_doc'),
                           'Контракт: дата' => ['contract_date', 'Дата контракта', 'Дата контракта'],
                           'Контракт: номер' => ['contract_number', 'Номер контракта', 'Номер контракта'],
                           'Код бюджетной классификации' => ['budget_code'],
                           'Предмет контракта' => ['subject', 'Предмет контракта', 'Предмет контракта'],
                           'Цена контракта' => ['subject_total', 'Сумма контракта', 'Сумма контракта', 'float'],
//                           'Идентификационный код закупки (ИКЗ)' => array('ikz','ИКЗ','Идентификационный код закупки'),
//                           'Объект закупки: наименование товаров, работ, услуг' => array('item_name','Наименование объекта закупки','Наименование объекта закупки'),
//                           'Объект закупки: код позиции' => array('item_code','Код объекта закупки','Код объекта закупки'),
//                           'Объект закупки: цена за единицу, рублей' => array('item_price','Цена объекта закупки','Цена объекта закупки','float'),
//                           'Объект закупки: количество' => array('item_amount','Количество объекта закупки','Количество объекта закупки'),
//                           'Объект закупки: сумма, рублей' => array('item_total','Сумма объекта закупки','Сумма объекта закупки','float'),
                           'Информация о поставщиках (исполнителях, подрядчиках) по контракту: наименование юридического лица (ф.и.о. физического лица)' => ['supplier_name', 'Наименование поставщика', 'Наименование поставщика'],
                           'Информация о поставщиках (исполнителях, подрядчиках) по контракту: ИНН' => ['supplier_inn', 'ИНН поставщика', 'ИНН поставщика'],
                           'Информация о поставщиках (исполнителях, подрядчиках) по контракту: КПП' => ['supplier_kpp', 'КПП поставщика', 'КПП поставщика'],
                           '' => [''],
                           'Закупки по' => ['order_law'],
                           'Реестровый номер закупки' => ['order_id', 'Номер закупки', 'Номер закупки'],
                           'Способ определения поставщика, подрядной организации (размещения закупки)' => ['selection_method', 'Способ определения поставщика', 'Способ определения поставщика'],
                           'Наименование закупки' => ['order_subject'],
                           'Номер лота' => ['lot_number'],
                           'Наименование лота' => ['lot_subject'],
                           'Начальная (максимальная) цена контракта' => ['start_sum', 'Начальная (максимальная) цена контракта', 'Начальная (максимальная) цена контракта', 'float'],
                           'Валюта' => ['currency'],
                           'Наименование Заказчика' => ['customer_name', 'Заказчик', 'Заказчик'],
                           'Дата размещения' => ['publish_date'],
                           'Дата обновления' => ['last_change_date'],
                           'Этап закупки' => ['order_status'],
                           'Дата начала' => ['start_date'],
                           'Дата окончания' => ['end_date'],
                           '' => [''],
                           'Реестровый номер банковской гарантии' => ['guarantee_id'],
                           'Номер банковской гарантии, присвоенный кредитной организацией' => ['guarantee_number', 'Номер банковской гарантии', 'Номер банковской гарантии'],
                           'Дата выдачи банковской гарантии' => ['issue_date', 'Дата выдачи', 'Дата выдачи'],
                           'Дата размещения' => ['publish_date'],
                           'Дата обновления' => ['last_change_date'],
                           'Размер банковской гарантии' => ['guarantee_total', 'Размер банковской гарантии', 'Размер банковской гарантии', 'float'],
                           'Наименование валюты' => ['currency_name'],
                           'Наименование банка-гаранта' => ['bank_name'],
                           'ИНН банка-гаранта' => ['bank_inn'],
                           'Наименование заказчика-бенефициара' => ['beneficiary_name'],
                           'ИНН заказчика-бенефициара' => ['beneficiary_inn'],
                           'Вид обеспечения' => ['guarantee_type'],
                           'Реестровый номер контракта' => ['contract_id'],
                           'Номер закупки' => ['order_id'],
                           'Идентификационный код закупки (ИКЗ)' => ['order_code', 'Идентификационный код закупки', 'Идентификационный код закупки'],
                           'Дата вступления в силу банковской гарантии (при наличии)' => ['guarantee_start_date'],
                           'Дата окончания срока действия банковской гарантии' => ['guarantee_end_date'],
                           'Статус банковской гарантии' => ['guarantee_status'],
                           '' => [''],
                           'Закон' => ['law'],
                           'Уникальный учётный номер организации' => ['org_number'],
//                           'код ИКУ' => array('org_iku'),
//                           'ОГРН' => array('ogrn'),
                           'Полное наименование организации' => ['org_fullname'],
                           'Сокращенное наименование организации' => ['org_shortname'],
                           'ИНН' => ['inn'],
                           'ОГРН' => ['ogrn'],
                           'КПП' => ['kpp'],
                           'Уровень организации' => ['org_level'],
                           'Полномочия организации/Вид юридического лица' => ['org_type', 'Вид организации', 'Вид организации'],
                           'Адрес / место нахождения' => ['org_address', 'Адрес', 'Адрес'],
                           '' => [''],
                           'Номер реестровой записи' => ['customer_number'],
                           'Статус' => ['status'],
                           'Полномочия организации' => ['org_authority', 'Полномочия организации', 'Полномочия организации'],
                           'Вид юридического лица' => ['org_type', 'Вид организации', 'Вид организации'],
    ];

    public function getName()
    {
        return 'zakupki';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск в ЕИС в сфере закупок',
            'zakupki_eruz' => 'ЕИС в сфере закупок - поиск участника',
            'zakupki_org' => 'ЕИС в сфере закупок - поиск организации',
            'zakupki_customer223' => 'ЕИС в сфере закупок - поиск заказчика',
            'zakupki_order' => 'ЕИС в сфере закупок - участие в торгах',
            'zakupki_contract' => 'ЕИС в сфере закупок - контракты с бюджетным организациями 44-ФЗ и 94-ФЗ',
            'zakupki_fz223' => 'ЕИС в сфере закупок - контракты 223-ФЗ',
            'zakupki_capital' => 'ЕИС в сфере закупок - контракты на капитальное строительство',
            'zakupki_rkpo' => 'ЕИС в сфере закупок - поиск в реестре квалифицированных подрядных организаций',
            'zakupki_dishonest' => 'ЕИС в сфере закупок - поиск в реестре недобросовестных поставщиков',
            'zakupki_guarantee' => 'ЕИС в сфере закупок - банковские гарантии (до 01.07.2018)',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в ЕИС в сфере закупок';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 8);

        if (!isset($initData['inn'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (ИНН)');

            return false;
        }
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        if (!isset($swapData['page'])) {
            $swapData['page'] = 1;
            $rContext->setSwapData($swapData);
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'http://zakupki.gov.ru/epz/';
        if ('order' == $checktype) {
            //            $url .= 'order/search/results.html?pageNumber='.$swapData['page'].'&recordsPerPage=_500&fz44=on&fz223=on&ppRf615=on&fz94=on&participantName='.$initData['inn'];
            $url .= 'order/extendedsearch/orderCsvSettings/download.html?morphology=on&pageNumber=1&sortDirection=false&recordsPerPage=_100&showLotsInfoHidden=true&sortBy=UPDATE_DATE&fz44=on&fz223=on&ppRf615=on&fz94=on&af=on&ca=on&pc=on&pa=on&currencyIdGeneral=-1&selectedSubjectsIdNameHidden=%7B%7D&OrderPlacementSmallBusinessSubject=on&OrderPlacementRnpData=on&OrderPlacementExecutionRequirement=on&orderPlacement94_0=0&orderPlacement94_1=0&orderPlacement94_2=0&from=1&to=100&placementCsv=true&registryNumberCsv=true&stepOrderPlacementCsv=true&methodOrderPurchaseCsv=true&nameOrderCsv=true&purchaseNumbersCsv=true&numberLotCsv=true&nameLotCsv=true&maxContractPriceCsv=true&currencyCodeCsv=true&maxPriceContractCurrencyCsv=true&currencyCodeContractCurrencyCsv=true&scopeOkdpCsv=true&scopeOkpdCsv=true&scopeOkpd2Csv=true&scopeKtruCsv=true&ea615ItemCsv=true&customerNameCsv=true&organizationOrderPlacementCsv=true&publishDateCsv=true&lastDateChangeCsv=true&startDateRequestCsv=true&endDateRequestCsv=true&ea615DateCsv=true&featureOrderPlacementCsv=true&participantName='.$initData['inn'];
        } elseif ('contract' == $checktype) {
            //            $url .= 'contract/search/results.html?pageNumber='.$swapData['page'].'&recordsPerPage=_500&fz44=on&fz94=on&supplierTitle='.$initData['inn'];
            $url .= 'contract/contractCsvSettings/download.html?morphology=on&fz44=on&fz94=on&contractStageList_0=on&contractStageList_1=on&contractStageList_2=on&contractStageList_3=on&contractStageList=0%2C1%2C2%2C3&selectedContractDataChanges=ANY&contractCurrencyID=-1&budgetLevelsIdNameHidden=%7B%7D&sortBy=UPDATE_DATE&pageNumber=1&sortDirection=false&recordsPerPage=_100&showLotsInfoHidden=false&from=1&to=100&numberRegisterContractCsv=true&customerNameCsv=true&customerInnCsv=true&customerKppCsv=true&budgetLavelCsv=true&budgetNameCsv=true&nobudgetViewCsv=true&orderPlanMethodCsv=true&noticeNumberTenderCsv=true&auctionDateCsv=true&requsiteDocumentsCsv=true&contractDateCsv=true&contractNumberCsv=true&contractSubjectNameCsv=true&contractPriceCsv=true&codeBudgetClassificationCsv=true&kosguCsv=true&kvrCsv=true&ikzCsv=true&scopeContractNameServiceCsv=true&scopeContractOkdpCsv=true&scopeContractPriceCsv=true&scopeContractCountCsv=true&scopeContractSumCsv=true&infoSupplierNameCsv=true&infoSupplierInnCsv=true&infoSupplierKppCsv=true&lastDateChangeCsv=true&dateContractCsvCsv=true&&supplierTitle='.$initData['inn'];
        } elseif ('fz223' == $checktype) {
            $url .= 'contractfz223/search/results.html?pageNumber='.$swapData['page'].'&recordsPerPage=_50&supplierTitle='.$initData['inn'];
        } elseif ('capital' == $checktype) {
            $url .= 'capitalrepairs/search/results.html?pageNumber='.$swapData['page'].'&recordsPerPage=_50&subContractNameInn='.$initData['inn'];
        } elseif ('guarantee' == $checktype) {
            //            $url .= 'bankguarantee/search/results.html?pageNumber='.$swapData['page'].'&recordsPerPage=_50&supplier='.$initData['inn'];
            $url .= 'bankguarantee/bankGuaranteeCsvSettings/download.html?morphology=on&pageNumber=1&sortDirection=false&recordsPerPage=_50&showLotsInfoHidden=false&sortBy=PUBLISH_DATE_SORT&currencyId=-1&from=1&to=100&registryNumberCsv=true&creditInstitutionBgNumberCsv=true&registryStatusCsv=true&supplyTypeCsv=true&issueDateCsv=true&publishDateCsv=true&registryUpdateDateCsv=true&effectStartDateCsv=true&validityEndDateCsv=true&amountCsv=true&currencyNameCsv=true&supplierFullNameCsv=true&supplierInnCsv=true&bankFullNameCsv=true&bankInnCsv=true&customerFullNameCsv=true&customerInnCsv=true&contractRegistryNumberCsv=true&purchaseRegistryNumberCsv=true&purchaseCodeCsv=true&supplier='.$initData['inn'];
        } elseif ('dishonest' == $checktype) {
            $url .= 'dishonestsupplier/search/results.html?pageNumber='.$swapData['page'].'&recordsPerPage=_50&fz94=on&fz223=on&ppRf615=on&customerINN='.$initData['inn'];
        } elseif ('rkpo' == $checktype) {
            $url .= 'rkpo/search/results.html?strictEqual=false&pageNumber='.$swapData['page'].'&recordsPerPage=_50&searchString='.$initData['inn'];
        } elseif ('eruz' == $checktype) {
            $url .= 'eruz/search/results.html?pageNumber='.$swapData['page'].'&recordsPerPage=_50&inn='.$initData['inn'];
        } elseif ('org' == $checktype) {
            //            $url .= 'organization/search/results.html?pageNumber='.$swapData['page'].'&recordsPerPage=_50&withBlocked=on&inn='.$initData['inn'];
            $url .= 'organization/organizationCsvSettings/download.html?morphology=on&fz94=on&fz223=on&F=on&S=on&M=on&NOT_FSM=on&sortBy=NAME&pageNumber=1&sortDirection=true&recordsPerPage=_50&showLotsInfoHidden=false&from=1&to=100&lawCsv=true&orgLevelCsv=true&fullNameOrgCsv=true&shortNameOrgCsv=true&orgNumberCsv=true&ikuCsv=true&innCsv=true&ogrnCsv=true&kppCsv=true&addressCsv=true&orgTypeCsv=true&inn='.$initData['inn'];
        } elseif ('customer223' == $checktype) {
            //            $url .= 'customer223/extendedsearch/results.html?pageNumber='.$swapData['page'].'&recordsPerPage=_50&withBlocked=on&inn='.$initData['inn'];
            $url .= 'customer223/customer223CsvSettings/download.html?morphology=on&sortBy=NAME&pageNumber=1&sortDirection=false&recordsPerPage=_50&showLotsInfoHidden=false&regionDeleted=false&from=1&to=100&regNumberCsv=true&statusCsv=true&fullNameOrgCsv=true&shortNameOrgCsv=true&innCsv=true&ogrnCsv=true&kppCsv=true&organizationRulesCsv=true&jurTypeCsv=true&addressCsv=true&includeDateCsv=true&inn='.$initData['inn'];
        }
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 1);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 8);

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        $error = ($swapData['iteration'] > 5) && \curl_error($rContext->getCurlHandler());
        if (!$error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            $format = false !== \strpos($content, '<html') ? 'html' : 'csv';
            \file_put_contents('./logs/zakupki/zakupki_'.$checktype.'_'.$initData['inn'].'_'.$swapData['page'].'_'.\time().'.'.$format, $content);

            if (isset($swapData['result'])) {
                $resultData = $swapData['result'];
            } else {
                $resultData = new ResultDataList();
            }

            if ('csv' == $format && !$content) {
                if ($swapData['iteration'] > 3) {
                    $error = 'Ответ от сервиса не получен';
                }
            } elseif ('csv' == $format && $content) {
                $content = \iconv('windows-1251', 'utf-8', $content);
                $content = \explode("\n", $content);
                foreach ($content as $row => $text) {
                    if (0 == $row) {
                        $titles = \str_getcsv($text, ';');
                    } else {
                        $values = \str_getcsv($text, ';', '"');
                        $fields = [];
                        $data = [];
                        foreach ($values as $i => $val) {
                            $title = isset($titles[$i]) ? $titles[$i] : '-';
                            if ("'" == \substr($title, 0, 1) && "'" == \substr($title, \strlen($title) - 1, 1)) {
                                $title = \substr($title, 1, \strlen($title) - 2);
                            }
                            if (isset($this->names[$title])) {
                                $field = $this->names[$title];
                                if ("'" == \substr($val, 0, 1) && "'" == \substr($val, \strlen($val) - 1, 1)) {
                                    $val = \substr($val, 1, \strlen($val) - 2);
                                }
                                if ('order_id' == $field[0]) {
                                    $val = \strtr($val, ['№' => '']);
                                }
                                if (isset($field[3]) && 'float' == $field[3]) {
                                    $val = \strtr($val, [' ' => '', ',' => '.']);
                                }
                                if ($val) {
                                    $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $val, isset($field[1]) ? $field[1] : $title, isset($field[2]) ? $field[2] : $title);
                                    $fields[$field[0]] = $val;
                                }
                            }
                        }
                        if (isset($fields['order_id'])) {
                            $url = false;
                            if ((isset($fields['order_law']) && '44-ФЗ' == $fields['order_law']) || 19 == \strlen($fields['order_id'])) {
                                $url = 'http://zakupki.gov.ru/epz/order/notice/ea44/view/common-info.html?regNumber='.$fields['order_id'];
                            }
                            if (isset($fields['order_law']) && '223-ФЗ' == $fields['order_law']) {
                                $url = 'http://zakupki.gov.ru/223/purchase/public/purchase/info/common-info.html?regNumber='.$fields['order_id'];
                            }
                            //                            if (isset($fields['order_law']) && $fields['order_law']=='94-ФЗ')
                            //                                $url='http://zakupki.gov.ru/pgz/public/action/orders/info/common_info/show?source=epz&notificationId='.$fields['order_id'];
                            if (isset($fields['order_law']) && 'ПП РФ 615' == $fields['order_law']) {
                                $url = 'http://zakupki.gov.ru/epz/order/notice/ea615/view/common-info.html?regNumber='.$fields['order_id'];
                            }
                            if ($url) {
                                $data['order_url'] = new ResultDataField('url', 'order_url', $url, 'Страница закупки', 'Страница закупки');
                            }
                        }
                        if (isset($fields['contract_id'])) {
                            $url = 'https://zakupki.gov.ru/epz/contract/contractCard/common-info.html?reestrNumber='.$fields['contract_id'];
                            $data['contract_url'] = new ResultDataField('url', 'contract_url', $url, 'Страница контракта', 'Страница контракта');
                        }
                        if (\count($data)) {
                            $resultData->addResult($data);
                        }
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } else {
                $parts = [''];
                if (\preg_match('/<div class="search-registry-entry-block/', $content)) {
                    $parts = \preg_split('/<div class="search-registry-entry-block/', $content);
                } elseif (\preg_match('/<div class="registerBox registerBoxBank/', $content)) {
                    $parts = \preg_split('/<div class="registerBox registerBoxBank/', $content);
                } elseif (\preg_match('/<p class="noRecords">/', $content)) {
                } elseif (\preg_match('/недоступны функции/', $content) || \preg_match('/временно недоступ/', $content) || \preg_match('/Service Unavailable/', $content)) {
                    if ($swapData['iteration'] > 3) {
                        $error = 'Сервис временно недоступен';
                    }
                } elseif (\preg_match('/failed/', $content)) {
                } else {
                    \file_put_contents('./logs/zakupki/zakupki_'.$checktype.'_err_'.$initData['inn'].'_'.$swapData['page'].'_'.\time().'.'.$format, $content);
                    $error = 'Некорректный ответ сервиса';
                }
                \array_shift($parts);
                foreach ($parts as $part) {
                    $data = [];
                    if ('contract' == $checktype || 'fz223' == $checktype || 'capital' == $checktype) {
                        if (\preg_match("/<div class=\"registry-entry__header-mid__title\">([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['contract_status'] = new ResultDataField('string', 'contract_status', \trim(\strip_tags($matches[1])), 'Статус контракта', 'Статус контракта');
                        }
                        if (\preg_match("/<div class=\"price-block__value\">([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['contract_total'] = new ResultDataField('float', 'contract_total', \strtr(\trim(\strip_tags($matches[1])), [',' => '.', "\u{a0}" => '', '&nbsp;' => '', '&#8381;' => '']), 'Сумма контракта', 'Сумма контракта');
                        }
                        if (\preg_match("/<div class=\"registry-entry__header-mid__number\">[\s]*<a target=\"_blank\" href=\"[^>]+>([^>]+)<\/a>/sim", $part, $matches)) {
                            $data['contract_id'] = new ResultDataField('string', 'contract_id', \trim(\strtr(\strip_tags($matches[1]), ['№' => ''])), 'Реестровый номер контракта', 'Реестровый номер контракта');
                        }
                        if (\preg_match("/<div class=\"registry-entry__header-mid__number\">[\s]*<a target=\"_blank\" href=\"([^\"]+)/sim", $part, $matches)) {
                            $data['contract_url'] = new ResultDataField('url', 'contract_url', (\strpos($matches[1], '://') ? '' : 'http://zakupki.gov.ru').$matches[1], 'Страница контракта', 'Страница контракта');
                        }
                        if (\preg_match("/Заказчик<\/div>[\s]*<div class=\"registry-entry__body-[^>]+>(.*?)<\/div>/sim", $part, $matches)) {
                            $data['customer_name'] = new ResultDataField('string', 'customer_name', \trim(\strip_tags($matches[1])), 'Заказчик', 'Заказчик');
                            if (\preg_match('/<a href="([^"]+)/', $matches[1], $url)) {
                                $data['customer_url'] = new ResultDataField('url', 'customer_url', (\strpos($url[1], '://') ? '' : 'http://zakupki.gov.ru').$url[1], 'Страница заказчика', 'Страница заказчика');
                            }
                        }
                        if (\preg_match("/Номер договора<\/div>[\s]*<div class=\"registry-entry__body-value\">([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['contract_number'] = new ResultDataField('string', 'contract_number', \trim(\strtr(\strip_tags($matches[1]), ['№' => ''])), 'Номер контракта', 'Номер контракта');
                        }
                        if (\preg_match("/Заключени[ея]* договора<\/div>[\s]*<div class=\"data-block__value\">([^<]+)/sim", $part, $matches)) {
                            $data['contract_date'] = new ResultDataField('string', 'contract_date', \trim(\strip_tags($matches[1])), 'Дата контракта', 'Дата контракта');
                        }
                        if (\preg_match("/Срок исполнения<\/div>[\s]*<div class=\"data-block__value\">[\s]*([\d\.]+)[^\d]+([\d\.]+)[\s]*<\/div>/sim", $part, $matches)) {
                            $data['start_date'] = new ResultDataField('string', 'start_date', \trim(\strip_tags($matches[1])), 'Дата начала', 'Дата начала');
                            $data['end_date'] = new ResultDataField('string', 'end_date', \trim(\strip_tags($matches[2])), 'Дата окончания', 'Дата окончания');
                        }
                        if (\preg_match("/Окончание исполнения<\/div>[\s]*<div class=\"data-block__value\">([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['end_date'] = new ResultDataField('string', 'end_date', \trim(\strip_tags($matches[1])), 'Дата окончания', 'Дата окончания');
                        }
                        if (\preg_match("/Предмет электронного аукциона<\/div>[\s]*<div class=\"registry-entry__body-value\">([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['subject_name'] = new ResultDataField('string', 'subject_name', \trim(\strip_tags($matches[1])), 'Предмет контракта', 'Предмет контракта');
                        }
                        if (\preg_match("/Реквизиты закупки<\/div>[\s]*<div class=\"registry-entry__body-value\">(.*?)<\/div>/sim", $part, $matches)) {
                            $a = \explode('№', \strip_tags($matches[1]));
                            if (2 == \count($a)) {
                                $data['order_placement'] = new ResultDataField('string', 'order_placement', \trim($a[0]), 'Способ размещения заказа', 'Способ размещения заказа');
                                $data['order_number'] = new ResultDataField('string', 'order_number', \trim($a[1]), 'Номер закупки', 'Номер закупки');
                            }
                            if (\preg_match('/<a href="([^"]+)/', $matches[1], $url)) {
                                $data['order_url'] = new ResultDataField('url', 'order_url', (\strpos($url[1], '://') ? '' : 'http://zakupki.gov.ru').$url[1], 'Страница закупки', 'Страница закупки');
                            }
                        }
                    } elseif ('guarantee' == $checktype) {
                    } elseif ('dishonest' == $checktype) {
                        if (\preg_match("/<div class=\"registry-entry__header-top__title text-truncate\">[\s]*([^<]+)/sim", $part, $matches)) {
                            $data['dishonest_law'] = new ResultDataField('string', 'dishonest_law', \trim($matches[1]), 'Реестр недобросовестных поставщиков по', 'Реестр недобросовестных поставщиков по');
                        }
                        if (\preg_match("/<div class=\"registry\-entry__header\-mid__number\"><a[\s]+href=\"[^>]+>([^<]+)<\/a>/sim", $part, $matches)) {
                            $data['dishonest_number'] = new ResultDataField('string', 'dishonest_number', \trim(\strtr($matches[1], ['№' => ''])), 'Номер записи', 'Номер записи');
                        }
                        if (\preg_match("/<div class=\"registry\-entry__header\-mid__number\"><a[\s]+href=\"([^\"]+)/sim", $part, $matches)) {
                            $data['dishonest_url'] = new ResultDataField('url', 'dishonest_url', (\strpos($matches[1], '://') ? '' : 'http://zakupki.gov.ru').$matches[1], 'Страница записи', 'Страница записи');
                        }
                        if (\preg_match("/<div[^>]*>Наименование \(ФИО\) недобросовестного[\s]+поставщика[\s]*<\/div>[\s]*<div[^>]*>([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['supplier_name'] = new ResultDataField('string', 'supplier_name', \trim($matches[1]), 'Наименование поставщика', 'Наименование поставщика');
                        }
                        if (\preg_match("/<div[^>]*>[\s]*Местонахождение[\s]*<\/div>[\s]*<div[^>]*>([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['supplier_address'] = new ResultDataField('string', 'supplier_address', \trim($matches[1]), 'Адрес поставщика', 'Адрес поставщика');
                        }
                        if (\preg_match("/<div[^>]*>[\s]*Идентификационный код закупки \(ИКЗ\)[\s]*<\/div>[\s]*<div[^>]*>([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['order_code'] = new ResultDataField('string', 'order_code', \trim($matches[1]), 'Идентификационный код закупки', 'Идентификационный код закупки');
                        }
                        if (\preg_match("/<div[^>]*>[\s]*Включено[\s]*<\/div>[\s]*<div[^>]*>([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['inclusion_date'] = new ResultDataField('string', 'inclusion_date', $matches[1], 'Дата включения в реестр', 'Дата включения в реестр');
                        }
                        if (\preg_match("/<div[^>]*>[\s]*Планируемая дата исключения[\s]*<\/div>[\s]*<div[^>]*>([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['exclusion_date'] = new ResultDataField('string', 'exclusion_date', \trim($matches[1]), 'Дата исключения из реестра', 'Дата исключения из реестра');
                        }
                    } elseif ('eruz' == $checktype) {
                        if (\preg_match("/>[\s]*№ ([\d]+)[\s]*</sim", $part, $matches)) {
                            $data['eruz_number'] = new ResultDataField('string', 'eruz_number', \trim(\strip_tags($matches[1])), 'Номер в ЕРУЗ', 'Номер в ЕРУЗ');
                            $data['eruz_url'] = new ResultDataField('url', 'eruz_url', 'https://zakupki.gov.ru/epz/eruz/card/general-information.html?reestrNumber='.\trim(\strip_tags($matches[1])), 'Карточка участника', 'Карточка участника');
                        }
                        if (\preg_match("/<div class=\"registry-entry__body-href\">(.*?)<\/div>/sim", $part, $matches)) {
                            $data['name'] = new ResultDataField('string', 'name', \trim(\strip_tags($matches[1])), 'Наименование', 'Наименование');
                        }
                        if (\preg_match('/<div class="registry-entry__header-mid__title">([^<]+)</sim', $part, $matches)) {
                            $data['status'] = new ResultDataField('string', 'status', \trim(\strip_tags($matches[1])), 'Статус', 'Статус');
                        }
                        if (\preg_match("/ИНН<\/div>[\s]*<div class=\"registry-entry__body-value\">([^<]+)/sim", $part, $matches)) {
                            //                            $data['inn'] = new ResultDataField('string', 'inn', trim(strip_tags($matches[1])), 'ИНН', 'ИНН');
                        }
                        if (\preg_match("/КПП<\/div>[\s]*<div class=\"registry-entry__body-value\">([^<]+)/sim", $part, $matches)) {
                            $data['kpp'] = new ResultDataField('string', 'kpp', \trim(\strip_tags($matches[1])), 'КПП', 'КПП');
                        }
                        if (\preg_match("/ОГРН<\/div>[\s]*<div class=\"registry-entry__body-value\">([^<]+)/sim", $part, $matches)) {
                            $data['ogrn'] = new ResultDataField('string', 'ogrn', \trim(\strip_tags($matches[1])), 'ОГРН', 'ОГРН');
                        }
                        if (\preg_match("/ОГРНИП<\/div>[\s]*<div class=\"registry-entry__body-value\">([^<]+)/sim", $part, $matches)) {
                            $data['ogrnip'] = new ResultDataField('string', 'ogrnip', \trim(\strip_tags($matches[1])), 'ОГРНИП', 'ОГРНИП');
                        }
                        if (\preg_match('/<div class="text-block__title">([^<]+)/sim', $part, $matches)) {
                            $data['participant_type'] = new ResultDataField('string', 'participant_type', \trim(\strip_tags($matches[1])), 'Тип участника', 'Тип участника');
                        }
                        if (\preg_match("/Регистрация<\/div>[\s]*<div class=\"data-block__value\">([^<]+)/sim", $part, $matches)) {
                            $data['reg_date'] = new ResultDataField('string', 'reg_date', \trim(\strip_tags($matches[1])), 'Дата регистрации', 'Дата регистрации');
                        }
                    } elseif ('rkpo' == $checktype) {
                        if (\preg_match("/Наименование квалифицированной подрядной организации:<\/div>[\s]*<div[^>]*>(.*?)<\/div>/sim", $part, $matches)) {
                            $data['supplier_name'] = new ResultDataField('string', 'supplier_name', \trim(\strip_tags($matches[1])), 'Наименование поставщика', 'Наименование поставщика');
                        }
                        if (\preg_match("/<div class=\"registry-entry__header-mid__title\">([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['org_status'] = new ResultDataField('string', 'org_status', \trim(\strip_tags($matches[1])), 'Статус организации', 'Статус организации');
                        }
                        if (\preg_match("/<div class=\"price-block__value\">([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['max_total'] = new ResultDataField('float', 'max_total', \strtr(\trim(\strip_tags($matches[1])), [',' => '.', "\u{a0}" => '', '&nbsp;' => '', '&#8381;' => '']), 'Максимальная начальная сумма', 'Максимальная начальная сумма');
                        }
                        if (\preg_match("/<div[^<]+<a target=\"_blank\" href=\"[^>]+>([^>]+)<\/a>/sim", $part, $matches)) {
                            $data['rkpo_id'] = new ResultDataField('string', 'rkpo_id', \trim(\strtr($matches[1], ['№' => ''])), 'Реестровый номер квалифицированной подрядной организации', 'Реестровый номер квалифицированной подрядной организации');
                        }
                        if (\preg_match("/<div[^<]+<a target=\"_blank\" href=\"([^\"\&]+)/sim", $part, $matches)) {
                            $data['rkpo_url'] = new ResultDataField('url', 'rkpo_url', (\strpos($matches[1], '://') ? '' : 'http://zakupki.gov.ru').$matches[1], 'Карточка квалифицированной подрядной организации', 'Карточка квалифицированной подрядной организации');
                        }
                        if (\preg_match("/Предмет электронного аукциона<\/div>[\s]*<div class=\"registry-entry__body-value\">([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['subject_name'] = new ResultDataField('string', 'subject_name', \trim(\strip_tags($matches[1])), 'Предмет контракта', 'Предмет контракта');
                        }
                        if (\preg_match("/Номер предварительного отбора<\/div>[\s]*<div class=\"registry-entry__body-[^>]+>(.*?)<\/div>/sim", $part, $matches)) {
                            $data['po_number'] = new ResultDataField('string', 'po_number', \trim(\strip_tags($matches[1])), 'Номер предварительного отбора', 'Номер предварительного отбора');
                            if (\preg_match("/<a href=\"([^\"\&]+)/", $matches[1], $url)) {
                                $data['po_url'] = new ResultDataField('url', 'po_url', (\strpos($url[1], '://') ? '' : 'http://zakupki.gov.ru').$url[1], 'Страница предварительного отбора', 'Страница предварительного отбора');
                            }
                        }
                        if (\preg_match("/Включено в сводный реестр<\/div>[\s]*<div class=\"data-block__value\">([^<]+)/sim", $part, $matches)) {
                            $data['inclusion_date'] = new ResultDataField('string', 'inclusion_date', \trim(\strip_tags($matches[1])), 'Дата включения в реестр', 'Дата включения в реестр');
                        }
                        if (\preg_match("/Исключено из сводного реестра<\/div>[\s]*<div class=\"data-block__value\">([^<]+)/sim", $part, $matches)) {
                            $data['exclusion_date'] = new ResultDataField('string', 'exclusion_date', \trim(\strip_tags($matches[1])), 'Дата исключения из реестра', 'Дата исключения из реестра');
                        }
                        if (\preg_match("/Период включения в сводный реестр<\/div>[\s]*<div [^<]+<div class=\"data-block__value\">([^<]+)<\/div>[^<]+<div class=\"data-block__value\">([^<]+)<\/div>/sim", $part, $matches)) {
                            $data['inclusion_date'] = new ResultDataField('string', 'inclusion_date', \trim(\strip_tags($matches[1])), 'Дата включения в реестр', 'Дата включения в реестр');
                            $data['exclusion_date'] = new ResultDataField('string', 'exclusion_date', \trim(\strip_tags($matches[2])), 'Дата исключения из реестра', 'Дата исключения из реестра');
                        }
                    }
                    if (\preg_match("/Субъект РФ<\/div>[\s]*<div class=\"registry-entry__body-value\">([^<]+)<\/div>/sim", $part, $matches)) {
                        $data['region'] = new ResultDataField('string', 'region', \trim(\strip_tags($matches[1])), 'Регион', 'Регион');
                    }
                    if (\preg_match("/Размещено<\/div>[\s]*<div class=\"data-block__value\">([^<]+)/sim", $part, $matches)) {
                        $data['publish_date'] = new ResultDataField('string', 'publish_date', $matches[1], 'Дата размещения', 'Дата размещения');
                    }
                    if (\preg_match("/<label[^>]*>Обновлено:<\/label>(.*?)<\/li>/sim", $part, $matches) || \preg_match("/Обновлено<\/div>[\s]*<div class=\"data-block__value\">([^<]+)/sim", $part, $matches)) {
                        $data['last_change_date'] = new ResultDataField('string', 'last_change_date', $matches[1], 'Дата обновления', 'Дата обновления');
                    }
                    if (\count($data)) {
                        $resultData->addResult($data);
                    }
                }
                if ($error) {
                } elseif (\preg_match('/<li class="rightArrow">/', $content)) {
                    --$swapData['iteration'];
                    ++$swapData['page'];
                    $swapData['result'] = $resultData;
                } else {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                }
            }
            $rContext->setSwapData($swapData);
        }

        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 5) {
            $error = 'Превышено количество попыток получения ответа';
        }

        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        return true;
    }
}
