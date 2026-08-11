<?php
// includes/auth.php
session_start();

function checkLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }
}

function checkRole($roles)
{
    if (!in_array($_SESSION['role'], (array) $roles)) {
        header("Location: ../index.php?error=unauthorized");
        exit();
    }
}
?>