<?php
require 'db.php';

if ( isset( $_GET[ 'token' ] ) ) {
    $token = $_GET[ 'token' ];

    $stmt = $pdo->prepare( "SELECT * FROM files WHERE secure_token = ? AND status = 'active'" );
    $stmt->execute( [ $token ] );
    $file = $stmt->fetch();

    if ( $file ) {
        if ( $file[ 'expires_at' ] && strtotime( $file[ 'expires_at' ] ) < time() ) {
            die( 'This file link has expired.' );
        }

        $update = $pdo->prepare( 'UPDATE files SET download_count = download_count + 1 WHERE id = ?' );
        $update->execute( [ $file[ 'id' ] ] );

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: ' . $file[ 'file_type' ] );
        header( 'Content-Disposition: attachment; filename="' . $file[ 'original_name' ] . '"' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . $file[ 'file_size' ] );
        readfile( $file[ 'file_path' ] );
        exit;
    } else {
        die( 'Invalid or deleted file link.' );
    }
}
?>