<?php
session_start();
require 'db.php';

if ( !isset( $_SESSION[ 'user_id' ] ) ) {
    exit( 'Unauthorized' );
}

if ( isset( $_GET[ 'id' ] ) ) {
    $file_id = $_GET[ 'id' ];
    $user_id = $_SESSION[ 'user_id' ];

    try {
        $stmt = $pdo->prepare( 'UPDATE files SET is_starred = NOT is_starred WHERE id = ? AND user_id = ?' );
        $stmt->execute( [ $file_id, $user_id ] );
    } catch ( PDOException $e ) {
        die( 'Database Error: ' . $e->getMessage() );
    }
}

$back = isset( $_SERVER[ 'HTTP_REFERER' ] ) ? $_SERVER[ 'HTTP_REFERER' ] : 'index.php';
header( 'Location: ' . $back );
exit();
?>