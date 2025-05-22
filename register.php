<?php
session_start();
require 'config/koneksi.php'; // Adjust path as necessary

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    // Default role for new users
    $role = 'user';

    if (empty($username) || empty($password) || empty($konfirmasi_password)) {
        $error = "Username dan password wajib diisi.";
    } elseif ($password !== $konfirmasi_password) {
        $error = "Password dan konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal harus 6 karakter.";
    } else {
        // Check if username already exists
        $stmt_check = $koneksi->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = "Username sudah terdaftar.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt_insert = $koneksi->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $username, $hashed_password, $role);
            if ($stmt_insert->execute()) {
                // Redirect to login page with success message
                header("Location: login.php?success=Registrasi berhasil! Silakan login.");
                exit();
            } else {
                $error = "Terjadi kesalahan saat registrasi. Silakan coba lagi. Error: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
    // Removed $koneksi->close(); to allow it to be closed if an error occurs before this point or after form display.
    // It will be closed implicitly at the end of the script if not already closed.
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun - Bumi Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #eef2f7; /* Light, modern background */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow: hidden; /* To prevent scrollbars from background elements */
        }

        .register-container {
            max-width: 480px; /* Slightly wider for better spacing */
            width: 100%;
        }

        .card {
            border: none; /* Remove default border */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); /* Softer, more modern shadow */
            border-radius: 1rem; /* More pronounced rounding */
            overflow: hidden; /* Ensures child elements conform to border radius */
        }

        .card-header {
            background-color: #007bff; /* Primary color from login */
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-bottom: none;
        }
        .card-header img {
            max-width: 60px; /* Smaller logo */
            margin-bottom: 0.75rem;
        }
        .card-header h2 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .card-header p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .card-body {
            padding: 2rem; /* More padding inside card */
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .input-group {
            border-radius: 0.5rem; /* Rounded input groups */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); /* Subtle shadow on input groups */
            transition: box-shadow 0.3s ease;
        }
        .input-group:focus-within {
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2); /* Enhanced shadow on focus */
        }

        .form-control {
            border: 1px solid #ced4da;
            padding: 0.85rem 1rem; /* Comfortable padding */
            font-size: 0.95rem;
            border-radius: 0.5rem !important; /* Ensure consistent rounding */
        }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: none; /* Remove default Bootstrap focus shadow, handled by input-group */
        }

        .input-group-text {
            background-color: #fff;
            border: 1px solid #ced4da;
            border-right: none; /* For icons on the left */
            padding: 0.85rem 1rem;
            border-top-left-radius: 0.5rem !important;
            border-bottom-left-radius: 0.5rem !important;
        }
        .input-group-text.icon-append { /* For icons on the right, like password toggle */
            border-right: 1px solid #ced4da;
            border-left: none;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            border-top-right-radius: 0.5rem !important;
            border-bottom-right-radius: 0.5rem !important;
        }
        .input-group .form-control {
            border-top-left-radius: 0 !important; /* When icon is on left */
            border-bottom-left-radius: 0 !important;
        }
         .input-group .form-control.no-left-icon { /* When no icon on left */
            border-top-left-radius: 0.5rem !important;
            border-bottom-left-radius: 0.5rem !important;
        }


        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
        }
        .btn-primary:focus {
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.5);
        }

        .alert {
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .text-center a {
            color: #007bff;
            font-weight: 500;
            text-decoration: none;
        }
        .text-center a:hover {
            text-decoration: underline;
        }

        /* Dynamic Background Styles */
        .background-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2; /* Ensure it's behind all content */
            overflow: hidden;
            transition: background 4s ease-in-out; /* Adjusted: was background-color 1s */
        }
        .sun, .moon {
            position: absolute;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            transition: all 4s ease-in-out; /* Adjusted: was opacity 1s, transform 1s */
        }
        .sun {
            background-color: #FFD700; /* Gold */
            box-shadow: 0 0 30px #FFD700;
            top: 10%;
            left: 10%;
            opacity: 0; /* Initial state, JS will control */
        }
        .moon {
            background-color: #f5f5f5; /* Light grey */
            box-shadow: 0 0 20px #f5f5f5, inset -15px -15px 0 0 #ccc; /* Crater effect */
            top: 10%;
            right: 10%;
            opacity: 0; /* Initial state, JS will control */
        }
        .star {
            position: absolute;
            width: 3px;
            height: 3px;
            background-color: white;
            border-radius: 50%;
            animation: twinkle 2s infinite alternate;
        }
        @keyframes twinkle {
            from { opacity: 0; }
            to { opacity: 0.8; }
        }
        .cloud {
            position: absolute;
            background: #fff;
            border-radius: 50%; /* Simplified cloud shape */
            opacity: 0.6; /* Base opacity for day/morning */
            transition: opacity 3s ease-in-out; /* Adjusted: was 1s */
        }

        /* Time-based themes */
        .background-layer.morning { background: linear-gradient(to bottom, #87CEEB, #FFDAB9); } /* Sky blue to light peach */
        .background-layer.day { background: linear-gradient(to bottom, #87CEFA, #ADD8E6); } /* Light sky blue to lighter blue */
        .background-layer.evening { background: linear-gradient(to bottom, #4682B4, #FF7F50); } /* Steel blue to coral */
        .background-layer.night { background: linear-gradient(to bottom, #000033, #191970); } /* Dark blue to midnight blue */

        .morning .sun { transform: translate(20vw, 10vh) scale(1.2); }
        .day .sun { transform: translate(20vw, 10vh) scale(1.2); }
        .evening .sun { transform: translate(70vw, 60vh) scale(1); }
        .night .moon { transform: translate(-20vw, 10vh) scale(1.1); }

        .morning .cloud, .day .cloud { opacity: 0.6; }
        .evening .cloud { opacity: 0.4; }
        .night .cloud { opacity: 0.2; }

        /* Page transition styles */
        body.page-transition {
            opacity: 0;
            transition: opacity 1s ease-in-out; /* Adjusted: was 0.7s */
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
    <div class="background-layer" id="backgroundLayer">
        <div class="sun" id="sun"></div>
        <div class="moon" id="moon"></div>
        <div id="starsContainer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; opacity: 0; transition: opacity 3s ease-in-out;"></div>
        <div class="cloud c1"></div>
        <div class="cloud c2"></div>
        <div class="cloud c3"></div>
    </div>

    <div class="register-container">
        <div class="card">
            <div class="card-header">
                <img src="assets/logosmk.png" alt="Logo SMK">
                <h2>Buat Akun Baru</h2>
                <p>Bergabunglah dengan Bumi Library</p>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php // The success message is now handled by redirecting to login.php with a GET parameter ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="registerForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Pilih username Anda" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="passwordInput" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" name="password" id="passwordInput" placeholder="Minimal 6 karakter" required>
                            <span class="input-group-text icon-append" id="togglePassword" style="cursor: pointer;">
                                <i class="bi bi-eye-fill" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirmPasswordInput" class="form-label">Konfirmasi Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock-fill"></i></span>
                            <input type="password" class="form-control" name="konfirmasi_password" id="confirmPasswordInput" placeholder="Ulangi password Anda" required>
                            <span class="input-group-text icon-append" id="toggleConfirmPassword" style="cursor: pointer;">
                                <i class="bi bi-eye-fill" id="toggleConfirmPasswordIcon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button class="btn btn-primary" type="submit">DAFTAR</button>
                    </div>
                </form>

                <p class="mt-4 mb-0 text-center small">
                    Sudah punya akun? <a href="login.php" id="loginLink">Login di sini</a>
                </p>
                <p class="mt-2 mb-0 text-center text-muted" style="font-size: 0.8rem;">&copy; Bumi Library <3 <?php echo date("Y"); ?></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('fade-in');

            // Dynamic Background Logic
            const backgroundLayer = document.getElementById('backgroundLayer');
            const sun = document.getElementById('sun');
            const moon = document.getElementById('moon');
            const starsContainer = document.getElementById('starsContainer'); // New

            function updateTimePhase(phase) {
                if (!backgroundLayer) return;
                backgroundLayer.className = 'background-layer '; // Reset classes
                backgroundLayer.classList.add(phase);

                // Control sun and moon opacity directly
                sun.style.opacity = (phase === 'morning' || phase === 'day' || phase === 'evening') ? '1' : '0';
                moon.style.opacity = (phase === 'night') ? '1' : '0';

                // Control stars visibility
                if (phase === 'night') {
                    starsContainer.style.opacity = '1';
                    generateStars();
                } else {
                    starsContainer.style.opacity = '0';
                    clearStars();
                }
            }

            function generateStars() {
                if (!starsContainer) return;
                starsContainer.innerHTML = ''; // Clear previous stars
                for (let i = 0; i < 100; i++) { // Generate 100 stars
                    const star = document.createElement('div');
                    star.className = 'star'; // Uses existing .star CSS for twinkle etc.
                    star.style.top = Math.random() * 100 + '%';
                    star.style.left = Math.random() * 100 + '%';
                    star.style.animationDelay = Math.random() * 5 + 's';
                    star.style.animationDuration = (Math.random() * 3 + 2) + 's'; // Twinkle duration 2-5s
                    starsContainer.appendChild(star);
                }
            }

            function clearStars() {
                if (!starsContainer) return;
                starsContainer.innerHTML = '';
            }

            const currentHour = new Date().getHours();
            // Adjusted time definitions to match login.php
            if (currentHour >= 5 && currentHour < 8) { // Morning: 5 AM - 7:59 AM
                updateTimePhase('morning');
            } else if (currentHour >= 8 && currentHour < 17) { // Day: 8 AM - 4:59 PM (Adjusted from < 18)
                updateTimePhase('day');
            } else if (currentHour >= 17 && currentHour < 20) { // Evening: 5 PM - 7:59 PM (Adjusted from >= 18)
                updateTimePhase('evening');
            } else { // Night
                updateTimePhase('night');
            }

            // Password toggle logic (existing)
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('passwordInput');
            const togglePasswordIcon = document.getElementById('togglePasswordIcon');

            if (togglePassword) {
                togglePassword.addEventListener('click', function () {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    togglePasswordIcon.classList.toggle('bi-eye-fill');
                    togglePasswordIcon.classList.toggle('bi-eye-slash-fill');
                });
            }
            // ... existing code for confirm password toggle ...
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const confirmPasswordInput = document.getElementById('confirmPasswordInput');
            const toggleConfirmPasswordIcon = document.getElementById('toggleConfirmPasswordIcon');

            if (toggleConfirmPassword) {
                toggleConfirmPassword.addEventListener('click', function () {
                    const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmPasswordInput.setAttribute('type', type);
                    toggleConfirmPasswordIcon.classList.toggle('bi-eye-fill');
                    toggleConfirmPasswordIcon.classList.toggle('bi-eye-slash-fill');
                });
            }

            // Page transition logic (existing)
            const loginLink = document.getElementById('loginLink');
            if(loginLink) {
                loginLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.remove('fade-in'); // Ensure it's not trying to fade in if already faded
                    document.body.classList.add('fade-out');
                    setTimeout(function() {
                        window.location.href = loginLink.href;
                    }, 1500); // Adjusted: was 700, matches CSS transition time
                });
            }
        });
    </script>
</body>
</html>
