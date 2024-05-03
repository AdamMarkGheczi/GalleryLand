<?php

session_start();

if(!($_SERVER['REQUEST_METHOD']  === 'POST' && isset($_SESSION['myUserId']))) {
    header('Location:../index.php');
    exit;
}

try{
    require_once 'databaseCredentials.php';
    $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);
    
    $ownerId = htmlspecialchars(stripslashes(trim($_SESSION['myUserId'])));
    $galleryTitle = htmlspecialchars(stripslashes(trim($_POST['galleryTitle'])));
    $galleryDescription = htmlspecialchars(stripslashes(trim($_POST['galleryDescription'])));

    $stmt = $conn->prepare(
        "INSERT INTO `galleries` (`id`, `owner_id`, `creation_date`, `title`, `description`) VALUES (NULL, ?, current_timestamp(), ?, ?);"
    );

    $stmt -> bind_param("sss", $ownerId, $galleryTitle, $galleryDescription);
    $stmt -> execute();

    $result = mysqli_execute_query($conn, 'SELECT LAST_INSERT_ID() as galleryId');
    $galleryId = $result->fetch_assoc()['galleryId'];

    $conn -> close();
    header('Location:../fullGallery.php?id=' . $galleryId);
    exit;

} catch (Exception $e) {
    header('Location:../createGalleryPage.php?' . '&error=stmtFailed');
    exit;
}
