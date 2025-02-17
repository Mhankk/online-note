<?php

/*
Author: Hakim Winahyu
Description: A simple Online Notes application that allows users to create, store, and view text-based notes.  While basic security measures are implemented, this application is for demonstration purposes only and should NOT be used for storing sensitive information.  It demonstrates input sanitization, CSRF protection, a basic CAPTCHA, and limits the display of existing notes for a cleaner UI.  It is crucial to understand that this application is NOT fully secure and requires further hardening before being deployed in a production environment.

Features:
    - Basic note creation and storage in text files within a designated directory.
    - Input sanitization to mitigate basic XSS vulnerabilities (but not comprehensive).
    - CSRF protection using a token to prevent cross-site request forgery.
    - A simple CAPTCHA to deter basic bot submissions.
    - Dynamic form field names (using a session-based prefix) to make automated form submission slightly more difficult.
    - Displaying a limited number of existing notes (maximum 5) for a cleaner user interface.
    - Basic user-friendly message display for success and error conditions.

Limitations and Security Considerations:
    - This application is NOT robustly secure.  It is vulnerable to various attacks, including but not limited to more sophisticated XSS attacks, potential file inclusion vulnerabilities if not properly configured, and brute-force attacks against the simple CAPTCHA.
    - File permissions are crucial.  The "txt" directory should have restrictive permissions to prevent unauthorized access.
    - The CAPTCHA is very basic and easily bypassed by determined bots.
    - Input sanitization is basic and may not catch all malicious input.  More advanced techniques are needed for production systems.
    - The application relies on client-side validation (HTML required attribute), which can be easily bypassed.  Server-side validation is essential.
    - Error messages may reveal sensitive information and should be handled more carefully in a production environment.
    - No authentication or authorization is implemented.  All users can create and view notes.
    - The code lacks proper error handling and logging.

Disclaimer:
    This code is provided for educational and demonstrational purposes only.  It should NOT be used in a production environment without significant security enhancements and thorough testing.  The author and distributors are not liable for any damages or losses arising from the use of this code.
*/

session_start();

$txt_folder = "txt";

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Field name prefix
if (empty($_SESSION['field_prefix'])) {
    $_SESSION['field_prefix'] = bin2hex(random_bytes(8));
}
$prefix = $_SESSION['field_prefix'];

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        $message = "<div class='alert alert-danger'>Invalid request.</div>";
        exit; // Stop further processing
    }

    if (isset($_POST[$prefix . 'captcha']) && isset($_SESSION["captcha_answer"]) && $_POST[$prefix . 'captcha'] == $_SESSION["captcha_answer"]) {

        $filename = basename(sanitizeInput($_POST[$prefix . 'filename']));
        $content = sanitizeInput($_POST[$prefix . 'content']);

        $filename = preg_replace('/[^a-zA-Z0-9\_\-\.]/', '', $filename); // Further sanitize filename

        if (empty($filename)) {
            $message = "<div class='alert alert-danger'>Filename cannot be empty.</div>";
        } elseif (strlen($filename) > 50) {
            $message = "<div class='alert alert-danger'>Filename is too long.</div>";
        } else {
            $filepath = $txt_folder . "/" . $filename . ".txt";

            // Check for existing file and append counter if necessary
            $counter = 1;
            $base_filename = $filename;
            while (file_exists($filepath)) {
                $filename = $base_filename . "-" . $counter;
                $filepath = $txt_folder . "/" . $filename . ".txt";
                $counter++;
            }


            if (file_put_contents($filepath, $content) !== false) {
                $file_link = $txt_folder . "/" . $filename . ".txt";
                $message = "<div class='alert alert-success'>Note saved successfully! <a href='" . $file_link . "' target='_blank'>View Note</a></div>";
            } else {
                $message = "<div class='alert alert-danger'>Error saving note. Check folder permissions.</div>";
            }
        }

    } else {
        $message = "<div class='alert alert-danger'>Incorrect CAPTCHA.</div>";
    }
}

// CAPTCHA generation
$num1 = random_int(1, 10);
$num2 = random_int(1, 10);
$captcha_question = "What is " . $num1 . " + " . $num2 . "?";
$captcha_answer = $num1 + $num2;

$_SESSION["captcha_answer"] = $captcha_answer;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Notes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Online Notes</h1>
        <p><em>Don't store sensitive information!</em></p>
        <div class="message-note">
            <?php echo $message; ?>
        </div>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="<?php echo $prefix; ?>filename">Filename:</label>
                <input type="text" class="form-control" id="<?php echo $prefix; ?>filename" name="<?php echo $prefix; ?>filename" required>
            </div>
            <div class="form-group">
                <label for="<?php echo $prefix; ?>content">Note Content:</label>
                <textarea class="form-control" id="<?php echo $prefix; ?>content" name="<?php echo $prefix; ?>content" rows="5" required></textarea>
            </div>

            <div class="form-group">
                <label for="<?php echo $prefix; ?>captcha"><?php echo $captcha_question; ?></label>
                <input type="text" class="form-control" id="<?php echo $prefix; ?>captcha" name="<?php echo $prefix; ?>captcha" required>
            </div>

            <button type="submit" class="btn btn-primary">Save Note</button>
        </form>

        <hr>

<h2>Existing Notes</h2>
<ul>
    <?php
    if (is_dir($txt_folder)) {
        $files = array_diff(scandir($txt_folder), ['.', '..']); // Remove . and ..

        // Sort files by modification time (newest first)
        usort($files, function ($a, $b) use ($txt_folder) {
            $time_a = filemtime($txt_folder . "/" . $a);
            $time_b = filemtime($txt_folder . "/" . $b);
            return $time_b - $time_a; // Descending order
        });

        $file_count = 0;
        foreach ($files as $file) {
            $safe_filename = basename($file);
            echo "<li><a href='" . $txt_folder . "/" . $safe_filename . "' target='_blank'>" . htmlspecialchars($safe_filename, ENT_QUOTES, 'UTF-8') . "</a></li>";
            $file_count++;

            if ($file_count >= 5) {
                break;
            }
        }

        if ($file_count === 0) {
            echo "<li>No notes found.</li>";
        }

    } else {
        echo "<li>No notes found.</li>";
    }
    ?>
</ul>

    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
