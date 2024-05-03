<?php

session_start();

if(!($_SERVER['REQUEST_METHOD']  === 'POST' && isset($_SESSION['myUserId']))) {
    header('Location:../index.php');
    exit;
}

$type = htmlspecialchars(stripslashes(trim($_POST['updateType'])));
$adminPrivilege = boolval($_SESSION['myAdmin']);

try{
    require_once 'databaseCredentials.php';
    $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);
    
    switch($type) {
        case 'password':
            $id = htmlspecialchars(stripslashes(trim($_POST['userId'])));
            $password = htmlspecialchars(stripslashes(trim($_POST['oldPassword'])));
            
            $stmt = $conn->prepare('SELECT * FROM users where id = ?');
            $stmt -> bind_param("s", $id);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            if(!($adminPrivilege || password_verify($password, $row['password']))) {
                $conn -> close();
                header('Location:../profile.php?id='.$id.'&passwordChangeError=wrongPassword');
                exit;
            }
            
            $newPassword = htmlspecialchars(stripslashes(trim($_POST['newPassword'])));
            $newPasswordConfirm = htmlspecialchars(stripslashes(trim($_POST['newPasswordConfirm'])));

            if($newPassword !== $newPasswordConfirm) {
                $conn -> close();
                header('Location:../profile.php?id='.$id.'&passwordChangeError=dontMatch');
                exit;
            }
            
            if(strlen($newPassword) < 6) {
                $conn -> close();
                header('Location:../profile.php?id='.$id.'&passwordChangeError=badFormat');
                exit;    
            }

            $stmt = $conn->prepare('UPDATE users SET password = ? where id = ?');
            $stmt -> bind_param("ss", password_hash($newPassword, PASSWORD_DEFAULT), $id);
            $stmt -> execute();


            if(isset($_POST['returnUrl'])) {
                $conn -> close();
                $returnUrl = stripslashes(trim($_POST['returnUrl']));
                header('Location:../' . $returnUrl);
                exit;
            }

            $conn -> close();
            header('Location:../profile.php?id='.$id);
            exit;
        break;

        case 'galleryTitle':
            $galleryId = htmlspecialchars(stripslashes(trim($_POST['galleryId'])));
            $galleryTitle = htmlspecialchars(stripslashes(trim($_POST['galleryTitle'])));
            
            $stmt = $conn->prepare('SELECT owner_id FROM galleries where id = ?');
            $stmt -> bind_param("s", $galleryId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            if(!($adminPrivilege || $row['owner_id'] === $_SESSION['myUserId'])) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $stmt = $conn->prepare('UPDATE galleries SET title = ? where id = ?');
            $stmt -> bind_param("ss", $galleryTitle, $galleryId);
            $stmt -> execute();

            if(isset($_POST['returnUrl'])) {
                $conn -> close();
                $returnUrl = stripslashes(trim($_POST['returnUrl']));
                header('Location:../' . $returnUrl);
                exit;
            }
            
            $conn -> close();
            header('Location:../fullGallery.php?id=' . $galleryId);
            exit;
        break;

        case 'galleryDescription':
            $galleryId = htmlspecialchars(stripslashes(trim($_POST['galleryId'])));
            $galleryDescription = htmlspecialchars(stripslashes(trim($_POST['galleryDescription'])));
            
            $stmt = $conn->prepare('SELECT owner_id FROM galleries where id = ?');
            $stmt -> bind_param("s", $galleryId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            if(!($adminPrivilege || $row['owner_id'] === $_SESSION['myUserId'])) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $stmt = $conn->prepare('UPDATE galleries SET description = ? where id = ?');
            $stmt -> bind_param("ss", $galleryDescription, $galleryId);
            $stmt -> execute();

            if(isset($_POST['returnUrl'])) {
                $conn -> close();
                $returnUrl = stripslashes(trim($_POST['returnUrl']));
                header('Location:../' . $returnUrl);
                exit;
            }

            $conn -> close();
            header('Location:../fullGallery.php?id=' . $galleryId);
            exit;
        break;

        case 'username':
            $username = htmlspecialchars(stripslashes(trim($_POST['username'])));
            $userId = htmlspecialchars(stripslashes(trim($_POST['userId'])));

            if(!($adminPrivilege || $userId == $_SESSION['myUserId'])) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $stmt = $conn->prepare('SELECT COUNT(*) as matchingUsers FROM users where username = ? and not id = ?');
            $stmt -> bind_param("ss", $username, $userId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
        
            
            if($row['matchingUsers'] !== 0) {
                $conn -> close();
                if(isset($_POST['returnUrl'])) {
                    $returnUrl = stripslashes(trim($_POST['returnUrl'])); 
                    header('Location:../'. $returnUrl.'&userChangeError=usernameTaken');
                    exit;
                } else {
                    header('Location:../profile.php?id=' . $userId . '&userChangeError=usernameTaken');
                    exit;
                }  
            }
        
            $stmt = $conn->prepare('UPDATE users set username = ? where id = ?');
            $stmt -> bind_param("ss", $username, $userId);
            $stmt -> execute();
            
            if(!$adminPrivilege) $_SESSION['myUsername'] = $username;

            if(isset($_POST['returnUrl'])) {
                $conn -> close();
                $returnUrl = stripslashes(trim($_POST['returnUrl']));
                header('Location:../' . $returnUrl);
                exit;
            }

            $conn -> close();
            header('Location:../profile.php?id=' . $userId);
            exit;
        break;

        case 'photoTitle':
            $photoId = htmlspecialchars(stripslashes(trim($_POST['photoId'])));
            $photoTitle = htmlspecialchars(stripslashes(trim($_POST['photoTitle'])));
            
            $lookupQueryString =
            'SELECT
                owner_id
            FROM
                photos p
            JOIN
                galleries g on p.gallery_id = g.id
            where p.id = ?';

            $stmt = $conn->prepare($lookupQueryString);
            $stmt -> bind_param("s", $photoId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            if(!($adminPrivilege || $row['owner_id'] === $_SESSION['myUserId'])) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $stmt = $conn->prepare('UPDATE photos SET title = ? where id = ?');
            $stmt -> bind_param("ss", $photoTitle, $photoId);
            $stmt -> execute();

            $conn -> close();
            header('Location:../image.php?id='.$photoId);
            exit;
        break;

        case 'photoDescription':
            $photoId = htmlspecialchars(stripslashes(trim($_POST['photoId'])));
            $photoDescription = htmlspecialchars(stripslashes(trim($_POST['photoDescription'])));
            
            $lookupQueryString =
            'SELECT
                owner_id
            FROM
                photos p
            JOIN
                galleries g on p.gallery_id = g.id
            where p.id = ?';

            $stmt = $conn->prepare($lookupQueryString);
            $stmt -> bind_param("s", $photoId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            if(!($adminPrivilege || $row['owner_id'] === $_SESSION['myUserId'])) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $stmt = $conn->prepare('UPDATE photos SET description = ? where id = ?');
            $stmt -> bind_param("ss", $photoDescription, $photoId);
            $stmt -> execute();

            $conn -> close();
            header('Location:../image.php?id='.$photoId);
            exit;
        break;

        case 'comment':
            $commentId = htmlspecialchars(stripslashes(trim($_POST['commentId'])));
            $content = htmlspecialchars(stripslashes(trim($_POST['content'])));
            
            $lookupQueryString = 'SELECT user_id, photo_id FROM comments where id = ?';

            $stmt = $conn->prepare($lookupQueryString);
            $stmt -> bind_param("s", $commentId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            $photoId = $row['photo_id'];
            
            if(!($adminPrivilege || $row['user_id'] === $_SESSION['myUserId'])) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $stmt = $conn->prepare('UPDATE comments SET text = ? where id = ?');
            $stmt -> bind_param("ss", $content, $commentId);
            $stmt -> execute();

            $conn -> close();
            header('Location:../image.php?id='.$photoId);
            exit;
        break;
            
        default:
            $conn -> close();
            header('Location:../index.php');
            exit;
        break;
    }

} catch (Exception $e) {
    switch($type) {
        case 'password':
            if(isset($_POST['returnUrl'])) {
                $returnUrl = stripslashes(trim($_POST['returnUrl'])); 
                header('Location:../'. $returnUrl.'&passwordChangeError=stmtFailed');
                exit;
            } else {
                header('Location:../profile.php?id='.$id.'&passwordChangeError=stmtFailed');
                exit;
            } 

        break;
        case 'galleryTitle':
        case 'galleryDescription':
            $galleryId = htmlspecialchars(stripslashes(trim($_POST['galleryId'])));
            if(isset($_POST['returnUrl'])) {
                $returnUrl = stripslashes(trim($_POST['returnUrl']));
                header('Location:../' . $returnUrl);
                exit;
            }
            
            header('Location:../fullGallery.php?id=' . $galleryId);
            exit;
        break;

        case 'username' :
            if(isset($_POST['returnUrl'])) {
                $returnUrl = stripslashes(trim($_POST['returnUrl'])); 
                header('Location:../'. $returnUrl.'&userChangeError=stmtFailed');
                exit;
            } else {
                header('Location:../profile.php?id=' . $userId . '&userChangeError=stmtFailed');
                exit;
            } 
        break;

        case 'photoTitle' :
        case 'photoDescription' :
            $photoId = htmlspecialchars(stripslashes(trim($_POST['photoId'])));
            header('Location:../image.php?id='.$photoId.'&error=stmtFailed');
            exit;
        break;

        case 'comment':
            $returnUrl = trim($_POST['returnUrl']);
            header('Location:../' . $returnUrl . '&error=stmtFailed');
            exit;
        break;
    }
} catch (Error $error) {
    switch($type) {
        case 'password':
            if(isset($_POST['returnUrl'])) {
                $returnUrl = stripslashes(trim($_POST['returnUrl'])); 
                header('Location:../'. $returnUrl.'&passwordChangeError=stmtFailed');
                exit;
            } else {
                header('Location:../profile.php?id='.$id.'&passwordChangeError=stmtFailed');
                exit;
            } 

        break;
        case 'galleryTitle':
        case 'galleryDescription':
            $galleryId = htmlspecialchars(stripslashes(trim($_POST['galleryId'])));
            if(isset($_POST['returnUrl'])) {
                $returnUrl = stripslashes(trim($_POST['returnUrl']));
                header('Location:../' . $returnUrl);
                exit;
            }
            
            header('Location:../fullGallery.php?id=' . $galleryId);
            exit;
        break;

        case 'username' :
            if(isset($_POST['returnUrl'])) {
                $returnUrl = stripslashes(trim($_POST['returnUrl'])); 
                header('Location:../'. $returnUrl.'&userChangeError=stmtFailed');
                exit;
            } else {
                header('Location:../profile.php?id=' . $userId . '&userChangeError=stmtFailed');
                exit;
            } 
        break;

        case 'photoTitle' :
        case 'photoDescription' :
            $photoId = htmlspecialchars(stripslashes(trim($_POST['photoId'])));
            header('Location:../image.php?id='.$photoId.'&error=stmtFailed');
            exit;
        break;

        case 'comment':
            $returnUrl = trim($_POST['returnUrl']);
            header('Location:../' . $returnUrl . '&error=stmtFailed');
            exit;
        break;
    }
}
