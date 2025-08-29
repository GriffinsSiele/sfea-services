<?php

/**
 * @global PhpEngine $view
 * @global Helper $menu
 */
declare(strict_types=1);

use Knp\Menu\Twig\Helper;
use Symfony\Component\Templating\PhpEngine;

$view->extend('base.php');

$view['slots']->set('title', 'Поиск в общедоступных источниках');

?>

<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="checkers">
                <?= $menu->render('checkers', ['allow_safe_labels' => true]); ?>
            </div>
        </div>
    </div>
</div>
