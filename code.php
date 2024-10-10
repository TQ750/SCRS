<?php
// Start the session
session_start();

// Include database connection file
include 'db_connection.php';

// Function to decrypt the file
function decryptFile($encryptedData, $key) {
    $data = base64_decode($encryptedData);
    $ivlen = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivlen);
    $ciphertext = substr($data, $ivlen);
    
    return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

// Check if rno is set in the URL
if (isset($_GET['rno'])) {
    $rno = $_GET['rno'];

    // Retrieve encrypted data from the info table
    $stmt = $conn->prepare("SELECT data FROM info WHERE rno = ?");
    $stmt->bind_param('s', $rno);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $encryptedData = $row['data'];

        // Generate the key based on rno (this should match your encryption method)
        $key = generateRandomKey($rno); // Ensure the key generation logic is the same

        // Decrypt the file content
        $decryptedContent = decryptFile($encryptedData, $key);

        // Save decrypted PDF to downloads folder
        $downloadDir = 'downloads/';
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0777, true); // Create downloads folder if it doesn't exist
        }

        $pdfFilePath = $downloadDir . $rno . '_report.pdf';
        file_put_contents($pdfFilePath, $decryptedContent);

        // Force download the PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($pdfFilePath) . '"');
        header('Content-Length: ' . filesize($pdfFilePath));
        readfile($pdfFilePath);

        // Optionally redirect to report_management.php after download
        // header("Location: report_management.php");
        // exit; // Ensure the script stops after redirection
    } else {
        echo "No report found for the provided report number.";
    }
} else {
    echo "Report number (rno) is not specified.";
}

// Function to generate a random 32-character key
function generateRandomKey($rno) {
    return bin2hex(random_bytes(16)) . substr($rno, 0, 16);
}
?>
