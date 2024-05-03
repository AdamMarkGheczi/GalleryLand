<?php

session_start();

require_once 'vendor/autoload.php';

$templateArray = [];

require_once 'pureScripts/GetSessionInfo.php';

GetInfoFromSession($templateArray);

if(isset($_GET['editName']) && $_GET['editName'] === 'true') {
    $templateArray['editName'] = TRUE;
}

if(isset($_GET['deleteError'])) {
    switch($_GET['deleteError']) {
        case 'wrongPassword':
            $templateArray['deleteErrorMessage'] = "Wrong password";
            break;
        case 'stmtFailed':
            $templateArray['deleteErrorMessage'] = "Something went wrong, could not delete";
            break;
    }
}

if(isset($_GET['passwordChangeError'])) {
    switch($_GET['passwordChangeError']) {
        case 'wrongPassword':
            $templateArray['passwordChangeErrorMessage'] = "Wrong password";
            break;
        case 'dontMatch':
            $templateArray['passwordChangeErrorMessage'] = "New passwords doesn't match confirmation password";
            break;
        case 'stmtFailed':
            $templateArray['passwordChangeErrorMessage'] = "Something went wrong, could not change password";
            break;
        case 'badFormat':
            $templateArray['passwordChangeErrorMessage'] = "New password must have at least 6 characters";
            break;
        }
    }
    
if(isset($_GET['pfpChangeError'])) {
    switch($_GET['pfpChangeError']) {
        case 'stmtFailed':
            $templateArray['pfpChangeErrorMessage'] = "Something went wrong, could not update profile picture";
            break;
        case 'badformat':
            $templateArray['pfpChangeErrorMessage'] = "Only 1:1 aspect ratio images allowed or idk what to write here";
            break;
            
    }
}
if(isset($_GET['userChangeError'])) {
    switch($_GET['userChangeError']) {
        case 'usernameTaken':
            $templateArray['usernameChangeErrorMessage'] = "That username is taken";
            break;
        case 'stmtFailed':
            $templateArray['usernameChangeErrorMessage'] = "Something went wrong, could not update username";
        break;
            
    }
}

$templateArray['noUserFound'] = TRUE;

if (isset(($_GET['id']))) {

    try{
        require_once 'pureScripts/databaseCredentials.php';
        $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);
        $id = htmlspecialchars(stripslashes(trim($_GET['id'])));

        $stmt = $conn->prepare('SELECT * FROM users where id = ?');
        $stmt -> bind_param("s", $id);
        $stmt -> execute();
        $row = $stmt -> get_result() -> fetch_assoc();
    
        if ($row){
            $templateArray['noUserFound'] = FALSE;
            $templateArray['username'] = $row['username'];
            $templateArray['userId'] = $row['id'];
            $templateArray['regDate'] =  $row['regdate'];
            $templateArray['email'] =  $row['email'];
            $templateArray['admin'] =  $row['admin'];
            
            if($row['profile_picture'] === NULL) {
                $templateArray['profilePicture'] = 'images/defaultProfile.png';
            } else{
                $templateArray['profilePicture'] = 'data:image/jpg;base64,'.base64_encode($row['profile_picture']);
            }

        }
    
        $stmt = $conn->prepare('SELECT COUNT(*) as nrGalleries FROM galleries where owner_id = ?');
        $stmt -> bind_param("s", $id);
        $stmt -> execute();
        $row = $stmt -> get_result() -> fetch_assoc();    

        $templateArray['nrGalleries'] = $row['nrGalleries'];

        $stmt = $conn->prepare(
            'SELECT Count(*) as nrPhotos FROM 
            users as u join
            galleries as g
            on u.id = g.owner_id
            join photos as p
            on p.gallery_id = g.id
            where u.id = ?'
        );

        $stmt -> bind_param("s", $id);
        $stmt -> execute();
        $row = $stmt -> get_result() -> fetch_assoc(); 
        $templateArray['nrPhotos'] = $row['nrPhotos'];


        $stmt->close();
        $conn -> close();
    } catch (Exception $e) {}
}



$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
echo $twig->render('profilePage.tpl.html', $templateArray);
