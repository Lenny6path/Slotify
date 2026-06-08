<?php

require_once __DIR__ . '/../config/init.php';

session_unset();
session_destroy();

header("Location: login.php");
exit;