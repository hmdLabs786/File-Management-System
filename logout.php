<?php
session_start();

$_SESSION = array();

if ( isset( $_COOKIE[ session_name() ] ) ) {
    setcookie( session_name(), '', time() - 3600, '/' );
}

session_destroy();

header( 'Cache-Control: no-cache, no-store, must-revalidate' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

header( 'Location: auth.php' );
exit();
?>