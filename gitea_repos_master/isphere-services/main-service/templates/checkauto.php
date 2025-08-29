<?php

use App\Controller\AdminController;
use App\Controller\DefaultController;
use App\Entity\AccessRoles;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

require_once 'xml.php';

$mainRequest = $request;

$view->extend('base.php');

\set_time_limit($form_timeout + 30);

$user_level = $user->getAccessLevel();
$user_sources = $user->getAccessSourcesMap();

// Источники (название,выбран,рекурсивный,конец строки)
$check_sources = [
    'gibdd_history' => ['ГИБДД история', 1, 0, 0],
    'gibdd_aiusdtp' => ['ГИБДД дтп', 1, 0, 1],
    'gibdd_restricted' => ['ГИБДД ограничения', 1, 0, 0],
    'gibdd_wanted' => ['ГИБДД розыск', 1, 0, 1],
    'gibdd_diagnostic' => ['ГИБДД техосмотр', 1, 0, 0],
    'gibdd_fines' => ['ГИБДД штрафы', 0, 0, 1],
    'gibdd_driver' => ['ГИБДД права', 1, 0, 0],
    'rsa_policy' => ['РСА авто', 1, 0, 0],
    'rsa_bsostate' => ['РСА полис', 1, 0, 0],
    'rsa_kbm' => ['РСА КБМ', 1, 0, 1],
    'eaisto' => ['ЕАИСТО', 1, 0, 0],
    'avtokod' => ['Автокод', 1, 0, 0],
    'gisgmp' => ['ГИС ГМП', 1, 0, 1],
//  'rz'=>array('Реестр залогов',1,0,0),
    'reestrzalogov' => ['Реестр залогов', 1, 0, 0],
//  'avinfo'=>array('AvInfo',1,1,0)),
//  'vin'=>array('Расшифровка VIN',1,1,0),
    'fssp' => ['ФССП', 1, 0, 1],
];

if (!isset($_REQUEST['vin'])) {
    $_REQUEST['vin'] = '';
}
if (!isset($_REQUEST['bodynum'])) {
    $_REQUEST['bodynum'] = '';
}
if (!isset($_REQUEST['chassis'])) {
    $_REQUEST['chassis'] = '';
}
if (!isset($_REQUEST['regnum'])) {
    $_REQUEST['regnum'] = '';
}
if (!isset($_REQUEST['ctc'])) {
    $_REQUEST['ctc'] = '';
}
if (!isset($_REQUEST['pts'])) {
    $_REQUEST['pts'] = '';
}
if (!isset($_REQUEST['osago'])) {
    $_REQUEST['osago'] = '';
}
if (!isset($_REQUEST['reqdate'])) {
    $_REQUEST['reqdate'] = '';
}
if (!isset($_REQUEST['driver_number'])) {
    $_REQUEST['driver_number'] = '';
}
if (!isset($_REQUEST['driver_date'])) {
    $_REQUEST['driver_date'] = '';
}
if (!isset($_REQUEST['sources'])) {
    $_REQUEST['sources'] = [];
}
if (!isset($_REQUEST['recursive'])) {
    $_REQUEST['recursive'] = 0;
}
if (!isset($_REQUEST['async'])) {
    $_REQUEST['async'] = ('POST' == $_SERVER['REQUEST_METHOD'] ? 0 : 1);
}

foreach ($_REQUEST as $rn => $rv) {
    $_REQUEST[$rn] = \preg_replace("/[<>\/]/", '', $rv);
}

foreach ($check_sources as $k => $s) {
    if (/* ($user_level<0) || */
        isset($user_sources[$k]) && $user_sources[$k]) {
        if (!isset($_REQUEST['mode']) && !isset($_REQUEST['sources'][$k])) {
            $_REQUEST['sources'][$k] = $s[1];
        }
        //        if ($_REQUEST['recursive'] && $s[2]) $_REQUEST['sources'][$k] = 1;
    }
}

$view['slots']->set('title', 'Проверка автомобиля');

?>
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <form id="checkform" method="POST">
                    <div class="form-group row mb-3">
                        <label for="vin" class="col-sm-3 col-form-label">VIN</label>
                        <div class="col-sm-4">
                            <input type="text" name="vin" value="<?php echo $_REQUEST['vin']; ?>" maxlength="17"
                                   class="form-control" id="vin" autofocus/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="bodynum" class="col-sm-3 col-form-label">Номер кузова</label>
                        <div class="col-sm-4">
                            <input type="text" name="bodynum" value="<?php echo $_REQUEST['bodynum']; ?>" maxlength="20"
                                   class="form-control" id="bodynum"/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="chassis" class="col-sm-3 col-form-label">Номер шасси</label>
                        <div class="col-sm-4">
                            <input type="text" name="chassis" value="<?php echo $_REQUEST['chassis']; ?>" maxlength="20"
                                   class="form-control" id="chassis"/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="regnum" class="col-sm-3 col-form-label">Гос.номер</label>
                        <div class="col-sm-4">
                            <input type="text" name="regnum" value="<?php echo $_REQUEST['regnum']; ?>"
                                   maxlength="10" class="form-control" id="regnum"/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="ctc" class="col-sm-3 col-form-label">Св-во о регистрации ТС</label>
                        <div class="col-sm-4">
                            <input type="text" name="ctc" value="<?php echo $_REQUEST['ctc']; ?>" maxlength="10"
                                   id="ctc" class="form-control"/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="pts" class="col-sm-3 col-form-label">Паспорт ТС</label>
                        <div class="col-sm-4">
                            <input type="text" name="pts" value="<?php echo $_REQUEST['pts']; ?>" maxlength="10"
                                   id="pts" class="form-control"/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="osago" class="col-sm-3 col-form-label">Полис ОСАГО</label>
                        <div class="col-sm-4">
                            <input type="text" name="osago" value="<?php echo $_REQUEST['osago']; ?>"
                                   maxlength="100" id="osago" class="form-control"/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="reqdate" class="col-sm-3 col-form-label">Дата запроса в РСА</label>
                        <div class="col-sm-4">
                            <input type="text" id="reqdate" name="reqdate"
                                   value="<?php echo $_REQUEST['reqdate']; ?>"
                                   pattern="(0[1-9]|1[0-9]|2[0-9]|3[01])\.(0[1-9]|1[012])\.[0-9]{4}"
                                   data-type="date"
                                   maxlength="50" class="form-control"/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="driver_number" class="col-sm-3 col-form-label">Номер в/у</label>
                        <div class="col-sm-4">
                            <input type="text" name="driver_number"
                                   value="<?php echo $_REQUEST['driver_number']; ?>"
                                   maxlength="12" id="driver_number" class="form-control"/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="driver_date" class="col-sm-3 col-form-label">Дата выдачи в/у</label>
                        <div class="col-sm-4">
                            <input type="text" name="driver_date" value="<?php echo $_REQUEST['driver_date']; ?>"
                                   pattern="(0[1-9]|1[0-9]|2[0-9]|3[01])\.(0[1-9]|1[012])\.[0-9]{4}"
                                   data-type="date"
                                   maxlength="10" class="form-control" id="driver_date"/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label class="col-sm-3 col-form-label">Источники</label>
                        <div class="col-sm-4">
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap align-content-start" style="gap: 0 1rem;">
                                    <?php
                                    $line = false;
                                    foreach ($check_sources as $k => $s) {
                                        if (/* ($user_level<0) || */
                                        $user->hasAccessSourceBySourceName($k)) {
                                            echo '
<div>
    <div class="form-check">
       <input id="input' . $k . '" type="checkbox" class="form-check-input source" ' . ((isset($_REQUEST['sources'][$k]) && $_REQUEST['sources'][$k]) || $s[2] > 1 ? 'checked' : '') . ' name="sources[' . $k . ']"> 
       <label for="input' . $k . '" class="form-check-label text-nowrap">' . $s[0] . '</label>
    </div>
</div>
';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col">
                                    <button class="btn btn-secondary btn-sm" type="button" id="selectall">Выбрать все
                                    </button>
                                    <button class="btn btn-secondary btn-sm" type="button" id="clearall">Снять все
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <div class="col-sm-3">&nbsp;</div>
                        <div class="col-sm-9">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" <?php
                                echo $_REQUEST['recursive'] ? 'checked' : ''; ?> name="recursive" id="recursive">
                                <label for="recursive" class="form-check-label">Поиск по найденным контактам</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <div class="col-sm-3">&nbsp;</div>
                        <div class="col-sm-9">
                            <div class="form-check">
                                <input type="checkbox" <?= $_REQUEST['async'] ? 'checked' : ''; ?> name="async"
                                       id="async"
                                       class="form-check-input"/>
                                <label for="async" class="">Подгружать информацию по мере получения</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="mode" class="col-sm-3 col-form-label">Формат ответа:</label>
                        <div class="col-sm-4">
                            <select class="form-select" name="mode" id="mode">
                                <option value="xml">XML</option>
                                <option value="html" selected>HTML</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <div class="col-sm-3">&nbsp;</div>
                        <div class="col-sm-4">
                            <input id="submitbutton" type="submit" value="Найти" class="btn btn-primary"/>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php

if (!isset($_REQUEST['mode']) || (!\count($_REQUEST['sources']))) {
    echo '<div id="request">';
    echo '</div>';
    echo '<div id="response">';
    echo '</div>';
    return;
}

$xml =
    "<Request>
        <UserIP>{$_SERVER['REMOTE_ADDR']}</UserIP>
        <UserID>{$user->getUserIdentifier()}</UserID>
        <Password>{$user->getPassword()}</Password>"
    . (!isset($_REQUEST['request_id']) || !$_REQUEST['request_id'] ? '' : "
        <requestId>{$_REQUEST['request_id']}</requestId>"
    ) . '
        <requestType>checkauto</requestType>
        <sources>' . \implode(',', \array_keys($_REQUEST['sources'])) . '</sources>
        <timeout>' . $form_timeout . '</timeout>
        <recursive>' . ($_REQUEST['recursive'] ? '1' : '0') . '</recursive>
        <async>' . ($_REQUEST['async'] ? '1' : '0') . '</async>'
    . (!$_REQUEST['driver_number'] ? '' : "
        <PersonReq>
            <driver_number>{$_REQUEST['driver_number']}</driver_number>"
        . (!$_REQUEST['driver_date'] ? '' : "
            <driver_date>{$_REQUEST['driver_date']}</driver_date>"
        ) . '
        </PersonReq>'
    ) . (!$_REQUEST['vin'] && !$_REQUEST['bodynum'] && !$_REQUEST['regnum'] && !$_REQUEST['ctc'] && !$_REQUEST['pts'] ? '' : '
        <CarReq>'
        . (!$_REQUEST['vin'] ? '' : "
            <vin>{$_REQUEST['vin']}</vin>"
        )
        . (!$_REQUEST['bodynum'] ? '' : "
            <bodynum>{$_REQUEST['bodynum']}</bodynum>"
        )
        . (!$_REQUEST['chassis'] ? '' : "
            <chassis>{$_REQUEST['chassis']}</chassis>"
        )
        . (!$_REQUEST['regnum'] ? '' : "
            <regnum>{$_REQUEST['regnum']}</regnum>"
        )
        . (!$_REQUEST['ctc'] ? '' : "
            <ctc>{$_REQUEST['ctc']}</ctc>"
        )
        . (!$_REQUEST['pts'] ? '' : "
            <pts>{$_REQUEST['pts']}</pts>"
        )
        . (!$_REQUEST['reqdate'] ? '' : "
            <reqdate>{$_REQUEST['reqdate']}</reqdate>"
        ) . '
        </CarReq>'
    ) . (!isset($_REQUEST['osago']) || !$_REQUEST['osago'] ? '' : '
        <OtherReq>'
        . (!$_REQUEST['osago'] ? '' : "
            <osago>{$_REQUEST['osago']}</osago>"
        ) . '
        </OtherReq>'
    ) . '
</Request>';

echo '<div id="request">';
if ('xml' == $_REQUEST['mode']) {
    echo 'Запрос XML: <textarea style="width:100%;height:30%">';
    $request = \preg_replace("/<Password>[^<]+<\/Password>/", '<Password>***</Password>', $xml);
    echo $request;
    echo '</textarea>';
    echo '<hr/>';
}
echo '</div>';

$subRequest = Request::create($urlGenerator->generate(DefaultController::NAME), Request::METHOD_POST, content: $xml);
$subRequest->attributes->set('_controller', DefaultController::class);
$subRequest->setSession($mainRequest->getSession());
$response = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
$answer = $response->getContent();

echo '<div id="response">';
if ('xml' == $_REQUEST['mode']) {
    echo 'Ответ XML: <textarea style="width:100%;height:70%">';
    echo $answer;
    echo '</textarea>';
} else {
    $answer = \substr($answer, \strpos($answer, '<?xml'));
    $doc = xml_transform($answer, 'isphere_view.xslt');
    if ($doc) {
        $servicename = isset($servicenames[$_SERVER['HTTP_HOST']]) ? 'платформой ' . $servicenames[$_SERVER['HTTP_HOST']] : '';
        echo \strtr($doc->saveHTML(), ['$servicename' => $servicename]);
    } else {
        echo $answer ? 'Некорректный ответ сервиса' : 'Нет ответа от сервиса';
    }
}
echo '</div>';

if (\preg_match("/<Response id=\"([\d]+)\" status=\"([\d]+)\" datetime=\"[^\"]+\" result=\"([^\"]+)\" view=\"([^\"]+)\"/", $answer, $matches)) {
    $id = $matches[1];
    $status = $matches[2];
    $url = ('xml' == $_REQUEST['mode']) ? $matches[3] : $matches[4];
} else {
    $id = 0;
    $status = 1;
    $url = '';
}

echo '<input type="hidden" id="id" value="' . $id . '"/>';
echo '<input type="hidden" id="status" value="' . $status . '"/>';
echo '<input type="hidden" id="url" value="' . $url . '"/>';
