<!DOCTYPE html>
<html>
<head>
    <title>FUMS - Secure Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
        <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body {
            margin: 0; padding: 0; display: flex; justify-content: center; align-items: center;
            min-height: 100vh; font-family: 'Jost', sans-serif;
            background: linear-gradient(to bottom, #002721, #004d40, #002721);
        }
        .main {
            width: 350px; height: 550px; background: #fff; overflow: hidden;
            border-radius: 15px; box-shadow: 5px 20px 50px rgba(0,0,0,0.5);
            position: relative;
        }
        .logo-container {
            text-align: center; padding-top: 20px;
        }
        .logo-container img {
            width: 60px; height: 60px; object-fit: contain;
        }

        #chk { display: none; }

        .signup {
            position: relative; width: 100%; height: 100%;
            background: #004d40;
        }
        label {
            color: #fff; font-size: 2.3em; justify-content: center; display: flex;
            margin: 30px 0; font-weight: bold; cursor: pointer; transition: .5s ease-in-out;
        }
        input {
            width: 60%; height: 20px; background: #e0dede; justify-content: center;
            display: flex; margin: 15px auto; padding: 12px; border: none;
            outline: none; border-radius: 8px;
        }
        button {
            width: 65%; height: 45px; margin: 10px auto; justify-content: center;
            display: block; color: #004d40; background: #fff; font-size: 1em;
            font-weight: bold; margin-top: 20px; outline: none; border: none;
            border-radius: 8px; transition: .2s ease-in; cursor: pointer;
        }
        button:hover { background: #e0dede; }

        
        .login {
            height: 500px; background: #eee; border-radius: 60% / 10%;
            transform: translateY(-160px); transition: .8s ease-in-out;
        }
        .login label { color: #004d40; transform: scale(.6); margin-top: 40px; }
        .login button { background: #004d40; color: #fff; }
        .login button:hover { background: #00332c; }

        #chk:checked ~ .login { transform: translateY(-520px); }
        #chk:checked ~ .login label { transform: scale(1); }
        #chk:checked ~ .signup label { transform: scale(.6); }

        .error-msg {
            color: #ff9999; text-align: center; font-size: 13px;
            margin: 5px 40px; font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="main">      
        <input type="checkbox" id="chk" aria-hidden="true">

            <div class="signup">
                <div class="logo-container">
                    <img src="logo.png" alt="FUMS LOGO">
                </div>
                <form action="auth_logic.php" method="POST">
                    <label for="chk" aria-hidden="true">Sign up</label>
                    <input type="hidden" name="action" value="register">
                    <input type="text" name="username" placeholder="Full Name" required="">
                    <input type="email" name="email" placeholder="Email Address" required="">
                    <input type="password" name="password" placeholder="Password" required="">
                    <button type="submit">Join FUMS</button>
                    <?php if(isset($_GET['error'])) echo "<p class='error-msg'>".$_GET['error']."</p>"; ?>
                </form>
            </div>

            <div class="login">
                <form action="auth_logic.php" method="POST">
                    <label for="chk" aria-hidden="true">Login</label>
                    <input type="hidden" name="action" value="login">
                    <input type="email" name="email" placeholder="Email" required="">
                    <input type="password" name="password" placeholder="Password" required="">
                    <button type="submit">Sign In</button>
                </form>
            </div>
    </div>
</body>
</html>