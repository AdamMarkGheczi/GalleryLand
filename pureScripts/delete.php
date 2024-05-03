<?php

session_start();

if(!($_SERVER['REQUEST_METHOD']  === 'POST' && isset($_SESSION['myUserId']))) {
    header('Location:../index.php');
    exit;
}
    
$type = htmlspecialchars(stripslashes(trim($_POST['deleteType'])));
$adminPrivilege = boolval($_SESSION['myAdmin']);

try{
    require_once 'databaseCredentials.php';
    $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);
    
    switch($type) {
        case 'user':
            $id = htmlspecialchars(stripslashes(trim($_POST['userId'])));
            $password = htmlspecialchars(stripslashes(trim($_POST['password'])));
            
            $stmt = $conn->prepare('SELECT * FROM users where id = ?');
            $stmt -> bind_param("s", $id);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();

            if(!($adminPrivilege || password_verify($password, $row['password']))) {
                $conn -> close();
                header('Location:../profile.php?id='.$id.'&deleteError=wrongPassword');
                exit;
            }

            $stmt = $conn->prepare('DELETE FROM users where id = ?');
            $stmt -> bind_param("s", $id);
            $stmt -> execute();

            if(!$adminPrivilege) {
                session_unset();
                session_destroy();
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $conn -> close();
            header('Location:' . $_SERVER['HTTP_REFERER']);
            exit;
        break;
        case 'gallery':
            $galleryId = htmlspecialchars(stripslashes(trim($_POST['galleryId'])));
            
            $stmt = $conn->prepare('SELECT owner_id FROM galleries where id = ?');
            $stmt -> bind_param("s", $galleryId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            if(!($adminPrivilege || $row['owner_id'] === $_SESSION['myUserId'])) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $stmt = $conn->prepare('DELETE FROM galleries where id = ?');
            $stmt -> bind_param("s", $galleryId);
            $stmt -> execute();

            if(isset($_POST['returnUrl'])) {
                $conn -> close();
                $returnUrl = stripslashes(trim($_POST['returnUrl']));
                header('Location:../' . $returnUrl);
                exit;
            }

            $conn -> close();
            header('Location:' . $_SERVER['HTTP_REFERER']);
            exit;
        break;
        case 'photo':
            $photoId = htmlspecialchars(stripslashes(trim($_POST['photoId'])));
            
            $lookupQueryString =
            'SELECT
                gallery_id,
                owner_id
            FROM
                photos p
            JOIN
                galleries g on p.gallery_id = g.id
            where p.id = ?
            ';

            $stmt = $conn->prepare($lookupQueryString);
            $stmt -> bind_param("s", $photoId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            if(!($adminPrivilege || $row['owner_id'] === $_SESSION['myUserId'])) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $galleryId = $row['gallery_id'];

            $stmt = $conn->prepare('DELETE FROM photos where id = ?');
            $stmt -> bind_param("s", $photoId);
            $stmt -> execute();

            $conn -> close();
            header('Location:../fullGallery.php?id='.$galleryId);
            exit;
        break;
        case 'comment':
            $commentId = htmlspecialchars(stripslashes(trim($_POST['commentId'])));
            
            $lookupQueryString = 'SELECT user_id, photo_id FROM comments where id = ?';

            $stmt = $conn->prepare($lookupQueryString);
            $stmt -> bind_param("s", $commentId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            $photoId = $row['photo_id'];

            var_dump($row);

            if(!($adminPrivilege || $row['user_id'] === $_SESSION['myUserId'])) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }


            $stmt = $conn->prepare('DELETE FROM comments where id = ?');
            $stmt -> bind_param("s", $commentId);
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
        case 'user':
            $id = htmlspecialchars(stripslashes(trim($_POST['userId'])));
            header('Location:../profile.php?id='.$id.'&deleteError=stmtFailed');
            exit;
        break;
        case 'gallery':
            $galleryId = htmlspecialchars(stripslashes(trim($_POST['galleryId'])));
            header('Location:../fullGallery.php?id='.$galleryId.'&error=stmtFailed');
            exit;
        break;
        case 'photo':
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
} catch(Error $error) {
    switch($type) {
        case 'user':
            $id = htmlspecialchars(stripslashes(trim($_POST['userId'])));
            header('Location:../profile.php?id='.$id.'&deleteError=stmtFailed');
            exit;
        break;
        case 'gallery':
            $galleryId = htmlspecialchars(stripslashes(trim($_POST['galleryId'])));
            header('Location:../fullGallery.php?id='.$galleryId.'&error=stmtFailed');
            exit;
        break;
        case 'photo':
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