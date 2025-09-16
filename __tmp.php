<?php
session_start();
$_SESSION['utilisateur_id'] = 1;
$_GET['id'] = 1;
include 'livre.php';
