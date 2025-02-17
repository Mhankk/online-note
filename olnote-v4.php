<?php

/*
Author: Hakim Winahyu
Description: A simple online note-taking application that allows users to create, store, and view text-based notes. This application includes basic security features such as input sanitization, CSRF protection, and a CAPTCHA system. Users can mark notes as Public or Private. Public notes are stored in a "txt" directory and listed for easy access, while Private notes are stored securely in a "pvt" directory with a randomly generated filename suffix and can only be accessed via a direct link. 

Features:
    - Basic note creation and storage in text files.
    - Public and Private note functionality.
    - Private notes receive a randomized filename for security.
    - Input sanitization to prevent XSS vulnerabilities.
    - CSRF protection to prevent cross-site request forgery attacks.
    - Simple CAPTCHA to reduce bot submissions.
    - Public notes are listed (max 5), while private notes remain hidden.
    - User-friendly success and error messages.
    - Object-Oriented Programming (OOP) structure for better organization.

Limitations:
    - Security measures are basic and may not be sufficient for all threats. This is a demonstration script.
    - No user authentication or advanced access control.
    - Plain text storage is not ideal for sensitive data. Consider encryption or a database for production.
    - CAPTCHA is simple and could be bypassed by sophisticated bots.
    - Private notes rely on obscurity rather than encryption for security.
*/


class NoteManager {
    private $txt_folder = "txt";
    private $pvt_folder = "pvt";
    private $message = "";

    public function __construct() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        if (empty($_SESSION['field_prefix'])) {
            $_SESSION['field_prefix'] = bin2hex(random_bytes(8));
        }

        if (!is_dir($this->txt_folder)) {
            mkdir($this->txt_folder);
        }
        if (!is_dir($this->pvt_folder)) {
            mkdir($this->pvt_folder);
        }
    }

    private function sanitizeInput($input) {
        return htmlspecialchars(trim(stripslashes($input)), ENT_QUOTES, 'UTF-8');
    }

    public function handleFormSubmission() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $this->message = "<div class='alert alert-danger'>Invalid request.</div>";
                exit;
            }

            $prefix = $_SESSION['field_prefix'];

            if (isset($_POST[$prefix . 'captcha']) && isset($_SESSION["captcha_answer"]) && $_POST[$prefix . 'captcha'] == $_SESSION["captcha_answer"]) {

                $filename = basename($this->sanitizeInput($_POST[$prefix . 'filename']));
                $content = $this->sanitizeInput($_POST[$prefix . 'content']);
                $is_private = isset($_POST[$prefix . 'is_private']);
                
                $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $filename);

                if (empty($filename)) {
                    $this->message = "<div class='alert alert-danger'>Filename cannot be empty.</div>";
                } else if (strlen($filename) > 50) {
                    $this->message = "<div class='alert alert-danger'>Filename is too long.</div>";
                } else {
                    $folder = $is_private ? $this->pvt_folder : $this->txt_folder;

                    if ($is_private) {
                        $random_suffix = bin2hex(random_bytes(4));
                        $filename .= "-" . $random_suffix;
                    }
                    
                    $filepath = "$folder/$filename.txt";
                    
                    if (file_put_contents($filepath, $content) !== false) {
                        $file_link = "$folder/$filename.txt";
                        $this->message = "<div class='alert alert-success'>Note saved successfully! <a href='$file_link' target='_blank'>View Note</a></div>";
                    } else {
                        $this->message = "<div class='alert alert-danger'>Error saving note. Please check folder permissions.</div>";
                    }
                }
            } else {
                $this->message = "<div class='alert alert-danger'>Incorrect CAPTCHA. Please try again.</div>";
            }
        }
    }

    public function displayNotes() {
        echo "<h2>Existing Notes</h2><ul>";
        if (is_dir($this->txt_folder)) {
            $files = array_diff(scandir($this->txt_folder), ['.', '..']);
            $file_count = 0;

            foreach ($files as $file) {
                echo "<li><a href='$this->txt_folder/$file' target='_blank'>$file</a></li>";
                $file_count++;
                if ($file_count >= 5) break;
            }
            if ($file_count === 0) echo "<li>No notes found.</li>";
        } else {
            echo "<li>No notes found.</li>";
        }
        echo "</ul>";
    }

    public function getMessage() { return $this->message; }
    public function generateCaptcha() {
        $num1 = random_int(1, 10);
        $num2 = random_int(1, 10);
        $_SESSION["captcha_answer"] = $num1 + $num2;
        return "What is $num1 + $num2?";
    }
    public function getPrefix() { return $_SESSION['field_prefix']; }
    public function getCsrfToken() { return $_SESSION['csrf_token']; }
}

session_start();
$noteManager = new NoteManager();
$noteManager->handleFormSubmission();
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Online Notes</h1>
        <p><em>Don't store any sensitive information</em></p>
        <div class="message-note"> <?php echo $noteManager->getMessage(); ?> </div>

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
                <input type="checkbox" id="<?php echo $prefix; ?>is_private" name="<?php echo $prefix; ?>is_private">
                <label for="<?php echo $prefix; ?>is_private">Set Private Note</label>
            </div>
            <div class="form-group">
                <label for="<?php echo $prefix; ?>captcha"> <?php echo $captcha_question; ?> </label>
                <input type="text" class="form-control" id="<?php echo $prefix; ?>captcha" name="<?php echo $prefix; ?>captcha" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Note</button>
        </form>
        <hr>
        <?php $noteManager->displayNotes(); ?>
    </div>
</body>
</html>
