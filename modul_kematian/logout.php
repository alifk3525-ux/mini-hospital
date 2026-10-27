<?php
session_start();
unset($_SESSION['login_modul_k3rs']);
unset($_SESSION['user_k3rs']);
unset($_SESSION['role_k3rs']);

header("Location: login.php");
exit;