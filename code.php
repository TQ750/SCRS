<?php
// Start the session
session_start();

// Include database connection file
include 'db_connection.php';

// Function to generate a random 32-character key
function generateRandomKey($rno) {
    return substr(hash('sha256', $rno), 0, 32); // Use SHA-256 hash for a consistent key length
}

// Function to encrypt the PDF file
function encryptFile($filePath, $key) {
    $ivlen = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext = openssl_encrypt(file_get_contents($filePath), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    
    // Return the IV and ciphertext combined
    return base64_encode($iv . $ciphertext);
}

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
            $uploadPath = $uploadDir . $email . "_" . $dateOfCrime . "_" . $rno . "_" . basename($name);
            if (move_uploaded_file($tmpName, $uploadPath)) {
                $evidenceFiles[] = $uploadPath;
            }
        }
    }

    // Insert data into the report table using prepared statement
    $stmt = $conn->prepare("INSERT INTO report (rno, civilNumber, address, fullName, typeOfCase, evidence, email, phone, nationality, dateOfCrime, detailsOfCrime) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Convert array to comma-separated string
    $evidenceString = implode(',', $evidenceFiles);
    
    // Bind parameters with appropriate types ('s' for string, 'i' for integer, etc.)
    $stmt->bind_param('issssssssss', 
        $rno, 
        $civilNumber, 
        $address, 
        $fullName, 
        $typeOfCase, 
        $evidenceString, 
        $email, 
        $phoneNumber, 
        $nationality, 
        $dateOfCrime, 
        $crimeDetails
    );

    // Execute the prepared statement
    if ($stmt->execute()) {
        // Create PDF
        $pdfFilePath = "reports/" . $civilNumber . ".pdf";
        $pdfContent = "Civil Number: $civilNumber\nAddress: $address\nFull Name: $fullName\nType of Case: $typeOfCase\nEmail: $email\nPhone: $phoneNumber\nNationality: $nationality\nDate of Crime: $dateOfCrime\nCrime Details: $crimeDetails\nEvidence: $evidenceString";
        file_put_contents($pdfFilePath, $pdfContent);
        
        // Generate key and encrypt the PDF
        $key = generateRandomKey($rno);
        $encryptedData = encryptFile($pdfFilePath, $key);
        
        // Save to info table
        $infoStmt = $conn->prepare("INSERT INTO info (rno, data) VALUES (?, ?)");
        $infoStmt->bind_param('ss', $rno, $encryptedData);
        $infoStmt->execute();
        
        // Redirect after form submission
        header("Location: report_details.php?rno=" . $rno);
        exit; // Ensure script execution stops after redirection
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
