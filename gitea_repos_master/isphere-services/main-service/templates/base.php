<?php

declare(strict_types=1);

/**
 * @global ContainerInterface $container
 * @global Helper $menu
 * @global PhpEngine $view
 * @global SystemUser $user
 */

use App\Entity\SystemUser;
use Knp\Menu\Twig\Helper;
use Psr\Container\ContainerInterface;
use Symfony\Component\Templating\PhpEngine;

?><!DOCTYPE html>
<html lang="ru" data-bs-theme="<?= $_COOKIE['data-bs-theme'] ?? null; ?>">
<head>
    <meta charset="<?= $view->getCharset(); ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>

    <title><?php
        $view['slots']->output('title', 'iSphere'); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ"
          crossorigin="anonymous"/>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css"/>

    <link rel="stylesheet" type="text/css" href="/main.css"/>
    <style type="text/css">
        html {
            font-size: 15px;
        }

        body {
            font-family: Ubuntu, sans-serif;
            margin: 0;
            padding: 0;
            background-attachment: fixed;
            background-color: var(--bs-body-bg);
            background-image: radial-gradient(var(--bs-secondary-bg) 1px, transparent 0);
            background-size: 15px 15px;
        }

        h1, h2, h3, h4, h5, h6, button, input[type=button], input[type=submit] {
            font-weight: 300 !important;
        }

        .root {
            display: grid;
            grid-template-columns: 200px auto;
            height: 100vh;
            overflow: hidden;
            gap: 0 1rem;
        }

        .aside {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: auto;
        }

        .main {
            display: flex;
            flex-direction: column;
            overflow: scroll;
        }

        .main-content {
            flex: 1;
            overflow: auto;
            padding-bottom: 1rem;
            padding-top: 1rem;
            margin-top: -1rem;
        }

        .checkers ul {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        .checkers a {
            --bs-card-bg: var(--bs-body-bg);
            --bs-card-border-radius: var(--bs-border-radius);
            --bs-card-border-color: var(--bs-border-color-translucent);
            --bs-card-border-width: var(--bs-border-width);
            --bs-card-color: ;

            align-items: center;
            background-clip: border-box;
            background-color: var(--bs-card-bg);
            background: var(--bs-card-bg);
            border-radius: var(--bs-card-border-radius);
            border: var(--bs-card-border-width) solid var(--bs-card-border-color);
            color: var(--bs-body-color);
            display: flex;
            height: 6rem;
            justify-content: center;
            text-decoration: none;
        }

        .page-title a {
            text-decoration: none;
        }

        form {
            --bs-card-spacer-y: 1rem;
            --bs-card-spacer-x: 1rem;
            --bs-card-bg: var(--bs-body-bg);
            --bs-card-border-radius: var(--bs-border-radius);
            --bs-card-border-color: var(--bs-border-color-translucent);
            --bs-card-border-width: var(--bs-border-width);

            background: var(--bs-card-bg);
            border-radius: var(--bs-card-border-radius);
            border: var(--bs-card-border-width) solid var(--bs-card-border-color);
            padding: var(--bs-card-spacer-y) var(--bs-card-spacer-x);
        }

        <?php if ($container->getParameter('kernel.debug')) { ?>
        .sf-toolbar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }

        <?php } ?>

        .header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
        }

        .header h1 {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .container-fluid + .container-fluid {
            margin-top: 1rem;
        }

        #request > .container-fluid,
        #response > .container-fluid {
            margin-top: 1rem;
        }

        button .fa,
        button [data-fa-i2svg] {
            margin-right: .5ex;
        }

        .logo {
            display: block;
            width: 200px;
            filter: invert(1) grayscale(1);
        }

        html[data-bs-theme="dark"] .logo {
            filter: opacity(.75) grayscale(1);
        }

        .history {
            height: 100%;
            overflow: hidden;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .history .container-fluid + .container-fluid {
            overflow: auto;
        }

        .hljs {
            background: transparent;
            border-radius: .5rem;
            padding: 0;
        }
    </style>
</head>
<body>
<div class="<?php
$view['slots']->output('root-container-class', 'container'); ?>">
    <div class="root">
        <aside class="aside">
            <h2 class="page-title mb-4" style="padding-top: .75rem;">
                <a href="/" class="text-body">
                    <!--                    ИНФОСФЕРА-->
                    <img src="https://static.tildacdn.com/tild6438-6237-4936-a562-316261356535/logo_for_header.png"
                         alt="Инфосфера" class="logo"/>
                </a>
            </h2>

            <?= $menu->render('main', ['allow_safe_labels' => true, 'currentAsLink' => false]); ?>
        </aside>

        <main class="main">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="header mb-4 pt-3">
                            <h1>
                                <?php
                                $view['slots']->output('title', ''); ?>
                            </h1>

                            <div>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <?= $view->escape($user->getUserIdentifier()); ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="/logout.php">Выход</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-content">
                <?php
                if (($message = $user->getMessage())) { ?>
                    <div class="alert alert-danger">
                        <?= $view->escape($message->getText()); ?>
                    </div>
                    <?php
                } ?>

                <article class="<?php
                $view['slots']->output('article-class', ''); ?>">
                    <?php
                    $view['slots']->output('_content'); ?>
                </article>
            </div>
        </main>
    </div>
</div>

<?php
if (!$container->getParameter('kernel.debug')) {
    require 'metrika.html';
    require 'jivosite.html';
    require 'reformal.html';
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"
        integrity="sha512-pumBsjNRGGqkPzKHndZMaAG+bir374sORyzM3uulLV14lN5LyykqNk8eEeUlUkB3U0M4FApyaHraT65ihJhDpQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.1/js/bootstrap.bundle.min.js"
        integrity="sha512-sH8JPhKJUeA9PWk3eOcOl8U+lfZTgtBXD41q6cO/slwxGHCxKcW45K4oPCUhHG7NMB4mbKEddVmPuTXtpbCbFA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
<script src="https://kit.fontawesome.com/3141174b1b.js" crossorigin="anonymous" data-auto-replace-svg="nest"></script>
<?php
$view['slots']->output('javascripts', '<script src="/main.js"></script>'); ?>
<script>
  const THEME_DARK = 'dark'
  const THEME_LIGHT = 'light'

  function handleMediaChange ({ matches }) {
    const theme = matches ? THEME_DARK : THEME_LIGHT

    document.querySelector('html').setAttribute('data-bs-theme', theme)
    document.cookie = `data-bs-theme=${theme}; path=/`
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!window.matchMedia) {
      return
    }

    const matcher = window.matchMedia('(prefers-color-scheme: dark)')
    matcher.addEventListener('change', handleMediaChange)
    handleMediaChange(matcher)
  })
</script>
</body>
</html>
