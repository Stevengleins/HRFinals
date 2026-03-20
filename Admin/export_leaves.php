<?php
session_start();
require '../database.php';

// Security check: Only Admins can download this master file
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Generate a dynamic filename with today's date
$filename = "Leave_Records_Masterlist_" . date('Y-m-d') . ".xls";

// Set the headers to force a file download instead of displaying a webpage
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch ALL leave requests
$query = "
    SELECT lr.*, u.first_name, u.last_name 
    FROM leave_requests lr
    JOIN user u ON lr.user_id = u.user_id
    ORDER BY lr.date_applied DESC
";
$result = $mysql->query($query);
?>

<table border="1">
    <thead>
        <tr>
            <th style="background-color: #343a40; color: #ffffff; font-weight: bold;">Leave ID</th>
            <th style="background-color: #343a40; color: #ffffff; font-weight: bold;">Employee Name</th>
            <th style="background-color: #343a40; color: #ffffff; font-weight: bold;">Leave Type</th>
            <th style="background-color: #343a40; color: #ffffff; font-weight: bold;">Start Date</th>
            <th style="background-color: #343a40; color: #ffffff; font-weight: bold;">End Date</th>
            <th style="background-color: #343a40; color: #ffffff; font-weight: bold;">Reason</th>
            <th style="background-color: #343a40; color: #ffffff; font-weight: bold;">Status</th>
            <th style="background-color: #343a40; color: #ffffff; font-weight: bold;">Date Applied</th>
            <th style="background-color: #343a40; color: #ffffff; font-weight: bold;">HR Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['leave_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['date_applied'])); ?></td>
                    <td><?php echo htmlspecialchars($row['remarks'] ?? 'N/A'); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9">No leave records found in the database.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>