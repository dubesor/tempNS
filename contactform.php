<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Constants
    define('RECIPIENT_EMAIL', 'info@gmxolaischmidt.de');
    define('DEFAULT_LANG', 'de');

    // Basic sanitization
    $name = htmlspecialchars(strip_tags(trim($_POST["name"])));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(trim($_POST["message"]));
    $lang = isset($_POST["lang"]) ? htmlspecialchars(trim($_POST["lang"])) : DEFAULT_LANG;
    
    // Identifiers
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    date_default_timezone_set('Europe/Berlin');
    $timestamp = $_SERVER['REQUEST_TIME'];
    $formatted_timestamp = date('D M j Y H:i:s O', $timestamp);

    // Messages
    $messages = [
        'name_invalid' => [
            'de' => "Ein sehr ungewöhnlicher Name.",
            'en' => "That's an unusual name."
        ],
        'message_too_short' => [
            'de' => "Die Nachricht muss mindestens 10 Zeichen lang sein.",
            'en' => "The message must be at least 10 characters long."
        ],
        'message_too_long' => [
            'de' => "Romane bitte per Post schicken!",
            'en' => "Please send novels by letter post!"
        ],
        'email_invalid' => [
            'de' => "Bitte gültige E-Mail-Adresse angeben.",
            'en' => "Please enter a valid email address."
        ],
        'rate_limit' => [
            'de' => "Zu viele Anfragen. Bitte noch %d Sekunden warten.",
            'en' => "Too many requests. Please wait another %d seconds."
        ],
        'honeypot_triggered' => [
            'de' => "Okay.",
            'en' => "OK."
        ],
        'fields_missing' => [
            'de' => "Bitte alle Felder ausfüllen.",
            'en' => "Please fill out all fields."
        ],
        'header_injection' => [
            'de' => "Ungültige Eingabe.",
            'en' => "Invalid input."
        ],
        'message_sent' => [
            'de' => "Ihre Nachricht wurde gesendet.",
            'en' => "Your message has been sent."
        ],
        'message_failed' => [
            'de' => "Es gab ein Problem beim Versenden der Nachricht. Bitte später erneut versuchen.",
            'en' => "There was a problem sending your message. Please try again later."
        ],
        'request_error' => [
            'de' => "Es gab ein Problem mit der Anfrage. Bitte erneut versuchen.",
            'en' => "There was a problem with your request. Please try again."
        ]
    ];

    // Validate name length
    if (strlen($name) > 60 || strlen($name) < 3) {
        http_response_code(400);
        echo $messages['name_invalid'][$lang];
        exit;
    }

    // Validate message length
    if (strlen($message) < 10) {
        http_response_code(400);
        echo $messages['message_too_short'][$lang];
        exit;
    }

    if (strlen($message) > 10000) {
        http_response_code(400);
        echo $messages['message_too_long'][$lang];
        exit;
    }

    // Validate email length and format
    if (strlen($email) > 80 || strlen($email) < 6 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo $messages['email_invalid'][$lang];
        exit;
    }

    // Rate limiting using PHP sessions
    if (!isset($_SESSION['last_submission_time'])) {
        $_SESSION['last_submission_time'] = time();
    } else {
        $time_since_last_submission = time() - $_SESSION['last_submission_time'];
        $remaining_time = 600 - $time_since_last_submission;
        if ($time_since_last_submission < 600) { // Limit to 1 submission per 10 minutes
            http_response_code(429);
            printf($messages['rate_limit'][$lang], $remaining_time);
            exit;
        }
        $_SESSION['last_submission_time'] = time();
    }

    // Honeypot field
    if (!empty($_POST["website"])) {
        http_response_code(400);
        echo $messages['honeypot_triggered'][$lang];
        exit;
    }

    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo $messages['fields_missing'][$lang];
        exit;
    }

    // Prevent email header injection
    if (preg_match("/[\r\n]/", $name) || preg_match("/[\r\n]/", $email)) {
        http_response_code(400);
        echo $messages['header_injection'][$lang];
        exit;
    }

    $subject = $lang === 'de' ? "Neue Kontaktanfrage von $name" : "New contact request from $name";

    $email_content = $lang === 'de'
        ? "Name: $name\nE-Mail: $email\nUhrzeit: $formatted_timestamp\n\nIP: https://www.iplocation.net/ip-lookup/$ip_address \nAgent: $user_agent\n\nNachricht:\n$message\n"
        : "Name: $name\nEmail: $email\nTime: $formatted_timestamp\n\nIP: https://www.iplocation.net/ip-lookup/$ip_address \nAgent: $user_agent\n\nMessage:\n$message\n";

    $email_headers = "From: $name <$email>\r\n";
    $email_headers .= "Reply-To: $email\r\n";
    $email_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Send email
    if (mail(RECIPIENT_EMAIL, $subject, $email_content, $email_headers)) {

		// Get environment variables config file
		require_once '../../priv/config.php';
		$dbHost   = DB_HOST;
		$dbUsername   = DB_USERNAME;
		$dbPassword   = DB_PASSWORD;
		$dbName   = DB_NAME;

		// Create connection
		$conn  = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Insert data into the database
		$stmt = $conn->prepare("INSERT INTO submissions (name, email, message, lang, user_agent, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("ssssss", $name, $email, $message, $lang, $user_agent, $ip_address);

		if ($stmt->execute()) {
			echo $messages['message_sent'][$lang];
		} else {
			echo "Error: " . $stmt->error;
		}

		$stmt->close();
		$conn->close();

    } else {
        http_response_code(500);
        echo $messages['message_failed'][$lang];
    }
} else {
    http_response_code(403);
    echo $messages['request_error'][$lang];
}
?>
