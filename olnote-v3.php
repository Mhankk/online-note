<?php
/*
Author: Hakim Winahyu
Description: A simple online note-taking application that allows users to create, store, and view text-based notes.
Features:
    - Basic note creation and storage in text files.
    - Input sanitization to help prevent XSS vulnerabilities.
    - CSRF protection to prevent cross-site request forgery attacks.
    - Simple CAPTCHA to reduce bot submissions.
    - Displaying a limited number of existing notes (maximum 5).
    - User-friendly message display for success and error conditions.
    - Object-Oriented Programming (OOP) structure for organization.
    - Responsive check for folder existence.
    - Theme toggle (light/dark mode).
    - AJAX form submission.
*/

// Start session and process theme toggle
session_start();
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

class NoteManager {
    private $txt_folder = "txt";
    private $message = "";

    public function __construct() {
        // CSRF token generation & field prefix
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        if (empty($_SESSION['field_prefix'])) {
            $_SESSION['field_prefix'] = bin2hex(random_bytes(8));
        }

        // Create the directory if it doesn't exist, with permissions
        if (!is_dir($this->txt_folder)) {
            mkdir($this->txt_folder, 0755, true);
        }
    }

    private function sanitizeInput($input) {
        $input = trim($input);
        $input = stripslashes($input);
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    public function handleFormSubmission() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $this->message = "<div class='alert alert-danger'>Invalid request.</div>";
                $this->ajaxReturn();
            }

            $prefix = $_SESSION['field_prefix'];
            if (isset($_POST[$prefix . 'captcha']) && isset($_SESSION["captcha_answer"]) && $_POST[$prefix . 'captcha'] == $_SESSION["captcha_answer"]) {
                $filename = basename($this->sanitizeInput($_POST[$prefix . 'filename']));
                $content = $this->sanitizeInput($_POST[$prefix . 'content']);
                $filename = preg_replace('/[^a-zA-Z0-9\_\-\.]/', '', $filename); // Sanitize filename

                if (empty($filename)) {
                    $this->message = "<div class='alert alert-danger'>Filename cannot be empty.</div>";
                } elseif (strlen($filename) > 50) {
                    $this->message = "<div class='alert alert-danger'>Filename is too long.</div>";
                } else {
                    $filepath = $this->txt_folder . "/" . $filename . ".txt";
                    // Check for existing file and append a counter if necessary
                    $counter = 1;
                    $base_filename = $filename;
                    while (file_exists($filepath)) {
                        $filename = $base_filename . "-" . $counter;
                        $filepath = $this->txt_folder . "/" . $filename . ".txt";
                        $counter++;
                    }
                    if (file_put_contents($filepath, $content) !== false) {
                        $file_link = $this->txt_folder . "/" . $filename . ".txt";
                        $this->message = "<div class='alert alert-success'>Note saved successfully! <a href='" . $file_link . "' target='_blank'>View Note</a></div>";
                    } else {
                        $this->message = "<div class='alert alert-danger'>Error saving note. Please check folder permissions.</div>";
                    }
                }
            } else {
                $this->message = "<div class='alert alert-danger'>Incorrect CAPTCHA. Please try again.</div>";
            }
            $this->ajaxReturn();
        }
    }

    // If the request is AJAX, return only the message and exit.
    private function ajaxReturn() {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo $this->message;
            exit;
        }
    }

    public function displayNotes() {
        echo "<h2>Existing Notes</h2><ul>";
        if (is_dir($this->txt_folder)) {
            $files = scandir($this->txt_folder);
            $file_count = 0;
            // Sort files by modification time (newest first)
            usort($files, function($a, $b) {
                $path_a = $this->txt_folder . "/" . $a;
                $path_b = $this->txt_folder . "/" . $b;
                return filemtime($path_b) - filemtime($path_a);
            });
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $safe_filename = basename($file);
                    echo "<li><a href='" . $this->txt_folder . "/" . $safe_filename . "' target='_blank'>" . htmlspecialchars($safe_filename, ENT_QUOTES, 'UTF-8') . "</a></li>";
                    $file_count++;
                    if ($file_count >= 5) {
                        break;
                    }
                }
            }
            if ($file_count === 0) {
                echo "<li>No notes found.</li>";
            }
        } else {
            echo "<li>No notes found.</li>";
        }
        echo "</ul>";
    }

    public function getMessage() {
        return $this->message;
    }

    public function generateCaptcha() {
        $num1 = random_int(1, 10);
        $num2 = random_int(1, 10);
        $captcha_question = "What is " . $num1 . " + " . $num2 . "?";
        $captcha_answer = $num1 + $num2;
        $_SESSION["captcha_answer"] = $captcha_answer;
        return $captcha_question;
    }

    public function getPrefix() {
        return $_SESSION['field_prefix'];
    }

    public function getCsrfToken() {
        return $_SESSION['csrf_token'];
    }
}

$noteManager = new NoteManager();
$noteManager->handleFormSubmission(); // Process form submission before output

$captcha_question = $noteManager->generateCaptcha();
$prefix = $noteManager->getPrefix();
$csrf_token = $noteManager->getCsrfToken();
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
            body { padding-top: 20px; background-color: #343a40; color: #f8f9fa; }
            a, label, h1, h2, p { color: #f8f9fa; }
        </style>
    <?php else: ?>
        <!-- Light Mode: Default Bootstrap -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <style>
            body { padding-top: 20px; background-color: #fff; color: #212529; }
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
        <p><em>Don't store any sensitive information</em></p>
        <div class="message-note">
            <?php echo $noteManager->getMessage(); ?>
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
        <?php $noteManager->displayNotes(); ?>
    </div>

    <!-- Include full jQuery for AJAX support -->
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
                    url: "", // Submit to the same page
                    data: $(this).serialize(),
                    success: function(response) {
                        $(".message-note").html(response);
                        if(response.indexOf("alert-success") !== -1){
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
