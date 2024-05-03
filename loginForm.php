<?php

require_once 'vendor/autoload.php';

$errorMsg = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'badCredentials':
            $errorMsg = 'Incorrect username or password!';
        break;

        case 'stmtFailed':
            $errorMsg = 'Something went wrong, try again later!';
        break;
    }
}


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
echo $twig->render('login.tpl.html', ['errorMsg' => $errorMsg, 'removeProfileDropdown' => TRUE]);

