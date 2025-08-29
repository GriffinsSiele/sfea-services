<?php

/**
 * @global AuthorizationCheckerInterface $authorizationChecker
 * @global Kernel $kernel
 * @global PhpEngine $view
 * @global Request $request
 * @global SystemUser $user
 * @global UrlGeneratorInterface $urlGenerator
 */

use App\Controller\AdminController;
use App\Controller\DefaultController;
use App\Entity\AccessRoles;
use App\Entity\SystemUser;
use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\PhpEngine;

require_once 'xml.php';

$mainRequest = $request;

$view->extend('base.php');

$user_level = $user->getAccessLevel();
$user_sources = $user->getAccessSourcesMap();

// Источники (название,выбран,рекурсивный,конец строки)
$check_sources = [
    '2gis' => ['2ГИС', 1, 1, 1],
    'egrul' => ['ЕГРЮЛ', 1, 0, 0],
    'fns' => ['ФНС', 1, 0, 0],
//  'gks'=>array('Росстат',1,0,0),
    'bankrot' => ['Банкроты', 1, 0, 1],
    'cbr' => ['ЦБ РФ', 1, 0, 0],
//  'terrorist'=>array('Террористы',1,0,0),
//  'rz'=>array('Реестр залогов',1,0,0),
    'reestrzalogov' => ['Реестр залогов', 1, 0, 0],
    'rsa_org' => ['РСА КБМ', 0, 0, 1],
    'fssp' => ['ФССП', 1, 0, 0],
//  'fsspapi'=>array('ФССП (API)',1,0,0),
//  'fsspsite'=>array('ФССП (сайт)',1,0,0),
    'vestnik' => ['Вестник', 0, 0, 0],
//  'fedresurs'=>array('Федресурс',1,0,0),
//  'kad'=>array('Арбитражный суд',1,0,0),
    'zakupki' => ['Госзакупки', 1, 0, 1],
//  'rkn'=>array('Роскомнадзор',1,0,0),
//  'proverki'=>array('Проверки',1,0,1),
];

if (!isset($_REQUEST['inn'])) {
    $_REQUEST['inn'] = '';
}
if (!isset($_REQUEST['ogrn'])) {
    $_REQUEST['ogrn'] = '';
}
if (!isset($_REQUEST['name'])) {
    $_REQUEST['name'] = '';
}
if (!isset($_REQUEST['address'])) {
    $_REQUEST['address'] = '';
}
if (!isset($_REQUEST['region_id'])) {
    $_REQUEST['region_id'] = 0;
}
if (!isset($_REQUEST['bik'])) {
    $_REQUEST['bik'] = '';
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

$view['slots']->set('title', 'Проверка организации');

?>
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <form id="checkform" method="POST">
                    <div class="form-group row mb-3">
                        <label for="inn" class="col-sm-3 col-form-label">ИНН<span class="req">*</span></label>
                        <div class="col-sm-4">
                            <input autofocus class="form-control" type="text" id="inn" name="inn"
                                   value="<?= $_REQUEST['inn'] ?? ''; ?>"
                                   maxlength="20"/>
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
                                    <button class="btn btn-secondary btn-sm" type="button" id="selectall">Выбрать
                                        все
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
        <UserID>{$user->getId()}</UserID>
        <Password>{$user->getPassword()}</Password>"
    . (!isset($_REQUEST['request_id']) || !$_REQUEST['request_id'] ? '' : "
        <requestId>{$_REQUEST['request_id']}</requestId>"
    ) . '
        <requestType>checkorg</requestType>
        <sources>' . \implode(',', \array_keys($_REQUEST['sources'])) . '</sources>
        <timeout>' . $form_timeout . '</timeout>
        <recursive>' . ($_REQUEST['recursive'] ? '1' : '0') . '</recursive>
        <async>' . ($_REQUEST['async'] ? '1' : '0') . '</async>
        <OrgReq>'
    . (!$_REQUEST['inn'] ? '' : "
            <inn>{$_REQUEST['inn']}</inn>"
    ) . (!$_REQUEST['ogrn'] ? '' : "
            <ogrn>{$_REQUEST['ogrn']}</ogrn>"
    ) . (!$_REQUEST['name'] ? '' : "
            <name>{$_REQUEST['name']}</name>"
    ) . (!$_REQUEST['address'] ? '' : "
            <address>{$_REQUEST['address']}</address>"
    ) . (!$_REQUEST['region_id'] ? '' : "
            <region_id>{$_REQUEST['region_id']}</region_id>"
    ) . (!$_REQUEST['bik'] ? '' : "
            <bik>{$_REQUEST['bik']}</bik>"
    ) . '
        </OrgReq>
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
    try {
        $doc = xml_transform($answer, 'isphere_view.xslt');
    } catch (\Throwable $e) {
        if (str_contains($answer, 'Symfony Exception')) {
            echo $answer;
            return;
        } else {
            throw $e;
        }
    }
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
