<?php
require_once 'db.php';
require_once 'session_check.php';

/************************************************************
 * logout.php
 * Beendet die Session
 ************************************************************/

session_destroy();
header("Location: login.php");
exit;
