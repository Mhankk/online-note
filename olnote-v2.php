<?php
/*
Author: Hakim Winahyu
Description: A simple Online Notes application that allows users to create, store, and view text-based notes.
Features:
    - Basic note creation and storage in text files within a designated directory.
    - Input sanitization to mitigate basic XSS vulnerabilities.
    - CSRF protection using a token.
    - A simple CAPTCHA to deter basic bot submissions.
    - Dynamic form field names (using a session-based prefix) to make automated form submission slightly more difficult.
    - Displaying a limited number of existing notes (maximum 5).
    - Responsive folder check to create the folder if it doesn't exist.
    - Theme toggle feature for dark (Bootswatch Darkly) and light mode.
    - AJAX submission for form handling.
*/

session_start();

// Theme toggle handling via GET parameter
if (isset($_GET['theme'])) {
    $theme = ($_GET['theme'] === 'light') ? 'light' : 'dark';
    $_SESSION['theme'] = $theme;
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'dark';
}
$current_theme = $_SESSION['theme'];

$txt_folder = "txt";
// Check if the txt folder exists; if not, create it.
if (!is_dir($txt_folder)) {
    mkdir($txt_folder, 0755, true);
}

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
        // If AJAX, return message and exit
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo $message;
            exit;
        }
        exit;
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

    // If this is an AJAX request, output only the message and exit
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo $message;
        exit;
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
    <?php if ($current_theme === 'dark'): ?>
        <!-- Dark Mode: Bootswatch Darkly Theme -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.5.2/darkly/bootstrap.min.css">
        <style>
            body {
                padding-top: 20px;
                background-color: #343a40;
                color: #f8f9fa;
            }
            a, label, h1, h2, p {
                color: #f8f9fa;
            }
        </style>
    <?php else: ?>
        <!-- Light Mode: Default Bootstrap -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <style>
            body {
                padding-top: 20px;
                background-color: #fff;
                color: #212529;
            }
        </style>
    <?php endif; ?>
</head>

<body>
    <div class="container">
        <div class="d-flex justify-content-end mt-2">
            <?php if ($current_theme === 'dark'): ?>
                <a href="?theme=light" class="btn btn-secondary btn-sm">Switch to Light Mode</a>
            <?php else: ?>
                <a href="?theme=dark" class="btn btn-dark btn-sm">Switch to Dark Mode</a>
            <?php endif; ?>
        </div>
        <h1>Online Notes</h1>
        <p><em>Don't store sensitive information!</em></p>
        <div class="message-note">
            <?php echo $message; ?>
        </div>

        <form id="noteForm" method="post">
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
                $files = array_diff(scandir($txt_folder), ['.', '..']);
                // Sort files by modification time (newest first)
                usort($files, function ($a, $b) use ($txt_folder) {
                    return filemtime($txt_folder . "/" . $b) - filemtime($txt_folder . "/" . $a);
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

    <!-- Include full jQuery (for AJAX support) -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <?php if ($current_theme === 'dark'): ?>
        <script src="https://stackpath.bootstrapcdn.com/bootswatch/4.5.2/darkly/bootstrap.min.js"></script>
    <?php else: ?>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <?php endif; ?>
    <script>
        $(document).ready(function(){
            $("#noteForm").on("submit", function(e){
                e.preventDefault(); // Prevent default form submission

                $.ajax({
                    type: "POST",
                    url: "",
                    data: $(this).serialize(),
                    success: function(response) {
                        $(".message-note").html(response);
                        if (response.indexOf("alert-success") !== -1) {
                            $("#noteForm")[0].reset();
                        }
                    },
                    error: function() {
                        $(".message-note").html("<div class='alert alert-danger'>An error occurred. Please try again.</div>");
                    }
                });
            });
        });
    </script>
</body>

</html>
