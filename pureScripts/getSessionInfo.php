<?php

function GetInfoFromSession(&$templateArray) {
    if (isset($_SESSION['myUserId'])) {
        $templateArray['notLoggedIn'] = FALSE;
        $templateArray['myUsername'] = $_SESSION['myUsername'];
        $templateArray['myUserId'] =  $_SESSION['myUserId'];
        $templateArray['myAdmin'] = $_SESSION['myAdmin'];
        $templateArray['myProfilePicture'] = $_SESSION['myProfilePicture'];
        
    } else {
        $templateArray['notLoggedIn'] = TRUE;
    }
}