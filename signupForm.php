<?php

require_once 'vendor/autoload.php';

$errorMsg = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'stmtFailed':
            $errorMsg = 'Something went wrong, try again later!';
        break;
        case 'badFormat':
            $errorMsg = 'Password must have at least 6 characters!';
        break;
        case 'dontMatch':
            $errorMsg = 'The supplied passwords do not match';
        break;
        case 'usernameTaken':
            $errorMsg = 'There already is a registered a user with this username';
        break;
        case 'emailTaken':
            $errorMsg = 'There already is a registered a user with this email address!';
        break;

    }
}


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

echo $twig->render('signup.tpl.html', ['errorMsg' => $errorMsg]);