<?php

require_once 'vendor/autoload.php';

session_start();

$templateArray = [];

require_once 'pureScripts/GetSessionInfo.php';

GetInfoFromSession($templateArray);

$galleryNotFound = FALSE;
if(isset($_GET['editTitle']) && $_GET['editTitle'] == True) $templateArray['titleEditingEnabled'] = TRUE;
if(isset($_GET['editDescription']) && $_GET['editDescription'] == True) $templateArray['descriptionEditingEnabled'] = TRUE;

if(isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'stmtFailed':
            $templateArray['errorMessage'] = "Something went wrong, please try again later";
            break;

        case 'none':
            $templateArray['errorMessage'] = "Deletion successful!";
            $templateArray['successMessage'] = TRUE;
            break;
        }
    }

if(!isset($_GET['id'])) $galleryNotFound = TRUE;
else{
    try{
        require_once 'pureScripts/databaseCredentials.php';
        $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);
        
        $galleryId = htmlspecialchars(stripslashes(trim($_GET['id'])));
    
        $stmt = $conn->prepare(
            "SELECT owner_id as ownerId, creation_date as creationDate, title as galleryTitle, description, username as ownerName
            FROM galleries as g
            join users as u on
            g.owner_id = u.id
            where g.id = ?;"
        );

        $stmt -> bind_param("s", $galleryId);
        $stmt -> execute();
        $row = $stmt -> get_result() -> fetch_assoc();
    
        if($row){
            $templateArray['galleryId'] = $galleryId;
            $templateArray['ownerId'] = $row['ownerId'];
            $templateArray['ownerName'] = $row['ownerName'];
            $templateArray['creationDate'] = $row['creationDate'];
            $templateArray['galleryTitle'] =  $row['galleryTitle'];
            $templateArray['description'] =  $row['description'];
        }else{
            $galleryNotFound = TRUE;
        }

        $stmt = $conn->prepare("SELECT id, title, image FROM photos where gallery_id = ? ORDER by id DESC;");

        $stmt -> bind_param("s", $galleryId);
        $stmt -> execute();
        $imageArray = $stmt -> get_result() -> fetch_all(MYSQLI_ASSOC);

        $templateArray['photos'] = $imageArray;

        foreach($templateArray['photos'] as &$value)
            $value['image'] = 'data:image/jpg;base64,'.base64_encode($value['image']);
        
        $stmt->close();
        $conn -> close();
    } catch (Exception $e) {
        $galleryNotFound = TRUE;
        $stmt->close();
        $conn -> close();
    }catch (Error $error) {
        $galleryNotFound = TRUE;
    }
}



$templateArray['galleryNotFound'] = $galleryNotFound;

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
echo $twig->render('fullGallery.tpl.html', $templateArray);



