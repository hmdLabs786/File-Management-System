<?php
session_start();
require 'db.php';

if ( !isset( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: auth.php' );
    exit();
}

if ( isset( $_GET[ 'id' ] ) ) {
    $file_id = $_GET[ 'id' ];
    $user_id = $_SESSION[ 'user_id' ];

    try {

        $stmt = $pdo->prepare( "UPDATE files SET status = 'deleted' WHERE id = ? AND user_id = ?" );
        $stmt->execute( [ $file_id, $user_id ] );

        header( 'Location: index.php?msg=moved_to_trash' );
        exit();
    } catch ( PDOException $e ) {
        die( 'Error moving file to trash: ' . $e->getMessage() );
    }
} else {
    header( 'Location: index.php' );
}
?>