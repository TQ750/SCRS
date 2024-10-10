<?php
session_start();

include 'db_connection.php';

function generateRnoKey($length = 32) {
    return bin2hex(random_bytes($length / 2));}

function encryptEvidence($data, $rnoKey) {
    $iv = substr($rnoKey, 0, 16); 
    $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $rnoKey, 0, $iv);
    return base64_encode($encryptedData . '::' . $iv);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rno = generateRnoKey(); 
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

    // Handle evidence file uploads and encrypt them
    $evidenceFiles = array();
    if (isset($_FILES["evidence"]) && is_array($_FILES["evidence"]["name"])) {
        $uploadDir = "reports/";
        foreach ($_FILES["evidence"]["name"] as $key => $name) {
            $tmpName = $_FILES["evidence"]["tmp_name"][$key];
            $uploadPath = $uploadDir . $email . "_" . $dateOfCrime . "_" . $rno; // Path for storing the file
            
            if (move_uploaded_file($tmpName, $uploadPath)) {
                $fileData = file_get_contents($uploadPath);
                $encryptedEvidence = encryptEvidence($fileData, $rno);
                $evidenceFiles[] = $encryptedEvidence;
            }
        }
    }

    // Insert data into the report table using prepared statement
    $stmt = $conn->prepare("INSERT INTO report (rno, civilNumber, address, fullName, typeOfCase, evidence, email, phone, nationality, dateOfCrime, detailsOfCrime) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Convert array to comma-separated string for evidence files
    $evidenceFilesString = implode(",", $evidenceFiles);

    // Bind parameters with appropriate types ('s' for string, 'i' for integer, etc.)
    $stmt->bind_param('issssssssss', 
        $rno, // rno as the encryption key
        $civilNumber, 
        $address, 
        $fullName, 
        $typeOfCase, 
        $evidenceFilesString, // Encrypted evidence
        $email, 
        $phoneNumber, 
        $nationality, 
        $dateOfCrime, 
        $crimeDetails
    );

    // Execute the prepared statement
    $stmt->execute();

    // Redirect after form submission
    header("Location: report_details.php?rno=" . $rno);
    exit; // Ensure script execution stops after redirection
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

        // Validate phone number is exactly 8 digits
        if (!numberRegex.test(phoneNumber) || phoneNumber.length !== 8) {
            alert('Phone Number should contain exactly 8 digits.');
            return false;
        }

        // Validate date of crime (must not be in the future)
        const today = new Date().toISOString().split('T')[0];
        if (dateOfCrime > today) {
            alert('The date of the crime cannot be in the future.');
            return false;
        }

        return true; // If all validations pass, allow form submission
    }
</script>

</head>
<body>
    <header>
        <nav>
            <a href="user_home.php"><div class="logo">&#127968;</div></a>
            <a href="dashboard.php"> <div class="user-icon">&#128100;</div></a>
        </nav>
    </header>

    <main>
        <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="civil_number">Civil number</label>
                <input type="text" id="civil_number" name="civil_number" required>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" required>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="type_of_case">Type of case</label>
                <select id="type_of_case" name="type_of_case" required>
                    <option value="Social Media">Social Media</option>
                    <option value="Cyberbullying">Cyberbullying</option>
                    <option value="Online Fraud">Online Fraud</option>
                    <option value="Identity Theft">Identity Theft</option>
                    <option value="Others">Others</option>
                </select>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone number </label>
                <input type="tel" id="phone_number" name="phone_number" maxlength="8" required>
            </div>
            <div class="form-group">
                <label for="nationality">Nationality</label>
                <input type="text" id="nationality" name="nationality" required>
            </div>
            <div class="form-group">
                <label for="date_of_crime">Date of crime</label>
                <input type="date" id="date_of_crime" name="date_of_crime" required>
            </div>
            <div class="form-group">
                <label for="evidence">Upload Evidence</label>
                <input type="file" id="evidence" name="evidence[]" multiple accept="image/*,video/*,application/pdf" required>
            </div>
            <div class="form-group">
                <label for="crime_details">Write details of crime</label>
                <textarea id="crime_details" name="crime_details" required></textarea>
            </div>
            <div class="note">
                <p><strong>*NOTE*</strong></p>
                <ul>
                    <li>- The complaint must not take more than one year to be dealt with.</li>
                    <li>- Data must be entered correctly, such as the date, time, and details of the crime.</li>
                    <li>- Submitting a false complaint exposes you to legal liability.</li>
                </ul>
                <div class="consent">
                    <input type="checkbox" id="consent" name="consent" required>
                    <label for="consent">Do you agree to our privacy policy and consent to the storage of sensitive information in our database?</label>
                </div>
            </div>
            <button type="submit" name="submit">Submit</button>
        </form>
    </main>

    <footer>
        <!--  footer content  -->
    </footer>
</body>
</html>
