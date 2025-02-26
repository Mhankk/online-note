<?php
/*
Author: Hakim Winahyu
Description: Online Notes application that allows users to create, store, and view simple text-based notes.
Features:
    - Secure note creation and storage in individual text files within a designated directory ("txt").
    - Input sanitization using `sanitizeInput()` function to prevent XSS vulnerabilities.
    - Directory traversal protection by using `basename()` and additional sanitization.
    - CSRF protection using a unique token per session.
    - CAPTCHA implementation using a simple arithmetic question.
    - Duplicate filename handling by appending a counter to the filename if necessary.
    - File size limitation by checking if the filename exceeds a maximum length.
    - Error handling and user feedback through informative alert messages.
    - Displaying a limited number of existing notes (maximum 5).
    - User-friendly interface using Bootstrap with a responsive layout.
    - AJAX submission for note creation/editing.
    - Theme toggle feature for dark mode (using Bootswatch’s Darkly) and white mode (default Bootstrap).
*/

session_start();

// Process theme selection via GET parameter and store in session
if (isset($_GET['theme'])) {
    $theme = ($_GET['theme'] === 'light') ? 'light' : 'dark';
    $_SESSION['theme'] = $theme;
    // Redirect to remove the GET parameter from the URL
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Default to dark mode if not set
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'dark';
}
$current_theme = $_SESSION['theme'];

$txt_folder = "txt";
// Create the txt folder if it doesn't exist
if (!is_dir($txt_folder)) {
    mkdir($txt_folder, 0755, true);
}

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
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo $message;
            exit;
        }
        exit;
    }

    if (isset($_POST["captcha"]) && isset($_SESSION["captcha_answer"]) && $_POST["captcha"] == $_SESSION["captcha_answer"]) {

        $filename = basename(sanitizeInput($_POST["filename"])); // Directory traversal protection
        $content = sanitizeInput($_POST["content"]);

        $filename = preg_replace('/[^a-zA-Z0-9\_\-\.]/', '', $filename); // Further sanitize filename

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
            $message = "<div class='alert alert-success'>Note saved successfully! <a href='" . $file_link . "' target='_blank'>View Note</a></div>";
        } else {
            $message = "<div class='alert alert-danger'>Error saving note. Please check folder permissions.</div>";
        }

    } else {
        $message = "<div class='alert alert-danger'>Incorrect CAPTCHA. Please try again.</div>";
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo $message;
        exit;
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
    <?php if ($current_theme === 'dark'): ?>
        <!-- Bootswatch Darkly Theme for Dark Mode -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.5.2/darkly/bootstrap.min.css">
        <style>
            body {
                padding-top: 20px;
                background-color: #343a40;
                color: #f8f9fa;
            }
            .table, a, label, h1, h2, p {
                color: #f8f9fa;
            }
        </style>
    <?php else: ?>
        <!-- Default Bootstrap for White Mode -->
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
        <p><em>Don't store any sensitive information</em></p>
        <div class="message-note">
            <?php echo $message; ?>
        </div>

        <form id="noteForm" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
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
                $files = array_diff(scandir($txt_folder), ['.', '..']);
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

    <!-- Use full jQuery (not slim) for AJAX support -->
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
                e.preventDefault();
                $.ajax({
                    type: "POST",
                    url: "",
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
