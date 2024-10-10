<?php
// Start the session at the beginning of the file
session_start();

// Include database connection file
include 'db_connection.php';

// Function to generate a random 32-character key
function generateRandomKey($rno) {
    return bin2hex(random_bytes(16)) . substr($rno, 0, 16);
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Crime Reporting</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Reset styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* General styles */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: url('Background.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        header {
            background-color: #f2f1ec;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            cursor: pointer;
        }

        .user-icon {
            font-size: 24px;
            cursor: pointer;
        }

        main {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f2f1ec;
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        form {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        input,
        textarea,
        select {
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #fff;
            color: #555;
        }

        .file-input {
            position: relative;
        }

        .file-input input[type=file] {
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .file-icon {
            display: inline-block;
            padding: 10px;
            background-color: #005bb5;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }

        .note {
            grid-column: 1 / -1;
            margin-top: 20px;
        }

        .note ul {
            margin-left: 20px;
        }

        .consent {
            margin-top: 10px;
            display: flex;
            align-items: center;
        }

        .consent input[type=checkbox] {
            margin-right: 10px;
        }

        button[type=submit] {
            grid-column: 1 / -1;
            margin-top: 20px;
            padding: 10px;
            background-color: #005bb5;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button[type=submit]:hover {
            background-color: #555;
        }
    </style>

    <!-- Add JavaScript for validation -->
<script>
    function validateForm() {
        const nameRegex = /^[A-Za-z\s]+$/;
        const numberRegex = /^[0-9]+$/;

        // Get form fields
        const fullName = document.getElementById('full_name').value;
        const address = document.getElementById('address').value;
        const nationality = document.getElementById('nationality').value;
        const civilNumber = document.getElementById('civil_number').value;
        const phoneNumber = document.getElementById('phone_number').value;
        const dateOfCrime = document.getElementById('date_of_crime').value;

        // Validate full name, address, and nationality (letters only)
        if (!nameRegex.test(fullName)) {
            alert('Full Name should contain only letters.');
            return false;
        }
        if (!nameRegex.test(address)) {
            alert('Address should contain only letters.');
            return false;
        }
        if (!nameRegex.test(nationality)) {
            alert('Nationality should contain only letters.');
            return false;
        }

        // Validate civil number and phone number (numbers only)
        if (!numberRegex.test(civilNumber)) {
            alert('Civil Number should contain only numbers.');
            return false;
        }
        if (!numberRegex.test(phoneNumber)) {
            alert('Phone Number should contain only numbers.');
            return false;
        }

        // Validate date of crime
        if (new Date(dateOfCrime) > new Date()) {
            alert('Date of Crime cannot be in the future.');
            return false;
        }

        return true; // All validations passed
    }
</script>

</head>
<body>
    <header>
        <div class="logo">Digital Crime Reporting</div>
        <div class="user-icon" onclick="location.href='user_profile.php';">👤</div>
    </header>
    <div class="container">
        <h2>Submit a Crime Report</h2>
        <form onsubmit="return validateForm()" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="civil_number">Civil Number:</label>
                <input type="text" id="civil_number" name="civil_number" required>
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" required>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="type_of_case">Type of Case:</label>
                <select id="type_of_case" name="type_of_case" required>
                    <option value="">Select</option>
                    <option value="Theft">Theft</option>
                    <option value="Assault">Assault</option>
                    <option value="Fraud">Fraud</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number:</label>
                <input type="text" id="phone_number" name="phone_number" required>
            </div>
            <div class="form-group">
                <label for="nationality">Nationality:</label>
                <input type="text" id="nationality" name="nationality" required>
            </div>
            <div class="form-group">
                <label for="date_of_crime">Date of Crime:</label>
                <input type="date" id="date_of_crime" name="date_of_crime" required>
            </div>
            <div class="form-group">
                <label for="crime_details">Details of Crime:</label>
                <textarea id="crime_details" name="crime_details" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="evidence">Evidence:</label>
                <div class="file-input">
                    <span class="file-icon">Upload Evidence</span>
                    <input type="file" id="evidence" name="evidence[]" multiple required>
                </div>
            </div>
            <div class="note">
                <p>Note: Please ensure all information is correct before submission.</p>
            </div>
            <div class="consent">
                <input type="checkbox" id="consent" name="consent" required>
                <label for="consent">I consent to the terms and conditions.</label>
            </div>
            <button type="submit">Submit Report</button>
        </form>
    </div>
</body>
</html>
