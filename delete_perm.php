<?php
session_start();
require 'db.php';

if ( !isset( $_SESSION[ 'user_id' ] ) || !isset( $_GET[ 'id' ] ) ) {
    header( 'Location: index.php' );
    exit();
}

$file_id = $_GET[ 'id' ];
$user_id = $_SESSION[ 'user_id' ];

$stmt = $pdo->prepare( 'SELECT file_path FROM files WHERE id = ? AND user_id = ?' );
$stmt->execute( [ $file_id, $user_id ] );
$file = $stmt->fetch();

if ( $file ) {
    $physical_path = $file[ 'file_path' ];

    if ( file_exists( $physical_path ) ) {
        unlink( $physical_path );
    }

    $delete = $pdo->prepare( 'DELETE FROM files WHERE id = ? AND user_id = ?' );
    $delete->execute( [ $file_id, $user_id ] );
}

header( 'Location: index.php?view=trash&msg=deleted_perm' );
exit();