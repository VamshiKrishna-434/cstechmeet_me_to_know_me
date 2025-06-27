
<?php
header('Content-Type: application/json');
// Simple rate limiting
session_start();

if (!isset($_SESSION['last_submit_time'])) {
    $_SESSION['last_submit_time'] = 0;
}

$currentTime = time();
$timeSinceLastSubmit = $currentTime - $_SESSION['last_submit_time'];

if ($timeSinceLastSubmit < 30) { // 30 seconds between submissions
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Please wait before submitting another message']);
    exit;
}

$_SESSION['last_submit_time'] = $currentTime;
// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['name']) || empty($data['email']) || empty($data['subject']) || empty($data['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Sanitize inputs
$name = filter_var($data['name'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$subject = filter_var($data['subject'], FILTER_SANITIZE_STRING);
$message = filter_var($data['message'], FILTER_SANITIZE_STRING);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Email configuration
$to = 'vamshikrishna43496@gmail.com'; // Your email address
$headers = [
    'From' => $email,
    'Reply-To' => $email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'Content-type' => 'text/html; charset=utf-8'
];

// Email content
$emailContent = "
    <html>
    <head>
        <title>New Contact Form Submission: $subject</title>
    </head>
    <body>
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Subject:</strong> $subject</p>
        <p><strong>Message:</strong></p>
        <p>$message</p>
    </body>
    </html>
";

// Send email
$mailSent = mail($to, $subject, $emailContent, $headers);

if ($mailSent) {
    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Oops! Something went wrong. Please try again later.']);
}
?>