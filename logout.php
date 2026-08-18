<?php
/**
 * Logout — tidak include config.php agar tidak kena proteksi login.
 */
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
