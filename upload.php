<?php
session_start();
require 'db.php';

if ( !isset( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: auth.php' );
    exit();
}

if ( $_SERVER[ 'REQUEST_METHOD' ] == 'POST' && isset( $_FILES[ 'file_upload' ] ) ) {
    $user_id = $_SESSION[ 'user_id' ];
    $files = $_FILES[ 'file_upload' ];
    $upload_dir = 'uploads/';

    $allowed_extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'doc', 'txt', 'zip', 'xlsx' ];

    $max_size = 10 * 1024 * 1024;

    if ( !is_dir( $upload_dir ) ) mkdir( $upload_dir, 0755, true );

    for ( $i = 0; $i < count( $files[ 'name' ] );
    $i++ ) {
        $original_name = basename( $files[ 'name' ][ $i ] );
        $file_size = $files[ 'size' ][ $i ];
        $file_tmp = $files[ 'tmp_name' ][ $i ];
        $file_error = $files[ 'error' ][ $i ];
        $file_ext = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );
        $file_type = $files[ 'type' ][ $i ];

        if ( $file_error === 4 ) continue;

        if ( $file_error !== 0 ) continue;

        if ( !in_array( $file_ext, $allowed_extensions ) ) continue;

        if ( $file_size > $max_size ) continue;

        $unique_name = bin2hex( random_bytes( 16 ) ) . '.' . $file_ext;
        $secure_token = bin2hex( random_bytes( 32 ) );

        $dest_path = $upload_dir . $unique_name;

        $expiry_date = date( 'Y-m-d H:i:s', strtotime( '+30 days' ) );

        if ( move_uploaded_file( $file_tmp, $dest_path ) ) {
            try {
                $stmt = $pdo->prepare( "INSERT INTO files 
                    (user_id, original_name, server_name, file_path, file_type, file_size, secure_token, status, expires_at, download_count) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, 0)" );

                $stmt->execute( [
                    $user_id,
                    $original_name,
                    $unique_name,
                    $dest_path,
                    $file_type,
                    $file_size,
                    $secure_token,
                    $expiry_date
                ] );
            } catch ( PDOException $e ) {
                if ( file_exists( $dest_path ) ) unlink( $dest_path );
                continue;

            }
        }
    }

    header( 'Location: index.php?upload=success' );
    exit();
} else {
    header( 'Location: index.php' );
}
?>