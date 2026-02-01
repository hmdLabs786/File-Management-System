<?php
require 'db.php';

session_start();

if ( $_SERVER[ 'REQUEST_METHOD' ] == 'POST' ) {
    $action = $_POST[ 'action' ];

    if ( $action == 'register' ) {
        $user = trim( $_POST[ 'username' ] );
        $email = trim( $_POST[ 'email' ] );
        $pass = password_hash( $_POST[ 'password' ], PASSWORD_BCRYPT );

        $check = $pdo->prepare( 'SELECT id FROM users WHERE email = ?' );
        $check->execute( [ $email ] );

        if ( $check->rowCount() > 0 ) {
            header( 'Location: auth.php?error=Email already registered' );
        } else {
            $stmt = $pdo->prepare( 'INSERT INTO users (username, email, password) VALUES (?, ?, ?)' );
            $stmt->execute( [ $user, $email, $pass ] );
            header( 'Location: auth.php?msg=Account Created. Please Login' );
        }
    }

    if ( $action == 'login' ) {
        $email = trim( $_POST[ 'email' ] );
        $stmt = $pdo->prepare( 'SELECT * FROM users WHERE email = ?' );
        $stmt->execute( [ $email ] );
        $user = $stmt->fetch();

        if ( $user && password_verify( $_POST[ 'password' ], $user[ 'password' ] ) ) {
            $_SESSION[ 'user_id' ] = $user[ 'id' ];
            $_SESSION[ 'username' ] = $user[ 'username' ];
            $_SESSION[ 'email' ] = $user[ 'email' ];
            header( 'Location: index.php' );

            exit();
        } else {
            header( 'Location: auth.php?error=Invalid Credentials' );
        }
    }
}
?>