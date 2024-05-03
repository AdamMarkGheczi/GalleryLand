<?php

session_start();

if(!($_SERVER['REQUEST_METHOD']  === 'POST' && isset($_SESSION['myUserId']))) {
    header('Location:../index.php');
    exit;
}

$type = htmlspecialchars(stripslashes(trim($_POST['uploadType'])));
$adminPrivilege = boolval($_SESSION['myAdmin']);

try{
    require_once 'databaseCredentials.php';
    $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);
    
    switch($type) {
        case 'profilePicture':
            $id = htmlspecialchars(stripslashes(trim($_POST['userId'])));
            
            if(!($adminPrivilege || $_SESSION['myUserId'] === intval($id))) {
                var_dump($id);
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $image = file_get_contents($_FILES['profilePicture']['tmp_name']);

            $stmt = $conn->prepare('UPDATE users SET profile_picture = ? where id = ?');
            $stmt -> bind_param('ss', $image, $id);
            $stmt -> execute();

            $_SESSION['myProfilePicture'] = 'data:image/jpg;base64,'.base64_encode($image);

            $conn -> close();
            header('Location:../profile.php?id='.$id);
            exit;
        break;

        case 'galleryPhoto':
            $galleryId = htmlspecialchars(stripslashes(trim($_POST['galleryId'])));
            $description = htmlspecialchars(stripslashes(trim($_POST['description'])));
            $title = htmlspecialchars(stripslashes(trim($_POST['title'])));

            $stmt = $conn->prepare('SELECT owner_id FROM galleries where id = ?');
            $stmt -> bind_param("s", $galleryId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            if(!($adminPrivilege || $row['owner_id'] === $_SESSION['myUserId'])) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }
            
            $images = $_FILES['photos']['tmp_name'];


            foreach($images as $value) {
                $image = file_get_contents($value);
                $stmt = $conn->prepare('INSERT INTO photos (`id`, `gallery_id`, `upload_date`, `title`, `description`, `image`) VALUES (NULL, ?, current_timestamp(), ?, ?, ?)');
                $stmt -> bind_param('ssss', $galleryId, $title, $description, $image);
                $stmt -> execute();
            }


            $conn -> close();
            header('Location:../fullGallery.php?id='.$galleryId);
            exit;
        break;

        case 'comment':
            $photoId = htmlspecialchars(stripslashes(trim($_POST['photoId'])));
            $userId = htmlspecialchars(stripslashes(trim($_POST['userId'])));
            $content = htmlspecialchars(stripslashes(trim($_POST['content'])));
            
            if($userId != $_SESSION['myUserId']) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $stmt = $conn->prepare('SELECT COUNT(*) as nr FROM photos where id = ?');
            $stmt -> bind_param("s", $photoId);
            $stmt -> execute();
            $row = $stmt -> get_result() -> fetch_assoc();
            
            if($row['nr'] !== 1) {
                $conn -> close();
                header('Location:../index.php');
                exit;
            }

            $stmt = $conn->prepare('INSERT INTO comments (`id`, `user_id`, `photo_id`, `post_date`, `text`) VALUES (NULL, ?, ?, current_timestamp(), ?)');
            $stmt -> bind_param('sss', $userId, $photoId, $content);
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
        case 'profilePicture':
            $id = htmlspecialchars(stripslashes(trim($_POST['userId'])));
            header('Location:../profile.php?id='.$id.'&pfpChangeError=stmtFailed');
            exit;
        break;
        case 'galleryPhoto':
            // var_dump($e);
            $galleryId = htmlspecialchars(stripslashes(trim($_POST['galleryId'])));
            header('Location:../fullGallery.php?id='.$galleryId.'&error=stmtFailed');
            exit;
        break;
    }
} catch (Error $error) {
    switch($type) {
        case 'profilePicture':
            header('Location:../profile.php?id='.$id.'&pfpChangeError=stmtFailed');
            exit;
        break;
        case 'galleryPhoto':
            // var_dump($error);
            $galleryId = htmlspecialchars(stripslashes(trim($_POST['galleryId'])));
            header('Location:../fullGallery.php?id='.$galleryId.'&error=stmtFailed');
            exit;
        break;
    }
}