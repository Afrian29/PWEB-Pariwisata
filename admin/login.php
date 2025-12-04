<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// If already logged in, redirect to manage_places
if (isset($_SESSION['user_id'])) {
    header('Location: manage_places.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        $query = "SELECT id, username, password FROM users WHERE username = ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: manage_places.php');
                exit();
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'User not found.';
        }
        $stmt->close();
    }
    $db->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Login - Pesona Gorontalo</title>
 
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <!--Stylesheet-->
    <style media="screen">
      *,
*:before,
*:after{
    padding: 0;
    margin: 0;
    box-sizing: border-box;
}
body{
    background-color: #080710;
    background-image: url('../assets/images/bg1.jpeg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    min-height: 100vh;
    overflow: hidden; /* Prevent scrollbars if shapes go out */
}
/* Overlay agar text form lebih terbaca */
body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3);
    z-index: -1;
}

.background{
    width: 430px;
    height: 520px;
    position: absolute;
    transform: translate(-50%,-50%);
    left: 50%;
    top: 50%;
}
.background .shape{
    height: 200px;
    width: 200px;
    position: absolute;
    border-radius: 50%;
}
/* Shape 1: Gradient Biru Laut */
.shape:first-child{
    background: linear-gradient(
        #0077B6,
        #0096C7
    );
    left: -80px;
    top: -80px;
}
/* Shape 2: Gradient Oranye Senja */
.shape:last-child{
    background: linear-gradient(
        to right,
        #F4A261,
        #E76F51
    );
    right: -30px;
    bottom: -80px;
}
form{
    height: auto; /* Auto height to fit content */
    min-height: 520px;
    width: 400px;
    background-color: rgba(255,255,255,0.13);
    position: absolute;
    transform: translate(-50%,-50%);
    top: 50%;
    left: 50%;
    border-radius: 10px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255,255,255,0.1);
    box-shadow: 0 0 40px rgba(8,7,16,0.6);
    padding: 50px 35px;
}
form *{
    font-family: 'Poppins',sans-serif;
    color: #ffffff;
    letter-spacing: 0.5px;
    outline: none;
    border: none;
}
form h3{
    font-size: 32px;
    font-weight: 500;
    line-height: 42px;
    text-align: center;
    margin-bottom: 20px;
}

label{
    display: block;
    margin-top: 30px;
    font-size: 16px;
    font-weight: 500;
}
input{
    display: block;
    height: 50px;
    width: 100%;
    background-color: rgba(255,255,255,0.07);
    border-radius: 3px;
    padding: 0 10px;
    margin-top: 8px;
    font-size: 14px;
    font-weight: 300;
}
::placeholder{
    color: #e5e5e5;
}
button{
    margin-top: 50px;
    width: 100%;
    background-color: #ffffff;
    color: #080710;
    padding: 15px 0;
    font-size: 18px;
    font-weight: 600;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}
button:hover {
    background-color: #f0f0f0;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
    font-size: 14px;
    text-align: center;
}
.alert-danger {
    color: #fff;
    background-color: rgba(220, 53, 69, 0.8);
    border-color: rgba(220, 53, 69, 0.9);
}

.back-link {
    display: block;
    text-align: center;
    margin-top: 20px;
    color: #fff;
    text-decoration: none;
    font-size: 14px;
    opacity: 0.8;
    transition: opacity 0.3s;
}
.back-link:hover {
    opacity: 1;
    text-decoration: underline;
}

    </style>
</head>
<body>
    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <form method="POST">
        <h3>Admin Login</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <label for="username">Username</label>
        <input type="text" placeholder="Enter Username" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" placeholder="Enter Password" id="password" name="password" required>

        <button type="submit">Log In</button>
        
        <a href="../index.php" class="back-link">← Kembali ke Home</a>
    </form>
</body>
</html>
