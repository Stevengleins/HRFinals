<?php
/**
 * Payroll Calculation API
 * RESTful endpoint for payroll calculations
 * Usage: POST /api/payroll_calculate.php
 */

header('Content-Type: application/json');
require '../database.php';
require '../includes/PayrollCalculator.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'errors' => []
];

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $requiredFields = ['gross_salary', 'pay_period'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            throw new Exception("Missing required field: $field");
        }
    }

    // Extract and validate input
    $grossSalary = (float)$input['gross_salary'];
    $payPeriod = $input['pay_period']; // 'monthly', 'semi-monthly', 'daily'
    $regularHours = $input['regular_hours'] ?? null;
    $overtimeHours = (float)($input['overtime_hours'] ?? 0);
    $undertimeHours = (float)($input['undertime_hours'] ?? 0);
    $lateMinutes = (int)($input['late_minutes'] ?? 0);

    if ($grossSalary < 0) {
        throw new Exception("Gross salary cannot be negative");
    }

    if (!in_array($payPeriod, ['monthly', 'semi-monthly', 'daily'])) {
        throw new Exception("Invalid pay period. Must be 'monthly', 'semi-monthly', or 'daily'");
    }

    // Initialize calculator
    $calculator = new PayrollCalculator($grossSalary, $payPeriod);

    // Calculate complete summary
    $summary = $calculator->calculateCompleteSummary(
        $regularHours,
        $overtimeHours,
        $undertimeHours,
        $lateMinutes
    );

    $response['success'] = true;
    $response['message'] = 'Payroll calculation completed successfully';
    $response['data'] = $summary;

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['errors'][] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
