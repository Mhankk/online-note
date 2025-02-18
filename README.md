# Online Notes Application

This is a simple online note-taking application written in PHP. It allows users to create, store, and view text-based notes. This application includes basic security features such as input sanitization, CSRF protection, and a CAPTCHA system.  Users can mark notes as Public or Private.

**Warning: This application is a demonstration and should NOT be used for storing sensitive information in a production environment.  Security measures are basic and plain text storage is used.**

## Features

*   **Basic Note Creation and Storage:** Allows users to create and save notes as text files.
*   **Public and Private Notes:**  Users can choose to make a note public or private.
*   **Private Note Security:** Private notes are stored in a separate directory and are given a randomized filename suffix for added obscurity.
*   **Input Sanitization:** Prevents XSS vulnerabilities by sanitizing user input.
*   **CSRF Protection:** Protects against cross-site request forgery attacks.
*   **Simple CAPTCHA:** Reduces bot submissions using a basic arithmetic CAPTCHA.
*   **Public Note Listing:** Lists the 5 most recently added public notes for easy access.
*   **User-Friendly Messages:** Provides success and error messages to guide the user.
*   **Object-Oriented Programming (OOP):** Organized using OOP principles for better code structure and maintainability.

## Limitations

*   **Basic Security:** Security measures are basic and may not be sufficient for all threats. *This is a demonstration script.*
*   **No User Authentication:** Lacks user authentication or advanced access control.
*   **Plain Text Storage:** Stores notes as plain text files, which is not ideal for sensitive data.  Consider encryption or a database for production.
*   **Simple CAPTCHA:**  The CAPTCHA is simple and could be bypassed by sophisticated bots.
*   **Obscurity, Not Security:** Private notes rely on obscurity rather than encryption for security. If a private note's URL is discovered, the note's contents are exposed.

## Requirements

*   PHP (version 7.0 or higher recommended)
*   A web server (e.g., Apache, Nginx)
*   `allow_url_fopen` should be disabled

## Installation

1.  **Clone the repository (if applicable) or download the PHP file.**
2.  **Place the PHP file in your web server's document root directory.**
3.  **Ensure the web server has write permissions to the directory where the PHP file is located.** The script automatically creates `txt` and `pvt` directories if they don't exist, but proper permissions are crucial for writing note files.
4.  **Access the application through your web browser (e.g., `http://localhost/your-script-name.php`).**

## Usage

1.  **Enter a filename for your note.**
2.  **Write the content of your note in the text area.**
3.  **Check the "Set Private Note" checkbox if you want the note to be private.**
4.  **Answer the CAPTCHA question.**
5.  **Click the "Save Note" button.**

*   **Public Notes:**  Public notes are saved in the `txt` directory and are listed on the main page.
*   **Private Notes:** Private notes are saved in the `pvt` directory with a randomly generated filename suffix.  A direct link to the note is provided upon successful saving.  **Keep this link safe, as it is the only way to access the private note.**

## File Structure

*   `index.php` (or the name you gave the PHP file): The main application file.
*   `txt/`:  Directory for storing public notes.
*   `pvt/`: Directory for storing private notes.

## Security Considerations

*   **Input Sanitization:**  The application uses `htmlspecialchars` to prevent XSS attacks by escaping HTML entities in user input.
*   **CSRF Protection:** A CSRF token is generated and validated on each form submission to prevent cross-site request forgery attacks.
*   **CAPTCHA:** A simple CAPTCHA is used to prevent bot submissions.
*   **Directory Permissions:** Ensure proper directory permissions are set to prevent unauthorized access to the note files.

**Disclaimer:** This application is a basic demonstration and does not implement advanced security features. Do not use it for storing sensitive information in a production environment. Consider using a database, encryption, and more robust security measures for a production-ready application.
