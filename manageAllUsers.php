<?php

use Twig\NodeVisitor\SafeAnalysisNodeVisitor;

session_start();

require_once 'vendor/autoload.php';
$templateArray = [];

require_once 'pureScripts/GetSessionInfo.php';

GetInfoFromSession($templateArray);

// var_dump($_SESSION);
if($_SESSION['myAdmin'] !== 1) {
    header('Location:index.php');
    exit;
}

$count = 5;

$owner = '';
$page = 1;

if (isset($_GET['userChangeError'])) {
    $error = htmlspecialchars(stripslashes(trim($_GET['userChangeError'])));
    switch ($error) {
        case 'usernameTaken' :
            $templateArray['errorMessage'] = 'Username already taken';
        break;
        case 'stmtFailed' :
            $templateArray['errorMessage'] = 'Something went wrong, please try again later';
        break;
    }
}
if (isset($_GET['passwordChangeError'])) {
    $error = htmlspecialchars(stripslashes(trim($_GET['passwordChangeError'])));
    switch ($error) {
        case 'badFormat' :
            $templateArray['errorMessage'] = 'Password must have at least 6 characters';
        break;
        case 'dontMatch' :
            $templateArray['errorMessage'] = 'Passwords have to match';
        break;
        case 'stmtFailed' :
            $templateArray['errorMessage'] = 'Something went wrong, please try again later';
        break;
    }
}

if (isset($_GET['deleteError'])) {
    $error = htmlspecialchars(stripslashes(trim($_GET['passwordChangeError'])));
    switch ($error) {
        case 'stmtFailed' :
            $templateArray['errorMessage'] = 'Something went wrong, please try again later';
        break;
    }
}


if (isset($_GET['editUsername'])) {
    $editUsername = htmlspecialchars(stripslashes(trim($_GET['editUsername'])));
    $templateArray['editUsername'] = $editUsername;
}

try{
    if (isset($_GET['page'])) $page = htmlspecialchars(stripslashes(trim($_GET['page'])));
    $offset = ($page - 1) * $count;
    
    require_once 'pureScripts/databaseCredentials.php';
    $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);
    
    $queryString =
    "SELECT
        id,
        username,
        email,
        regdate,
        admin,
        profile_picture as profilePicture,
        (SELECT COUNT(*) from galleries where owner_id = users.id) as nrGalleries,
        (SELECT Count(*)
        FROM 
            users as u
        join
            galleries as g on u.id = g.owner_id
        join
            photos as p on p.gallery_id = g.id
        where u.id = users.id
        ) as nrPhotos
     FROM
        users
     LIMIT $count  offset $offset;";

    $userArray = $conn -> query($queryString) -> fetch_all(MYSQLI_ASSOC);

    $totalUsers = intval($conn -> query("SELECT COUNT(*) as nr FROM users") -> fetch_assoc()['nr']);
    $conn -> close();
    
    foreach($userArray as &$user)
        $user['profilePicture'] = $user['profilePicture'] === NULL ? 'images/defaultprofile.png' : 'data:image/jpg;base64,'.base64_encode($user['profilePicture']);

    unset($user);

    $templateArray['users'] = $userArray;

    $totalPages = ceil($totalUsers/$count);
    if($totalPages === 0) $totalPages = 1;
    
    $paginationArray = [
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'neighbourPages' => []
    ];

    $start = $page - 2;
    $startDiff = 1 - $start;
    $end = $page + 2;
    $endDiff = $end - $totalPages;

    if($startDiff > 0 && !($endDiff >0 )) $end += $startDiff;
    if($endDiff > 0 && !($startDiff >0 )) $start -= $endDiff;
    
    $start = Max($start, 1);
    $end = Min($end, $totalPages);

    for($i = $start; $i <= $end; $i++) array_push($paginationArray['neighbourPages'], $i);

    
    $templateArray['paginationArray'] = $paginationArray;

} catch (Exception $e) {
    var_dump($e);
    $templateArray['errorMessage'] = 'Something went wrong, try again later!';
} catch (Error $error) {
    var_dump($error);
}


$templateArray['galleries'] = [];



$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
echo $twig->render('manageAllUsers.tpl.html', $templateArray);