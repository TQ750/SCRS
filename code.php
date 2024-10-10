<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session and include the database connection file
session_start();
include 'db_connection.php';

// Function to generate a random 32-character key
function generateRandomKey($rno) {
    return bin2hex(random_bytes(16)) . substr($rno, 0, 16);
}

// Function to decrypt the file
function decryptFile($encryptedData, $key) {
    $data = base64_decode($encryptedData);
    $ivlen = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivlen);
    $ciphertext = substr($data, $ivlen);
    return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

// Check if 'rno' is set in the URL
if (isset($_GET['rno'])) {
    $rno = $_GET['rno'];

    // Fetch the encrypted data from the info table
    $stmt = $conn->prepare("SELECT data FROM info WHERE rno = ?");
    $stmt->bind_param('s', $rno);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $encryptedData = $row['data'];
        
        // Regenerate the key used for decryption
        $key = generateRandomKey($rno); // Assuming the same key generation logic

        // Decrypt the data
        $pdfContent = decryptFile($encryptedData, $key);
        
        // Save decrypted content to a PDF file
        $downloadPath = "downloads/" . $rno . ".pdf";
        file_put_contents($downloadPath, $pdfContent);

        // Force download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($downloadPath) . '"');
        readfile($downloadPath);

        // Redirect to report management page
        header("Location: report_management.php");
        exit;
    } else {
        echo "No data found for the specified rno.";
    }
} else {
    echo "No rno specified.";
}
?>
