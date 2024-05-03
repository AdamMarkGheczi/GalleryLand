<?php

if($_SERVER['REQUEST_METHOD']  !== 'POST') {
    header('Location:../index.php');
    exit;
}


$username = htmlspecialchars(stripslashes(trim($_POST['username'])));
$email = htmlspecialchars(stripslashes(trim($_POST['email'])));
$password = htmlspecialchars(stripslashes(trim($_POST['password'])));
$confPassword = htmlspecialchars(stripslashes(trim($_POST['confPassword'])));

if(strlen($password) < 6) {
    header('Location:../signupForm.php?error=badFormat');
    exit;    
}

if($password !== $confPassword) {
    header('Location:../signupForm.php?error=dontMatch');
    exit;
}

try{
    require_once 'databaseCredentials.php';
    $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);

    $stmt = $conn->prepare('SELECT COUNT(*) as matchingUsers FROM users where username = ?');
    $stmt -> bind_param("s", $username);
    $stmt -> execute();
    $row = $stmt -> get_result() -> fetch_assoc();

    if($row['matchingUsers'] !== 0) {
        $conn -> close();
        header('Location:../signupForm.php?error=usernameTaken');
        exit;
    }

    $stmt = $conn->prepare('SELECT COUNT(*) as matchingUsers FROM users where email = ?');
    $stmt -> bind_param("s", $email);
    $stmt -> execute();
    $row = $stmt -> get_result() -> fetch_assoc();


    if($row['matchingUsers'] !== 0) {
        $conn -> close();
        header('Location:../signupForm.php?error=emailTaken');
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO users (`username`, `email`, `password`) VALUES (?, ?, ?)');
    $stmt -> bind_param("sss", $username, $email, password_hash($password, PASSWORD_DEFAULT));
    $stmt -> execute();
    
    $conn -> close();
    header('Location:../loginForm.php');
    exit;

} catch (Exception $e) {
    header('Location:../signupForm.php?error=stmtFailed');
    exit;
}
