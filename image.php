<?php

require_once 'vendor/autoload.php';
require_once 'pureScripts/getSessionInfo.php';

$templateArray = [];
session_start();
GetInfoFromSession($templateArray);

if(isset($_GET['error'])) $templateArray['errorMessage'] = "Something went wrong, try again later!";
if(isset($_GET['editDescription'])) $templateArray['editDescription'] = $_GET['editDescription'];
if(isset($_GET['editTitle'])) $templateArray['editTitle'] = $_GET['editTitle'];
if(isset($_GET['editComment'])) $templateArray['editComment'] = htmlspecialchars(stripslashes(trim($_GET['editComment'])));

try {
    require_once 'pureScripts/databaseCredentials.php';
    $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);

    $galleryQueryString =
    'SELECT
        p.id,
        p.upload_date as uploadDate,
        p.title as imageTitle,
        p.image,
        p.description,
        g.id as galleryId,
        g.owner_id as ownerId,
        u.username as ownerName,
        g.title as galleryTitle
    FROM
        photos p
    JOIN
        galleries g ON p.gallery_id = g.id
    JOIN
        users u on g.owner_id = u.id
    WHERE p.id = ?;';

    if(!isset($_GET['id'])) throw new Exception('No id provided');

    $photoId =  htmlspecialchars(stripslashes(trim($_GET['id'])));

    $stmt = $conn->prepare($galleryQueryString);
    $stmt -> bind_param("s", $photoId);
    $stmt -> execute();
    $photoInfo = $stmt -> get_result() -> fetch_assoc();

    if($photoInfo == NULL) throw new Exception("No image found");
    
    $photoInfo['image'] = 'data:image/pngjpg;base64,'.base64_encode($photoInfo['image']);
    
    $templateArray['photo'] = $photoInfo;

    $prevImageQueryString = 'SELECT id from photos where gallery_id = ? and id > ? ORDER BY id ASC LIMIT 1';
    $nextImageQueryString = 'SELECT id from photos where gallery_id = ? and id < ? ORDER BY id DESC LIMIT 1';

    $prevStmt = $conn->prepare($prevImageQueryString);
    $prevStmt -> bind_param("ss", $photoInfo['galleryId'], $photoId);
    $prevStmt -> execute();
    $prevPhotoId = $prevStmt -> get_result() -> fetch_assoc();
    
    $nextStmt = $conn->prepare($nextImageQueryString);
    $nextStmt -> bind_param("ss", $photoInfo['galleryId'], $photoId);
    $nextStmt -> execute();
    $nextPhotoId = $nextStmt -> get_result() -> fetch_assoc();
    
    
    $prevId = $prevPhotoId == NULL ? -1 : $prevPhotoId['id'];
    $nextId = $nextPhotoId == NULL ? -1 : $nextPhotoId['id'];
    
    $templateArray['prevId'] = $prevId;
    $templateArray['nextId'] = $nextId;
    
    $commentQueryString =
    'SELECT
        c.id,
        u.id as ownerId,
        u.profile_picture as ownerPicture,
        u.username as ownerName,
        c.post_date as postDate,
        c.text as content
    FROM
        comments c
    JOIN
        users u
    on u.id = c.user_id
    WHERE 
        photo_id = ?
    ORDER BY
        post_date DESC;';

    $getCommentsSTMT = $conn -> prepare($commentQueryString);
    $getCommentsSTMT -> bind_param("s", $photoId);
    $getCommentsSTMT -> execute();
    $commentsArray = $getCommentsSTMT -> get_result() -> fetch_all(MYSQLI_ASSOC);

    foreach($commentsArray as &$comment)
        $comment['ownerPicture'] = $comment['ownerPicture'] === NULL ? 'images/defaultprofile.png' : 'data:image/jpg;base64,'.base64_encode($comment['ownerPicture']);

    $templateArray['comments'] = $commentsArray;

    $conn -> close();

}catch(Exception $e) {
    // var_dump($e);
}catch(Error $error) {
    // var_dump($error);
}


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
echo $twig->render('image.tpl.html', $templateArray);