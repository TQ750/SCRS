<?php
session_start();
include 'db_connection.php';

// Check if the user is logged in as an admin (add your admin check logic here)

// Get the report ID from the URL
if (isset($_GET['id'])) {
    $report_id = intval($_GET['id']); // Sanitize the input

    // Fetch the report details
    $sql = "SELECT * FROM report WHERE id = $report_id";
    $result = $conn->query($sql);

    // Check if the report exists
    if ($result->num_rows > 0) {
        $report = $result->fetch_assoc();
    } else {
        die("Report not found.");
    }
} else {
    die("Invalid request.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Report - Cyber Crime Hub</title>
    <link rel="stylesheet" href="style.css"> <!-- CSS for styling -->
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f2f2f2; /* Light gray background */
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: white; /* White background for the content */
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #004080; /* Dark blue color for the heading */
        }

        .report-detail {
            margin: 20px 0;
        }

        .report-detail label {
            font-weight: bold;
            display: inline-block;
            margin-bottom: 5px;
        }

        .report-detail p {
            margin: 5px 0 20px 0;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #0066cc; /* Blue color for the back link */
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Report Details</h1>

        <div class="report-detail">
            <label for="reportId">Report ID:</label>
            <p id="reportId"><?php echo $report['id']; ?></p>
        </div>

        <div class="report-detail">
            <label for="reportType">Type of Case:</label>
            <p id="reportType"><?php echo $report['typeOfCase']; ?></p>
        </div>

        <div class="report-detail">
            <label for="reportDate">Date of Crime:</label>
            <p id="reportDate"><?php echo $report['dateOfCrime']; ?></p>
        </div>

        <div class="report-detail">
            <label for="reportDetails">Summary:</label>
            <p id="reportDetails"><?php echo nl2br(htmlspecialchars($report['detailsOfCrime'])); ?></p>
        </div>

        <a href="report_managment.php" class="back-link">Back to Report Management</a>
    </div>
</body>
</html>
