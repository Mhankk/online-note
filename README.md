# Online Notes Application

This is a versatile online note-taking application written in PHP that enables users to create, store, and view text-based notes. Over multiple versions, the application has evolved to include a range of features designed to improve usability, organization, and basic security. Users can mark notes as either Public or Private. Public notes are stored in a "txt" directory and listed on the main page for quick access, while Private notes are stored securely in a "pvt" directory with a randomized filename suffix and can only be accessed via a direct link.

**Warning:** This application is a demonstration and should **NOT** be used for storing sensitive information in a production environment. Security measures are basic and plain text storage is used.

## Features on Version 4

* **Basic Note Creation and Storage:** Allows users to create and save notes as text files.
* **Public and Private Notes:** Users can choose to make a note public or private.
* **Private Note Security:** Private notes are stored in a separate directory and are assigned a randomized filename suffix for added obscurity.
* **Input Sanitization:** Prevents XSS vulnerabilities by properly sanitizing user input.
* **CSRF Protection:** Guards against cross-site request forgery attacks.
* **Simple CAPTCHA:** Uses a basic arithmetic CAPTCHA to reduce bot submissions.
* **Public Note Listing:** Displays up to the 5 most recent public notes on the main page.
* **User-Friendly Messages:** Provides clear success and error messages to guide the user.
* **Object-Oriented Programming (OOP):** Organized using OOP principles for better code structure and maintainability.
* **Theme Toggle:** Offers a choice between light and dark mode themes.
* **AJAX Support:** Utilizes AJAX for seamless note submission without full page reloads.
* **Responsive Folder Checking:** Automatically creates necessary directories ("txt" and "pvt") if they do not exist.

## Features on Version 3

* **Enhanced OOP Structure:** Transition to an object-oriented design to better organize code.
* **Responsive Folder Existence Check:** Verifies and creates note storage folders as needed.
* **AJAX Form Submission:** Introduces AJAX to allow dynamic note creation without reloading the page.
* **Theme Toggle Implementation:** Adds the ability to switch between dark and light modes.
* **Basic Note Management:** Includes features such as note creation, input sanitization, CSRF protection, and CAPTCHA verification.

## Features on Version 2

* **Initial Dark/Light Mode Toggle:** Allows users to switch between dark mode (using Bootswatch’s Darkly theme) and light mode (default Bootstrap).
* **AJAX-Enabled Note Submission:** Implements AJAX to handle note creation, providing a smoother user experience.
* **Responsive Design:** Uses Bootstrap for a responsive and user-friendly layout.
* **Basic Security Measures:** Introduces input sanitization, CSRF protection, and a simple CAPTCHA system.

## Features on Version 1

* **Basic Online Note-Taking:** The initial version supports creating and storing text-based notes.
* **Fundamental Security Practices:** Implements basic input sanitization, CSRF protection, and a CAPTCHA to mitigate common vulnerabilities.
* **Simple Note Listing:** Displays a limited number of existing notes for easy access.

## Limitations

* **Basic Security:** Security measures are minimal and may not be sufficient for all threats. This is a demonstration script.
* **No User Authentication:** There is no advanced user authentication or access control.
* **Plain Text Storage:** Notes are stored as plain text files, which is not ideal for sensitive data. Consider encryption or database storage for production use.
* **Simple CAPTCHA:** The CAPTCHA system is basic and could be bypassed by sophisticated bots.
* **Obscurity, Not Encryption:** Private notes rely on obscurity (randomized filenames) rather than robust encryption for security. If a private note's URL is discovered, its contents can be accessed.

## Requirements

* PHP (version 7.0 or higher recommended)
* A web server (e.g., Apache, Nginx)
* `allow_url_fopen` should be disabled

## Installation

1. **Clone the repository (if applicable) or download the PHP file.**
2. **Place the PHP file in your web server's document root directory.**
3. **Ensure the web server has write permissions to the directory where the PHP file is located.**  
   The script automatically creates the `txt` and `pvt` directories if they do not exist, but proper permissions are required.
4. **Access the application through your web browser** (e.g., `http://localhost/your-script-name.php`).

## Usage

1. **Enter a filename for your note.**
2. **Write your note's content in the text area.**
3. **Check the "Set Private Note" checkbox** if you want the note to be private.
4. **Answer the CAPTCHA question.**
5. **Click the "Save Note" button.**

* **Public Notes:** Public notes are saved in the `txt` directory and are listed on the main page.
* **Private Notes:** Private notes are saved in the `pvt` directory with a randomized filename suffix. A direct link to the note is provided upon successful saving. **Keep this link safe, as it is the only way to access the private note.** (Only Version 4)

## File Structure

* `index.php` (or the name you gave the PHP file): The main application file.
* `txt/`: Directory for storing public notes.
* `pvt/`: Directory for storing private notes. (Only Version 4)

## Security Considerations

* **Input Sanitization:**  
  The application uses functions like `htmlspecialchars` to escape HTML entities in user input, mitigating XSS attacks.
* **CSRF Protection:**  
  A CSRF token is generated and validated on every form submission to prevent unauthorized actions.
* **CAPTCHA:**  
  A simple arithmetic CAPTCHA is employed to deter automated bot submissions.
* **Directory Permissions:**  
  Ensure that proper permissions are set on the note storage directories to prevent unauthorized access.

**Disclaimer:** This application is a basic demonstration and does not incorporate advanced security features. For production use, consider employing a database, encryption, robust authentication, and comprehensive security measures.
