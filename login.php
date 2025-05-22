<?php
require_once 'config/koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi.";
    } else {
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = null;
        $password_verified = false;

        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username_db, $db_password, $role);
                    if (mysqli_stmt_fetch($stmt)) {
                        $is_hashed = preg_match('/^\$2[axy]\$/', $db_password);

                        if ($is_hashed) {
                            if (password_verify($password, $db_password)) {
                                $password_verified = true;
                            }
                        } else {
                            if ($password === $db_password) {
                                $password_verified = true;
                                $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                $sql_update_hash = "UPDATE users SET password = ? WHERE id = ?";
                                $stmt_update = null;
                                if ($stmt_update = mysqli_prepare($koneksi, $sql_update_hash)) {
                                    mysqli_stmt_bind_param($stmt_update, "si", $new_hashed_password, $id);
                                    if (!mysqli_stmt_execute($stmt_update)) {
                                        error_log("Gagal update hash password untuk user ID: " . $id . " Error: " . mysqli_stmt_error($stmt_update));
                                    }
                                    mysqli_stmt_close($stmt_update);
                                } else {
                                     error_log("Gagal prepare statement update hash password untuk user ID: " . $id . " Error: " . mysqli_error($koneksi));
                                }
                            }
                        }

                        if ($password_verified) {
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $id;
                            $_SESSION['username'] = $username_db;
                            $_SESSION['role'] = $role;
                            $_SESSION['loggedin'] = true;

                            mysqli_stmt_close($stmt);
                            mysqli_close($koneksi);
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = "Password yang Anda masukkan salah.";
                        }
                    }
                } else {
                    $error = "Username tidak ditemukan.";
                }
            } else {
                $error = "Oops! Terjadi kesalahan saat eksekusi query. Silakan coba lagi nanti.";
            }
            if ($stmt) {
                 mysqli_stmt_close($stmt);
            }
        } else {
             $error = "Oops! Terjadi kesalahan database saat persiapan statement. Silakan coba lagi nanti.";
        }

        if (!$password_verified) {
             mysqli_close($koneksi);
        }
    }
}

if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bumi Library <3</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="assets/bootstrap.css/css/theme.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden; /* Prevent accidental horizontal scroll */
        }

        /* Login Form Custom Styles */
        .form-control, .input-group-text {
            border-color: #dee2e6;
        }

        .form-control {
            font-size: 1rem;
        }

        .input-group {
            transition: all 0.2s ease;
        }

        .input-group:hover {
            box-shadow: 0 .25rem .75rem rgba(0, 123, 255, 0.1) !important;
        }

        .input-group:focus-within {
            box-shadow: 0 .25rem 1rem rgba(0, 123, 255, 0.15) !important;
        }

        .input-group-text svg {
            opacity: 0.7;
        }

        /* Clean, modern form look */
        .input-group, .btn {
            border-radius: 0.375rem;
        }

        .btn-primary {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.3rem 0.5rem rgba(0, 0, 0, 0.1) !important;
        }

        /* Loading Intro Styles */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #1a1a2e; /* Dark blue, similar to osu! */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99999; /* Ensure it's on top */
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }

        #loading-overlay.hidden {
            opacity: 0;
            pointer-events: none; /* Allow interaction with page below after hidden */
        }

        .loader-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .loading-logo-intro {
            width: 100px; /* Adjust as needed */
            height: 100px; /* Adjust as needed */
            animation: pulseLogo 2s infinite ease-in-out;
            z-index: 10; /* Above circles */
        }

        .intro-circle {
            position: absolute;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.8);
            opacity: 0;
            animation-timing-function: ease-out;
            animation-iteration-count: 1; /* Play once then rely on JS to hide */
            animation-fill-mode: forwards; /* Keep final state */
        }

        .intro-circle.circle1 {
            width: 120px;
            height: 120px;
            animation-name: expandCircle;
            animation-duration: 1.5s;
            animation-delay: 0s;
        }

        .intro-circle.circle2 {
            width: 120px;
            height: 120px;
            animation-name: expandCircle;
            animation-duration: 1.5s;
            animation-delay: 0.3s; /* Stagger the animations */
        }

        .intro-circle.circle3 {
            width: 120px;
            height: 120px;
            animation-name: expandCircle;
            animation-duration: 1.5s;
            animation-delay: 0.6s; /* Stagger the animations */
        }


        @keyframes pulseLogo {
            0% {
                transform: scale(0.95);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(0.95);
            }
        }

        @keyframes expandCircle {
            0% {
                transform: scale(0.5);
                opacity: 0.8;
            }
            80% {
                transform: scale(2.5); /* Expand beyond logo */
                opacity: 0.2;
            }
            100% {
                transform: scale(3);
                opacity: 0;
            }
        }

        /* End Loading Intro Styles */

        /* Real-time Clock Styles */
        #realtime-clock {
            position: fixed;
            bottom: 10px;
            left: 10px;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black background */
            color: #ffffff; /* White text */
            padding: 8px 15px;
            border-radius: 5px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1em;
            z-index: 10000; /* Ensure it's above other elements but below loading overlay if active */
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        /* End Real-time Clock Styles */

        /* Background and day-night elements */
        .background-layer {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: -1;
            transition: background 4s ease;
        }

        .morning {
            background: linear-gradient(to bottom, #FFC1A6, #FFF3D9, #87CEEB);
        }

        .day {
            background: linear-gradient(to bottom, #87CEEB, #4682B4);
        }

        .evening {
            background: linear-gradient(to bottom, #FF7F50, #FFD700, #4682B4);
        }

        .night {
            background: linear-gradient(to bottom, #000428, #004e92);
        }

        /* Sun and moon */
        .sun {
            position: absolute;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            z-index: 1;
            transition: all 4s ease, box-shadow 4s ease, background 4s ease, transform 4s ease;
        }

        .morning .sun {
            top: 5%;
            left: 10%;
            background: radial-gradient(circle, #FFE4B5, #FF8C00);
            box-shadow: 0 0 40px rgba(255, 140, 0, 0.7);
            transform: scale(0.9);
        }

        .day .sun {
            top: 10%;
            left: 50%;
            transform: translateX(-50%) scale(1);
            background: radial-gradient(circle, #FFD700, #FFA500);
            box-shadow: 0 0 50px rgba(255, 223, 0, 0.8);
        }

        .evening .sun {
            top: 15%;
            left: 80%;
            background: radial-gradient(circle, #FF4500, #FF8C00);
            box-shadow: 0 0 60px rgba(255, 69, 0, 0.9);
            transform: scale(1.1);
        }

        .moon {
            position: absolute;
            top: 10%;
            left: 50%;
            width: 80px;
            height: 80px;
            background: radial-gradient(circle, #FFF, #BBB);
            border-radius: 50%;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.8);
            z-index: 1;
            transition: all 4s ease, opacity 4s ease;
            opacity: 0;
            transform: translateX(-50%);
        }

        /* Stars */
        .star {
            position: absolute;
            width: 5px;
            height: 5px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
            animation: twinkle 2s infinite;
            transition: opacity 3s ease;
            opacity: 0; /* Initially invisible */
            z-index: 0;
        }

        /* Only show stars at night with a nice fade-in effect */
        .night .star {
            opacity: 0; /* Start with opacity 0 even in night mode */
            animation: twinkle 3s infinite, fadeInStar 3s forwards; /* Add fade in animation */
        }

        @keyframes fadeInStar {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.8; box-shadow: 0 0 10px rgba(255, 255, 255, 0.8); }
            50% { opacity: 0.4; box-shadow: 0 0 5px rgba(255, 255, 255, 0.4); }
        }

        /* Clouds */
        .cloud {
            position: absolute;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            box-shadow: 0 0 20px 10px rgba(255, 255, 255, 0.7);
            opacity: 0.8;
            animation: moveCloud 60s linear infinite;
            transition: background 3s ease, box-shadow 3s ease, opacity 3s ease;
        }

        .night .cloud {
            background: rgba(200, 200, 220, 0.4);
            box-shadow: 0 0 15px 8px rgba(200, 200, 255, 0.3);
            opacity: 0.5;
        }

        .cloud.c1 { width: 150px; height: 50px; top: 10%; left: -100px; animation-duration: 50s; }
        .cloud.c2 { width: 200px; height: 70px; top: 25%; left: -150px; animation-duration: 70s; animation-delay: -10s; }
        .cloud.c3 { width: 120px; height: 40px; top: 50%; left: -80px; animation-duration: 45s; animation-delay: -20s; }
        .cloud.c4 { width: 180px; height: 60px; top: 70%; left: -120px; animation-duration: 65s; animation-delay: -30s; }
        .cloud.c5 { width: 100px; height: 35px; top: 85%; left: -50px; animation-duration: 40s; animation-delay: -5s; }

        .cloud::before, .cloud::after {
            content: '';
            position: absolute;
            background: inherit;
            border-radius: 50%;
            box-shadow: inherit;
            opacity: inherit;
        }
        .cloud::before {
            width: 60%; height: 120%;
            top: -40%; left: 10%;
        }
        .cloud::after {
            width: 70%; height: 100%;
            top: -20%; right: 5%;
        }

        @keyframes moveCloud {
            from { transform: translateX(0); }
            to { transform: translateX(calc(100vw + 300px)); }
        }

        /* Book character */
        .book-character {
            position: absolute;
            bottom: 10%;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 180px;
            perspective: 1000px;
            z-index: 10;
            transition: transform 0.2s ease-out;
        }

        .book-body {
            width: 100%;
            height: 100%;
            background-color: #a0522d; /* Brown color for the book */
            border: 3px solid #5c300a; /* Darker brown border */
            border-radius: 5px 10px 10px 5px; /* Rounded edges for book shape */
            position: relative;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15); /* Softer shadow */
            transform-style: preserve-3d;
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transitions */
        }

        .book-cover-line {
            position: absolute;
            left: 5px;
            top: 0;
            bottom: 0;
            width: 15px;
            background-color: #5c300a;
            border-radius: 5px 0 0 5px;
        }

        .book-title {
            position: absolute;
            top: 20px;
            left: 30px;
            right: 10px;
            height: 20px;
            background-color: #e0cfa8;
            border: 1px solid #5c300a;
            border-radius: 3px;
        }

        .book-title-2 {
            position: absolute;
            top: 50px;
            left: 30px;
            right: 25px;
            height: 10px;
            background-color: #e0cfa8;
            border: 1px solid #5c300a;
            border-radius: 3px;
        }

        /* Book eyes */
        .book-eyes {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 25px;
            z-index: 1;
        }

        .eye {
            width: 25px;
            height: 30px;
            background-color: #f8f8f8; /* Slightly off-white */
            border-radius: 50%;
            border: 1px solid #7a4017; /* Thinner, slightly lighter border */
            position: relative;
            overflow: hidden;
            transition: height 0.1s ease-in-out, transform 0.1s ease-in-out;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.1); /* Softer inner shadow */
        }

        .pupil {
            width: 14px; /* Slightly larger */
            height: 14px; /* Slightly larger */
            background-color: #2c2c2c; /* Dark grey pupil */
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            transition: transform 0.08s linear; /* Faster pupil movement */
        }

        .eye.blink {
            height: 3px;
            transform: scaleY(0.1);
        }

        /* Welcome text styling for different time phases */
        .morning .welcome-text h1 {
            color: #8B4513; /* Brown color for morning text */
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.7); /* Softer shadow */
        }

        .morning .welcome-text p {
            color: #A0522D; /* Lighter Brown for morning sub-text */
        }

        .day .welcome-text h1 {
            color: #FFFFFF; /* White for day text */
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3); /* Softer shadow */
        }

        .day .welcome-text p {
            color: #F5F5F5; /* Light off-white for day sub-text */
        }

        .evening .welcome-text h1 {
            color: #FF8C00; /* Vibrant Orange for evening text */
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3); /* Softer shadow */
        }

        .evening .welcome-text p {
            color: #FFA500; /* Lighter Orange for evening sub-text */
        }

        .night .welcome-text h1 {
            color: #E0F7FA; /* Light blue for night text */
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5); /* Adjusted for night contrast */
        }

        .night .welcome-text p {
            color: #B2EBF2; /* Lighter blue for night sub-text */
        }

        .btn-primary {
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary:hover {
            /* Assuming theme.css handles primary hover color or use filter: brightness(90%); */
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .card { /* Login form card */
            border: 1px solid #dee2e6; /* Standard Bootstrap border */
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.05); /* Subtle, clean shadow */
        }

        /* CSS for page transitions */
        body.page-transition {
            opacity: 0;
            transition: opacity 0.4s ease-in-out;
        }

        body.fade-in {
            opacity: 1;
        }

        body.fade-out {
            opacity: 0;
        }
    </style>
</head>
<body class="page-transition">
    <div id="loading-overlay">
        <div class="loader-container">
            <img src="assets/logosmk.png" alt="Logo" class="loading-logo-intro">
            <div class="intro-circle circle1"></div>
            <div class="intro-circle circle2"></div>
            <div class="intro-circle circle3"></div>
        </div>
    </div>
    <div class="background-layer">
        <div class="sun" id="sun"></div>
        <div class="moon" id="moon"></div>
        <div class="cloud c1"></div>
        <div class="cloud c2"></div>
        <div class="cloud c3"></div>
        <div class="cloud c4"></div>
        <div class="cloud c5"></div>
        <div class="star" style="top: 15%; left: 25%;"></div>
        <div class="star" style="top: 20%; left: 40%;"></div>
        <div class="star" style="top: 12%; left: 60%;"></div>
        <div class="star" style="top: 25%; left: 75%;"></div>
        <div class="star" style="top: 35%; left: 20%;"></div>
        <div class="star" style="top: 40%; left: 50%;"></div>
        <div class="star" style="top: 45%; left: 80%;"></div>
        <div class="star" style="top: 55%; left: 30%;"></div>
        <div class="star" style="top: 60%; left: 70%;"></div>
        <div class="star" style="top: 70%; left: 15%;"></div>
        <div class="star" style="top: 75%; left: 45%;"></div>
        <div class="star" style="top: 80%; left: 65%;"></div>
    </div>

    <div class="container-fluid vh-100 p-0 d-flex align-items-center justify-content-center">
        <div class="row h-100 g-0 w-100">
            <div class="col-md-6 text-white d-flex flex-column justify-content-center p-4 position-relative overflow-hidden">
                <div class="position-relative welcome-text" style="z-index: 5">
                    <h1 class="display-4 fw-bold mb-3" id="welcomeHeading">Welcome to Bumi Librart <3</h1>
                    <p class="lead mb-0" id="welcomeMessage">Sistem informasi perpustakaan untuk pengelolaan buku dan peminjaman yang efisien dan mudah digunakan.</p>
                </div>

                <div class="book-character" id="bookCharacter" style="z-index: 10;">
                    <div class="book-body">
                        <div class="book-cover-line"></div>
                        <div class="book-title"></div>
                        <div class="book-title-2"></div>
                        <div class="book-eyes">
                            <div class="eye">
                                <div class="pupil" id="leftPupil"></div>
                            </div>
                            <div class="eye">
                                <div class="pupil" id="rightPupil"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 d-flex align-items-center justify-content-center p-4 p-md-5">
                <div class="w-75">
                    <div class="card shadow-sm rounded-3">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <img class="mb-3" src="assets/logosmk.png" alt="Logo SMK" width="80" height="auto">
                                <h2 class="fw-semibold text-primary mb-4">USER LOGIN</h2>
                            </div>

                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_GET['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($_GET['success']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="mb-4">
                                    <label for="username" class="form-label visually-hidden">Username</label>
                                    <div class="input-group shadow-sm rounded overflow-hidden">
                                        <span class="input-group-text bg-white border-end-0 py-3 ps-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-person text-secondary" viewBox="0 0 16 16">
                                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                                            </svg>
                                        </span>
                                        <input type="text" class="form-control border-start-0 py-3" id="username" name="username" placeholder="Username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="passwordInput" class="form-label visually-hidden">Password</label>
                                    <div class="input-group shadow-sm rounded overflow-hidden">
                                        <span class="input-group-text bg-white border-end-0 py-3 ps-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-lock text-secondary" viewBox="0 0 16 16">
                                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/>
                                            </svg>
                                        </span>
                                        <input type="password" class="form-control border-start-0 py-3" name="password" id="passwordInput" placeholder="Password" required>
                                        <span class="input-group-text bg-white border-start-0 py-3 px-3" style="cursor:pointer;" id="togglePassword">
                                            <i id="togglePasswordIcon" class="bi bi-eye-fill text-secondary" style="font-size: 1rem;"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-4 text-end">
                                    <small><a href="#" class="text-decoration-none text-secondary">Forgot password?</a></small>
                                </div>
                                <div class="d-grid gap-2 mt-4">
                                    <button class="btn btn-primary text-white py-3 rounded-pill fw-semibold shadow-sm" type="submit">LOGIN</button>
                                </div>

                                <p class="mt-3 mb-0 text-secondary text-center small">
                                    Belum punya akun? <a href="register.php" class="text-decoration-none fw-medium">Daftar di sini</a>
                                </p>
                                <p class="mt-4 mb-0 text-secondary text-center small">&copy; Bumi Library <3 <?php echo date("Y"); ?></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="realtime-clock">
        <span id="clock-text"></span>
        <span id="time-emoji" style="margin-left: 5px;"></span>
    </div>

    <script src="assets/bootstrap.js/bootstrap.bundle.min.js"></script>
    <script>
        // Password peek functionality
        const passwordInput = document.getElementById('passwordInput');
        const togglePassword = document.getElementById('togglePassword');
        const togglePasswordIcon = document.getElementById('togglePasswordIcon'); // This now refers to the <i> element
        if (passwordInput && togglePassword && togglePasswordIcon) {
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                // Toggle Bootstrap icon classes
                if (type === 'text') {
                    togglePasswordIcon.classList.remove('bi-eye-fill');
                    togglePasswordIcon.classList.add('bi-eye-slash-fill');
                } else {
                    togglePasswordIcon.classList.remove('bi-eye-slash-fill');
                    togglePasswordIcon.classList.add('bi-eye-fill');
                }
            });
        }

        // Forgot password text change
        const forgotPasswordLink = document.querySelector('a[href="#"].text-decoration-none.text-secondary');
        if (forgotPasswordLink) {
            forgotPasswordLink.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default link behavior
                const originalText = this.textContent;
                this.textContent = 'Pasrah Saja';
                setTimeout(() => {
                    this.textContent = originalText;
                }, 3000); // Revert back after 3 seconds
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in effect on page load
            document.body.classList.add('fade-in');

            const loadingOverlay = document.getElementById('loading-overlay');

            // Hide loading overlay after animation/delay
            setTimeout(() => {
                if (loadingOverlay) {
                    loadingOverlay.classList.add('hidden');
                }
            }, 2500); // Adjust time as needed (e.g., 2.5 seconds)

            const backgroundLayer = document.querySelector('.background-layer');
            const sun = document.getElementById('sun');
            const moon = document.getElementById('moon');
            const stars = document.querySelectorAll('.star');
            const clouds = document.querySelectorAll('.cloud');

            const welcomeHeading = document.getElementById('welcomeHeading');
            const welcomeMessage = document.getElementById('welcomeMessage');
            const timeEmojiElement = document.getElementById('time-emoji'); // Get the new emoji span

            const timePhases = ['morning', 'day', 'evening', 'night'];
            let currentPhaseName; // Will store the name of the phase

            // Determine current time phase based on user's actual time
            const currentHour = new Date().getHours();
            if (currentHour >= 5 && currentHour < 12) {
                currentPhaseName = 'morning';
            } else if (currentHour >= 12 && currentHour < 15) {
                currentPhaseName = 'day';
            } else if (currentHour >= 15 && currentHour < 18) {
                currentPhaseName = 'evening';
            } else {
                currentPhaseName = 'night';
            }

            updateTimePhase(currentPhaseName);

            function updateTimePhase(phase) {
                backgroundLayer.classList.remove('morning', 'day', 'evening', 'night');
                backgroundLayer.classList.add(phase);
                updateWelcomeText(phase);

                let emoji = ''; // Variable to hold the emoji

                switch(phase) {
                    case 'morning':
                        sun.style.display = 'block';
                        moon.style.opacity = '0';
                        sun.style.opacity = '1';
                        updateCloudAppearance('morning');
                        hideStars();
                        emoji = '☀️'; // Sun emoji for morning
                        break;
                    case 'day':
                        sun.style.display = 'block';
                        moon.style.opacity = '0';
                        sun.style.opacity = '1';
                        updateCloudAppearance('day');
                        hideStars();
                        emoji = '☀️'; // Sun emoji for day
                        break;
                    case 'evening':
                        sun.style.display = 'block';
                        moon.style.opacity = '0';
                        sun.style.opacity = '1';
                        updateCloudAppearance('evening');
                        hideStars();
                        emoji = '🌇'; // Sunset emoji for evening
                        break;
                    case 'night':
                        sun.style.opacity = '0';
                        moon.style.opacity = '1';
                        moon.style.display = 'block';
                        setTimeout(showStars, 800);
                        updateCloudAppearance('night');
                        emoji = '🌙'; // Moon emoji for night
                        break;
                }

                if (timeEmojiElement) {
                    timeEmojiElement.textContent = emoji; // Set the emoji
                }
            }

            function updateWelcomeText(phase) {
                switch(phase) {
                    case 'morning':
                        welcomeHeading.textContent = "Selamat Pagi! Welcome to Bumi Library <3";
                        welcomeMessage.textContent = "Awali harimu dengan semangat membaca dan temukan pengetahuan baru!";
                        break;
                    case 'day':
                        welcomeHeading.textContent = "Selamat Siang! Welcome to Bumi Library <3";
                        welcomeMessage.textContent = "Manfaatkan waktumu untuk menjelajahi koleksi buku kami.";
                        break;
                    case 'evening':
                        welcomeHeading.textContent = "Selamat Sore! Welcome to Bumi Library <3";
                        welcomeMessage.textContent = "Waktu yang tepat untuk bersantai dan menikmati cerita menarik.";
                        break;
                    case 'night':
                        welcomeHeading.textContent = "Selamat Malam! Welcome to Bumi Library <3";
                        welcomeMessage.textContent = "Biarkan buku menemani malammu sebelum beristirahat.";
                        break;
                }
            }

            function showStars() {
                stars.forEach((star, index) => {
                    const randomDelay = 100 + Math.random() * 1000 + index * 50;
                    setTimeout(() => {
                        star.style.opacity = '0.8'; // Set a default opacity
                    }, randomDelay);
                });
            }

            function hideStars() {
                stars.forEach(star => {
                    star.style.opacity = '0';
                });
            }

            function updateCloudAppearance(phase) {
                clouds.forEach(cloud => {
                    cloud.classList.remove('morning-cloud', 'day-cloud', 'evening-cloud', 'night-cloud');
                });
            }

            const leftPupil = document.getElementById('leftPupil');
            const rightPupil = document.getElementById('rightPupil');
            const bookCharacter = document.getElementById('bookCharacter');
            const eyes = Array.from(document.querySelectorAll('.eye')); // Ensure it's an array for forEach
            const leftPanel = document.querySelector('.col-md-6.text-white');
            let panelRect = leftPanel.getBoundingClientRect();

            window.addEventListener('resize', () => {
                panelRect = leftPanel.getBoundingClientRect();
            });

            leftPanel.addEventListener('mousemove', function(event) {
                if (!leftPupil || !rightPupil || !bookCharacter || !leftPanel) return;

                const mouseX = event.clientX - panelRect.left;
                const mouseY = event.clientY - panelRect.top;

                // Pupil movement
                [leftPupil, rightPupil].forEach(pupil => {
                    const eyeRect = pupil.parentElement.getBoundingClientRect();
                    const eyeCenterX = eyeRect.left - panelRect.left + eyeRect.width / 2;
                    const eyeCenterY = eyeRect.top - panelRect.top + eyeRect.height / 2;

                    const deltaX = mouseX - eyeCenterX;
                    const deltaY = mouseY - eyeCenterY;
                    const angle = Math.atan2(deltaY, deltaX);

                    const maxPupilMove = pupil.parentElement.offsetWidth / 4; // Max distance pupil can move
                    const distance = Math.min(maxPupilMove, Math.hypot(deltaX, deltaY) * 0.2);

                    const moveX = Math.cos(angle) * distance;
                    const moveY = Math.sin(angle) * distance;

                    pupil.style.transform = `translate(calc(-50% + ${moveX}px), calc(-50% + ${moveY}px))`;
                });

                // Book character tilt and slight movement
                const maxTilt = 6; // Max degrees to tilt
                const bookTiltX = (mouseY / panelRect.height - 0.5) * maxTilt * -1.2;
                const bookTiltY = (mouseX / panelRect.width - 0.5) * maxTilt * 1.2;

                const maxMove = 4; // Max pixels to move
                const bookMoveX = (mouseX / panelRect.width - 0.5) * maxMove;
                const bookMoveYsync = (mouseY / panelRect.height - 0.5) * maxMove;

                // Apply if not focused on input
                if (!document.activeElement || (document.activeElement.id !== 'username' && document.activeElement.id !== 'password')) {
                    bookCharacter.style.transform = `translateX(calc(-50% + ${bookMoveX}px)) translateY(${bookMoveYsync}px) rotateX(${bookTiltX}deg) rotateY(${bookTiltY}deg) scale(1)`;
                }
            });

            function blink() {
                if (eyes.length > 0) {
                    eyes.forEach(eye => eye.classList.add('blink'));
                    setTimeout(() => {
                        eyes.forEach(eye => eye.classList.remove('blink'));
                    }, 150); // Blink duration
                }
            }
            setInterval(blink, 3000 + Math.random() * 3000); // Blink every 3-6 seconds

            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');

            [usernameInput, passwordInput].forEach(input => {
                if (input) {
                    input.addEventListener('focus', () => {
                        if (bookCharacter) {
                            bookCharacter.style.transform = 'translateX(-50%) translateY(-5px) scale(1.05) rotateX(5deg)';
                        }
                    });
                    input.addEventListener('blur', () => {
                        if (bookCharacter) {
                            // Reset to a neutral position, mousemove will take over for tilt/movement
                            bookCharacter.style.transform = 'translateX(-50%) translateY(0px) scale(1) rotateX(0deg) rotateY(0deg)';
                        }
                    });
                }
            });

            // Real-time Clock Functionality
            const clockTextElement = document.getElementById('clock-text'); // Target the new span for text

            function updateClock() {
                if (clockTextElement) {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    clockTextElement.textContent = `${hours}:${minutes}:${seconds}`;
                }
            }

            updateClock(); // Initial call to display clock immediately
            setInterval(updateClock, 1000); // Update clock every second

            // Handle fade-out for navigation to register.php
            const registerLink = document.querySelector('a[href="register.php"]');
            if (registerLink) {
                registerLink.addEventListener('click', function(event) {
                    event.preventDefault(); // Prevent default link behavior
                    document.body.classList.remove('fade-in');
                    document.body.classList.add('fade-out');
                    setTimeout(() => {
                        window.location.href = this.href; // Navigate after fade-out
                    }, 400); // Match CSS transition duration
                });
            }
        });

        // Loading Intro Animation
        document.addEventListener('DOMContentLoaded', () => {
            const loadingOverlay = document.getElementById('loading-overlay');
            const mainContent = document.querySelector('.container-custom'); // Or your main content wrapper

            if (loadingOverlay) {
                // Ensure main content is hidden initially to prevent flash of unstyled content
                if (mainContent) {
                    mainContent.style.visibility = 'hidden';
                }

                // Total duration of the longest circle animation (delay + duration)
                // circle3 has delay 0.6s and duration 1.5s = 2.1s
                // Let's give it a bit more, say 2500ms for animations to fully complete
                setTimeout(() => {
                    loadingOverlay.classList.add('hidden');

                    // After the fade out transition (0.5s), set display to none
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        // Make main content visible
                        if (mainContent) {
                            mainContent.style.visibility = 'visible';
                        }
                    }, 500); // Corresponds to the transition duration of #loading-overlay
                }, 2500);
            }
        });
    </script>
</body>
</html>