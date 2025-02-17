<?php
/*
Author: Hakim Winahyu
Description: Online Notes application that allows users to create, store, and view simple text-based notes. This application prioritizes security by implementing several measures to protect against common web vulnerabilities, including Cross-Site Request Forgery (CSRF), Cross-Site Scripting (XSS), and Directory Traversal attacks. It also incorporates a simple CAPTCHA to mitigate bot submissions and limits the display of existing notes for a cleaner user experience.  This script uses plain text files for storage.

Features:
    - Secure note creation and storage in individual text files within a designated directory ("txt").
    - Input sanitization using `sanitizeInput()` function to prevent XSS vulnerabilities by trimming whitespace, removing slashes, and encoding special characters.
    - Directory traversal protection by using `basename()` on user-provided filenames and further sanitizing them with a regular expression to allow only alphanumeric characters, underscores, hyphens, and periods. File paths are constructed securely.
    - CSRF protection through the use of a unique token generated for each session and validated on form submission.
    - CAPTCHA implementation using a simple arithmetic question to deter automated submissions. The answer is stored in a session variable for validation.
    - Duplicate filename handling by appending a counter to the filename if a file with the same name already exists.
    - File size limitation by checking if the filename exceeds a maximum length (50 characters).
    - Error handling and user feedback through informative alert messages for various scenarios (success, invalid input, CAPTCHA failure, file saving errors, empty filename).
    - Displaying a limited number of existing notes (maximum 5) to enhance usability and prevent overwhelming the user with a large list.
    - User-friendly interface using Bootstrap for styling and layout.
    - Real-time display of success and error messages via the $message variable.

Limitations:
    - Simple text-based notes only. No support for rich text formatting or attachments.
    - Limited number of displayed notes (5).  No pagination or search functionality.
    - Basic CAPTCHA implementation.  May not be robust against determined bots.
    - Relies on file system for storage.  No database integration.
    - Error handling is basic.  More detailed logging or error reporting could be implemented.
    - Security measures, while present, should be reviewed by a security professional for comprehensive protection.
*/

session_start();

$txt_folder = "txt";

function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        $message = "<div class='alert alert-danger'>Invalid request.</div>";
        exit; // Stop processing the request
    }

    if (isset($_POST["captcha"]) && isset($_SESSION["captcha_answer"]) && $_POST["captcha"] == $_SESSION["captcha_answer"]) {

        $filename = basename(sanitizeInput($_POST["filename"])); // Directory traversal protection
        $content = sanitizeInput($_POST["content"]);

        $filename = preg_replace('/[^a-zA-Z0-9\_\-\.]/', '', $filename); // Sanitize filename further

        $base_filename = $filename;
        $counter = 1;

        do {
            if ($counter > 1) {
                $filename = $base_filename . "-" . $counter;
            }
            $filepath = $txt_folder . "/" . $filename . ".txt";
            $counter++;
        } while (file_exists($filepath));

        if (empty($filename)) {
            $message = "<div class='alert alert-danger'>Filename cannot be empty.</div>";
        } else if (strlen($filename) > 50) {
            $message = "<div class='alert alert-danger'>Filename is too long.</div>";
        } else if (file_put_contents($filepath, $content) !== false) {
            $file_link = $txt_folder . "/" . $filename . ".txt";
            $message = "<div class='alert alert-success'>Note saved successfully!  <a href='" . $file_link . "' target='_blank'>View Note</a></div>";
        } else {
            $message = "<div class='alert alert-danger'>Error saving note. Please check folder permissions.</div>";
        }

    } else {
        $message = "<div class='alert alert-danger'>Incorrect CAPTCHA. Please try again.</div>";
    }
}


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
        <p><em>Don't store any sensitive information</em></p>
        <div class="message-note">
            <?php echo $message; ?>
        </div>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">  <div class="form-group">
                <label for="filename">Filename:</label>
                <input type="text" class="form-control" id="filename" name="filename" required>
            </div>
            <div class="form-group">
                <label for="content">Note Content:</label>
                <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
            </div>

            <div class="form-group">
                <label for="captcha"><?php echo $captcha_question; ?></label>
                <input type="text" class="form-control" id="captcha" name="captcha" required>
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
