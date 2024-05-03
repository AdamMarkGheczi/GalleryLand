<?php

session_start();

require_once 'vendor/autoload.php';
$templateArray = [];

require_once 'pureScripts/GetSessionInfo.php';

GetInfoFromSession($templateArray);

$count = 5;

$owner = '';
$page = 1;

try{
    if (isset($_GET['owner'])) $owner = htmlspecialchars(stripslashes(trim($_GET['owner'])));
    $queryStringCondition = $owner !== '' ? 'WHERE ownerId = ?' : '';

    if (isset($_GET['page'])) $page = htmlspecialchars(stripslashes(trim($_GET['page'])));
    $offset = ($page - 1) * $count;
    
    if (isset($_GET['editDescription'])) {
        $editDescription = htmlspecialchars(stripslashes(trim($_GET['editDescription'])));
        $templateArray['editDescription'] = $editDescription;
    }
    if (isset($_GET['editTitle'])) {
        $editTitle = htmlspecialchars(stripslashes(trim($_GET['editTitle'])));
        $templateArray['editTitle'] = $editTitle;
    }

    $queryString =
    "SELECT * FROM
        (
            SELECT
                g.id as id,
                g.title as title,
                g.owner_id as ownerId,
                u.username as ownerName,
                u.profile_picture as ownerPicture,
                g.creation_date as creationDate,
                g.description as description,
                p.image as thumbnail
            FROM
                galleries as g
            JOIN
                users as u on g.owner_id = u.id
            JOIN
                photos as p on p.gallery_id = g.id
            JOIN (
                SELECT 
                    gallery_id, 
                    MIN(id) AS first_id
                FROM 
                    photos
                GROUP BY 
                    gallery_id
            ) as fp on g.id = fp.gallery_id and p.id = fp.first_id
            UNION
            SELECT
                g.id,
                g.title,
                g.owner_id as ownerId,
                u.username as ownerName,
                u.profile_picture as ownerPicture,
                g.creation_date,
                g.description,
                p.image
            FROM
                galleries as g
            JOIN
                users as u on g.owner_id = u.id
            LEFT JOIN
                photos as p on g.id = p.gallery_id
            WHERE p.image is NULL
        ) as galleryInfo
        $queryStringCondition
        ORDER BY creationDate DESC
        LIMIT $count OFFSET $offset;";


    require_once 'pureScripts/databaseCredentials.php';
    $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);

    if($owner !== '') {
        $stmt = $conn->prepare($queryString);
        $stmt -> bind_param("s", $owner);
        $stmt -> execute();
        $galleryArray = $stmt -> get_result() -> fetch_all(MYSQLI_ASSOC);
        $stmt -> close();

        $getOwnerName = $conn -> prepare("SELECT username FROM users where id = ?");
        $getOwnerName -> bind_param("s", $owner);
        $getOwnerName -> execute();

        $ownerName = $getOwnerName -> get_result() -> fetch_assoc()['username'];
        $templateArray['ownerName'] = $ownerName;
        $templateArray['ownerId'] = $owner;
        $getOwnerName -> close();

        $userGalleries = $conn -> prepare("SELECT COUNT(*) as nr FROM galleries WHERE owner_id = ?");
        $userGalleries -> bind_param("s", $owner);
        $userGalleries -> execute();
        $totalGalleries = intval($userGalleries -> get_result() -> fetch_assoc()['nr']);

    } else {
        $galleryArray = $conn -> query($queryString) -> fetch_all(MYSQLI_ASSOC);
        $totalGalleries = intval($conn -> query("SELECT COUNT(*) as nr FROM galleries") -> fetch_assoc()['nr']);
    }

    $conn -> close();

    foreach($galleryArray as &$gallery){
        $gallery['thumbnail'] = $gallery['thumbnail'] === NULL ? 'images/noimages.jpg' : 'data:image/jpg;base64,'.base64_encode($gallery['thumbnail']);
        $gallery['ownerPicture'] = $gallery['ownerPicture'] === NULL ? 'images/defaultProfile.png' : 'data:image/jpg;base64,'.base64_encode($gallery['ownerPicture']);
    }


    $totalPages = ceil($totalGalleries/$count);
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
    // var_dump($e);
    $templateArray['errorMessage'] = 'Something went wrong, try again later!';
} catch (Error $error) {
    // var_dump($error);
}


$templateArray['galleries'] = $galleryArray;


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
echo $twig->render('galleryList.tpl.html', $templateArray);