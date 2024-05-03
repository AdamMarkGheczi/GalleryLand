<?php

require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

session_start();


$templateArray = [
    'tempImages' => ['la.jpg', 'chicago.jpg', 'ny.jpg', 'logo.png', 'tall.jpg']
];

require_once 'pureScripts/GetSessionInfo.php';
GetInfoFromSession($templateArray);

try{
    require_once 'pureScripts/databaseCredentials.php';
    $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);

    $randomIdsQueryString =
    'SELECT
    	g.id as galleryId,
        u.id as ownerId,
    	username as ownerName,
        profile_picture as ownerPicture
    FROM
	    galleries g
    JOIN
	    users u
    ON
    	g.owner_id = u.id
    WHERE (
        SELECT
    	    COUNT(*)
        FROM
    	    photos
        WHERE
    	    gallery_id = g.id
        ) != 0
    ORDER BY RAND()
    LIMIT 4';

    $galleryInfo = mysqli_execute_query($conn, $randomIdsQueryString)->fetch_all(MYSQLI_ASSOC);

    foreach($galleryInfo as &$value)
    $value['ownerPicture'] = 'data:image/jpg;base64,'.base64_encode($value['ownerPicture']);

    $getGalleryPhotosQueryString =
    'SELECT 
        image
    FROM
        photos
    WHERE
        gallery_id = ?';
    
    $templateArray['featuredGalleries'] = [];

    foreach($galleryInfo as $row) {
        $stmt = $conn -> prepare($getGalleryPhotosQueryString);
        $stmt -> bind_param("s", $row['galleryId']);
        $stmt -> execute();

        $images = $stmt -> get_result() -> fetch_all(MYSQLI_ASSOC);

        
        foreach($images as &$value)
            $value = 'data:image/jpg;base64,'.base64_encode($value['image']);
    
        $gallery = [
            'id' => $row['galleryId'],
            'ownerId' => $row['ownerId'],
            'ownerName' => $row['ownerName'],
            'ownerPicture' => $row['ownerPicture'],
            'images' => $images
        ];

        array_push($templateArray['featuredGalleries'], $gallery);
    }


    $conn -> close();

}catch(Exception $e) {
    $templateArray['errorMessage'] = 'Could not load galleries, try again later!';
}


echo $twig->render('index.tpl.html', $templateArray);

