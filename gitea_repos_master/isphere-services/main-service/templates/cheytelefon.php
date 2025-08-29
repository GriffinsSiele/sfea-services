<?php

use App\Controller\AdminController;
use App\Controller\CheyController;
use App\Entity\AccessRoles;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

require_once 'xml.php';

$mainRequest = $request;

$view->extend('base.php');

\set_time_limit($total_timeout + $http_timeout + 20);

$view['slots']->set('title', 'Чей телефон');
//$view['slots']->set('javascripts', '');

$_REQUEST['async'] ??= '1';
$_REQUEST['mode'] ??= 'html';

// Источники (название,выбран,рекурсивный,конец строки)
$check_sources = [
    'rossvyaz' => ['Россвязь', 1, 1, 1],
    'facebook' => ['Facebook', 1, 1, 0],
    'vk' => ['VK', 1, 1, 0],
    'ok' => ['OK', 1, 1, 0],
    'instagram' => ['Instagram', 1, 1, 1],
    'announcement' => ['Объявления', 1, 1, 0],
    'boards' => ['Boards', 1, 1, 1],
    'commerce' => array('Commerce', 1, 1, 0),
    'skype' => ['Skype', 1, 1, 0],
    'viber' => ['Viber', 1, 1, 1],
    'whatsapp' => ['WhatsApp', 1, 1, 0],
    'getcontactweb' => ['GetContact', 1, 1, 0],
    'getcontact' => ['GetContact', 1, 1, 0],
    'numbuster' => ['NumBuster', 1, 1, 1],
    'emt' => ['EmobileTracker', 1, 1, 1],
    'truecaller' => ['TrueCaller', 1, 1, 0],
    'callapp' => ['CallApp', 1, 1, 0],
    'infobip' => ['Infobip', 1, 1, 0],
];

$_REQUEST['sources'] ??= [];

foreach ($check_sources as $k => $s) {
    if (/* ($user_level<0) || */
        isset($user_sources[$k]) && $user_sources[$k]) {
        if (!isset($_REQUEST['mode']) && !isset($_REQUEST['sources'][$k])) {
            $_REQUEST['sources'][$k] = $s[1];
        }
        //        if ($_REQUEST['recursive'] && $s[2]) $_REQUEST['sources'][$k] = 1;
    }
}

?>
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <form id="checkform" method="POST" data-no-ajax>
                <div class="form-group row mb-3">
                    <label for="phone" class="col-sm-3 col-form-label">
                        Номер телефона<span class="req">*</span>
                    </label>
                    <div class="col-sm-4">
                        <input type="text" name="phone" value="<?php echo $_REQUEST['phone'] ?? ''; ?>" required="1"
                               maxlength="20" id="phone" autofocus class="form-control"/>
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
                    <label for="mode" class="col-sm-3 col-form-label">Формат ответа:</label>
                    <div class="col-sm-4">
                        <select class="form-select" name="mode" id="mode">
                            <option value="xml" <?= $_REQUEST['mode'] == 'xml' ? 'selected' : '' ?>>XML</option>
                            <option value="html" <?= $_REQUEST['mode'] == 'html' ? 'selected' : '' ?>>HTML</option>
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

if (!isset($_REQUEST['mode'])) {
    echo '<div id="request">';
    echo '</div>';
    echo '<div id="response">';
    echo '</div>';

    return;
}

if (!isset($_REQUEST['phone'])) {
    return;
}

$post = [
    'phone' => $_REQUEST['phone'],
    'userid' => $user->getUserIdentifier(),
    'password' => $user->getPassword(),
    'mode' => $_REQUEST['mode'],
    'type' => ($_REQUEST['extended'] ?? false) ? 'extended' : '',
    'sources' => array_filter($_REQUEST['sources'] ?? [], fn($v) => !empty($v)),
];

$subRequest = Request::create(
    $urlGenerator->generate(CheyController::NAME),
    Request::METHOD_POST,
    content: json_encode($post),
);
$subRequest->attributes->set('_controller', CheyController::class);
$subRequest->setSession($mainRequest->getSession());
$response = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
$answer = $response->getContent();

?>
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <div class="card-text">
                        <?php
                        if ('html' != $_REQUEST['mode']) {
                            echo '<textarea class="form-control" rows="10"">';
                        }
                        echo $answer;
                        if ('html' != $_REQUEST['mode']) {
                            echo '</textarea>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

