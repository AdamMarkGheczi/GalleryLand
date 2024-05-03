<?php
    
if (!isset($_POST['submit'])) {
    header('Location: index.php');
    exit;
}

require_once 'databaseCredentials.php';

try{
    $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);
    
    $username = htmlspecialchars(stripslashes(trim($_POST['username'])));
	$password = htmlspecialchars(stripslashes(trim($_POST['password'])));

    $stmt = $conn->prepare('SELECT * FROM users where username = ?');
    $stmt -> bind_param("s", $username);
    $stmt -> execute();
    $row = $stmt -> get_result() -> fetch_assoc();

    if ($row && password_verify($password, $row['password'])){
        // Reset session
        session_start();
        session_unset();
        session_destroy();

		session_start();
		$_SESSION['myUsername'] = $username;
		$_SESSION['myUserId'] = $row['id'];
        $_SESSION['myAdmin'] = $row['admin'];

        
        if($row['profile_picture'] === NULL) {
            $_SESSION['myProfilePicture'] = 'images/defaultProfile.png';
        } else{
            $_SESSION['myProfilePicture'] = 'data:image/jpg;base64,'.base64_encode($row['profile_picture']);
        }

        $conn -> close();
		Header("Location:../index.php");
        exit;
	} else {
        $conn -> close();
        header('Location: ../loginForm.php?error=badCredentials');
        exit;
	}
} catch (Exception $e) {
    header('Location: ../loginForm.php?error=stmtFailed');
    exit;
}