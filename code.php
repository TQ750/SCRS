<?php
// Start the session at the beginning of the file
session_start();

// Include database connection file and PhpWord for Word generation
include 'db_connection.php';
require_once 'vendor/autoload.php'; // Assuming PhpWord is installed via Composer

use PhpOffice\PhpWord\PhpWord;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rno = random_int(0, 550000);
    $civilNumber = $_POST["civil_number"];
    $address = $_POST["address"];
    $fullName = $_POST["full_name"];
    $typeOfCase = $_POST["type_of_case"];
    $email = $_POST["email"];
    $phoneNumber = $_POST["phone_number"];
    $nationality = $_POST["nationality"];
    $dateOfCrime = $_POST["date_of_crime"];
    $crimeDetails = $_POST["crime_details"];
    $consent = isset($_POST["consent"]) ? true : false;

    // Handle evidence file uploads
    $evidenceFiles = array();
    if (isset($_FILES["evidence"]) && is_array($_FILES["evidence"]["name"])) {
        $uploadDir = "reports/";
        foreach ($_FILES["evidence"]["name"] as $key => $name) {
            $tmpName = $_FILES["evidence"]["tmp_name"][$key];
            $uploadPath = $uploadDir . $email . "_" . $dateOfCrime . "_" . $rno;
            if (move_uploaded_file($tmpName, $uploadPath)) {
                $evidenceFiles[] = $uploadPath;
            }
        }
    }

    // Insert data into the report table using prepared statement
    $stmt = $conn->prepare("INSERT INTO report (rno, civilNumber, address, fullName, typeOfCase, evidence, email, phone, nationality, dateOfCrime, detailsOfCrime) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Convert array to comma-separated string for evidence files
    $evidenceFilesString = implode(",", $evidenceFiles);

    // Bind parameters and execute
    $stmt->bind_param('issssssssss', 
        $rno, 
        $civilNumber, 
        $address, 
        $fullName, 
        $typeOfCase, 
        $evidenceFilesString, 
        $email, 
        $phoneNumber, 
        $nationality, 
        $dateOfCrime, 
        $crimeDetails
    );

    // Execute the prepared statement
    $stmt->execute();

    // Generate a Word document and save it
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    $section->addText("Civil Number: " . $civilNumber);
    $section->addText("Full Name: " . $fullName);
    $section->addText("Address: " . $address);
    $section->addText("Type of Case: " . $typeOfCase);
    $section->addText("Crime Details: " . $crimeDetails);

    $fileName = 'reports/' . $civilNumber . '.docx';
    $phpWord->save($fileName, 'Word2007');

    // Generate a random 32-character key using the rno value
    $key = substr(hash('sha256', $rno), 0, 32);

    // Encrypt the content of the Word document using AES-256-CBC
    $data = file_get_contents($fileName);
    $iv = substr($key, 0, 16); // Use the first 16 characters of the key as IV
    $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

    // Save the encrypted data and key in the info table
    $stmtInfo = $conn->prepare("INSERT INTO info (rno, data) VALUES (?, ?)");
    $stmtInfo->bind_param('ss', $rno, $encryptedData);
    $stmtInfo->execute();

    // Clean up
    unlink($fileName); // Delete the original docx file after encryption

    // Redirect after form submission
    header("Location: report_details.php?rno=" . $rno);
    exit; // Ensure script execution stops after redirection
}
?>
