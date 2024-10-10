<!DOCTYPE html>
<html lang="en">
<head>
<script>
        setTimeout(function(){
            window.location.href = "admin_login.php";
        }, 3000000);
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Management</title>
    <link rel="stylesheet" href="style.css"> <!-- CSS for styling -->
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: white; /* Set background color to white */
        }

        .sidebar {
            width: 250px;
            background-color: rgba(0, 0, 102, 0.9); /* Dark blue sidebar */
            position: fixed;
            height: 100%;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            color: white; /* Sidebar title text color */
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 10px 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #ffffff; /* Sidebar link color */
            font-weight: bold;
        }

        .main-content {
            margin-left: 270px;
            padding: 40px 20px; /* Added padding to provide space around the content */
        }

        h1 {
            text-align: center; /* Center the Report Management heading */
            font-size: 32px;
            color: #004080; /* Dark blue color for the heading */
        }

        table {
            width: 80%; /* Make table slightly smaller */
            margin: 20px auto; /* Center the table */
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #004080; /* Dark blue border for cells */
            padding: 15px;
            text-align: left;
        }

        th {
            background-color: #004080; /* Dark blue header background */
            color: white; /* White text for table headers */
        }

        td {
            background-color: #e6f0ff; /* Light blue background for table rows */
        }

        .main-content a {
            margin: 0 5px;
            text-decoration: none;
            color: #004080; /* Dark blue for links */
            font-weight: bold;
        }

        .main-content a:hover {
            text-decoration: underline;
        }

        /* Style for messages */
        .message {
            text-align: center;
            color: green;
            margin-bottom: 20px;
        }

        .error {
            color: red;
        }

        .status-select {
            width: 100px; /* Set width for select */
        }
    </style>
</head>
    <div class="sidebar">
        <h2>Admin Dashboard</h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="user_management.php">User Management</a></li>
            <li><a href="report_management.php">Report Management</a></li>
            <li><a href="testimonial_management.php">Testimonial Management</a></li>
            <li><a href="admin_change_pass.php">Change Password</a></li>
            <li><a href="admin_logout.php">Logout</a></li>
        </ul>
    </div>
<?php  session_start();
                include 'db_connection.php'; ?>
    <div class="main-content">
        <h1>Report Management</h1>

        <?php if (isset($_GET['message'])): ?>
            <div class="message"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="message error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>RNO</th>
                    <th>Full Name</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Summary</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                
                
                // Fetch reports
                $reports_result = $conn->query("SELECT * FROM report");
                
                if ($reports_result->num_rows > 0):
                    while ($report = $reports_result->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo $report['id']; ?></td>
                            <td><?php echo $report['rno']; ?></td>
                            <td><?php echo $report['fullName']; ?></td>
                            <td><?php echo $report['typeOfCase']; ?></td>
                            <td><?php echo $report['dateOfCrime']; ?></td>
                            <td><?php echo $report['detailsOfCrime']; ?></td>
                            <td>
                                <form action="update_status.php" method="POST">
                                    <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="Active" <?php if ($report['status'] == 'Active') echo 'selected'; ?>>Active</option>
                                        <option value="In Progress" <?php if ($report['status'] == 'In Progress') echo 'selected'; ?>>In Progress</option>
                                        <option value="Resolved" <?php if ($report['status'] == 'Resolved') echo 'selected'; ?>>Resolved</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <a href="view_report.php?rno=<?php echo $report['rno']; ?>">View</a>
                                <a href="delete_report.php?id=<?php echo $report['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this report?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No reports found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
