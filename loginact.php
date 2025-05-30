<?php
session_start();

// Securely check for direct access to loginact.php
if (stripos($_SERVER['SCRIPT_NAME'], 'loginact.php') !== false) {
    header('Location: /index');
}

require_once 'includes/Dtbase.php';

// Secure session settings
session_regenerate_id(true);
//ini_set('session.cookie_httponly', 1);

// Database connection with error handling
try {
    $dbConnection = new DatabaseConnection();
    $conn = $dbConnection->getConnection();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    $_SESSION['xmessage'] = "Terjadi kesalahan sistem. Mohon coba kembali.";
    header('Location: /index');
    exit();
}

// Check if user is temporarily blocked
if (isset($_SESSION['block_time']) && time() < $_SESSION['block_time']) {
    $remainingTime = $_SESSION['block_time'] - time();
    $_SESSION['xmessage'] = "Anda telah gagal login sebanyak 3 kali. Silakan tunggu " . gmdate("i:s", $remainingTime) . " menit.";
    header('Location: /index');
    exit();
}

if (isset($_POST['login'])) {
    // Increment or reset login attempts counter
    if (!isset($_SESSION['login_attempts']) || !isset($_SESSION['last_attempt_time']) || time() - $_SESSION['last_attempt_time'] > 300) {
        // Reset attempts after 5 minutes of inactivity
        $_SESSION['login_attempts'] = 1;
    } else {
        $_SESSION['login_attempts']++;
    }

    // Update last attempt time
    $_SESSION['last_attempt_time'] = time();

    // Strict input validation
    $nidn = filter_var(trim($_POST['nidn']), FILTER_SANITIZE_NUMBER_INT);

    if (!preg_match('/^[0-9]{10}$/', $nidn)) {
        // Check if this is the third failed attempt
        if ($_SESSION['login_attempts'] >= 3) {
            $_SESSION['block_time'] = time() + 600; // Block the user for 10 minutes
            $_SESSION['xmessage'] = "Anda telah gagal login sebanyak 3 kali. Silakan tunggu 10 menit.";
            header('Location: /index');
            exit();
        } else {
            $remainingAttempts = 3 - $_SESSION['login_attempts'];
            $_SESSION['xmessage'] = "Jumlah angka NIDN harus lengkap dan hanya terdiri angka. Sisa percobaan login: $remainingAttempts";
            header('Location: /index');
            exit();
        }
    }

    $passwordAttempt = trim($_POST['passwd']);

    if (empty($nidn) || empty($passwordAttempt)) {
        $_SESSION['xmessage'] = "Isi semua kolom NIDN dan password. Sisa percobaan login: $remainingAttempts";
        header('Location: /index');
        exit();
    }

    try {
        $sql = "SELECT user_id, nidn, passwd, nama FROM users_bkd WHERE nidn = :nidn";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':nidn', $nidn, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        $_SESSION['xmessage'] = "Terjadi kesalahan sistem. Mohon coba kembali.";
        header('Location: /index');
        exit();
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if this is the third failed attempt
    if ($_SESSION['login_attempts'] >= 3) {
        $_SESSION['block_time'] = time() + 600; // Block the user for 10 minutes
        $_SESSION['xmessage'] = "Anda telah gagal login sebanyak 3 kali. Silakan tunggu 10 menit.";
        header('Location: /index');
        exit();
    }

    if ($user && password_verify($passwordAttempt, $user['passwd'])) {
        // Reset login attempts on successful login
        unset($_SESSION['login_attempts']);
        unset($_SESSION['last_attempt_time']);

        // Store essential user information in the session
        $_SESSION['isLoggedIn'] = true;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['nidn'] = $nidn;
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['message'] = "Sukses Masuk Aplikasi BKD";
        header("Location: https://bkd.sistm.app/user/dasbor?dash=$nidn");
        exit(0);
    }

    // If execution reaches here, it means the login failed
    $remainingAttempts = 3 - $_SESSION['login_attempts'];
    $_SESSION['xmessage'] = "NIDN atau password salah. Sisa percobaan login: $remainingAttempts";
    header('Location: /index');
    exit();
}
?>