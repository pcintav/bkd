<?php
// Start the session
session_start();

// Regenerate session ID for security
session_regenerate_id(true);

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Define trusted URLs for redirection
$trustedUrls = array(
  "https://bkd.sistm.app/login?out=user",  // Login page
  "https://bkd.sistm.app/index",  // Home page
);

// Validate redirect URL
$redirectUrl = isset($_GET['out']) ? validateRedirectUrl($_GET['out'], $trustedUrls) : false;

// Redirect to validated URL or error page
if ($redirectUrl !== false) {
  header("Location: " . $redirectUrl);
  exit();
} else {
  header("Location: https://bkd.sistm.app/login?out=user");  // Redirect to error page
  exit();
}

// Function to validate redirect URL
function validateRedirectUrl($url, $trustedUrls) {
  // Sanitize URL
  $url = filter_var($url, FILTER_SANITIZE_URL);

  // Check for valid protocol
  if (!preg_match('/^https:\/\//', $url)) {
    return false;
  }

  // Check if URL is in trusted list
  if (in_array($url, $trustedUrls)) {
    return $url;
  }

  // Return false for invalid URLs
  return false;
}
?>
