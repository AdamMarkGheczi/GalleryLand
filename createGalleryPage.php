<?php

require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

session_start();

$templateArray = [];

require_once 'pureScripts/GetSessionInfo.php';
GetInfoFromSession($templateArray);

if($templateArray['notLoggedIn']) {
    header('Location:loginForm.php');
    exit;
}

$errorMessage = '';

if(isset($_GET['error'])) {
    switch($_GET['error']){
        case 'stmtFailed' :
            $errorMessage = 'Something went wrong, try again later!';
        break;
    }

}

if($errorMessage !== '') $templateArray['errorMessage'] = $errorMessage;

echo $twig->render('createGallery.tpl.html', $templateArray);