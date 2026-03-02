<?php
require_once __DIR__ . '/includes/bootstrap.php';
session_destroy();
header('Location: ' . BASE . '/login.php');
exit;
