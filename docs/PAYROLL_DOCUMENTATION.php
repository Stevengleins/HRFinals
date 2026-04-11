<?php
/**
 * Philippine HRMS Payroll Module - Documentation & Examples
 * 
 * This document provides examples of how to use the PayrollCalculator
 * and PayrollProcessor classes for payroll computations.
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Module Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .code-block { background: #f5f5f5; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .section { margin-top: 40px; }
        .example-title { color: #007bff; font-weight: bold; margin-top: 20px; }
        table { margin: 20px 0; }
        .formula { background: #f0f7ff; padding: 15px; border-radius: 4px; margin: 15px 0; }
    </style>
</head>
<body>
<div class="container-fluid py-5">

<h1 class="mb-4">Philippine HRMS Payroll Calculation Module</h1>

<div class="alert alert-info">
    <strong><i class="fas fa-info-circle"></i> Documentation Version:</strong> 1.0<br>
    <strong>Effective Date:</strong> 2025-2026 TRAIN Law Rates<br>
    <strong>Last Updated:</strong> April 10, 2026
</div>

<!-- ============ OVERVIEW ============ -->
<div class="section">
    <h2>1. Overview</h2>
    <p>The Payroll Calculation Module provides accurate computation of Philippine employee payroll including:</p>
    <ul>
        <li><strong>Mandatory Government Deductions:</strong> SSS, PhilHealth, Pag-IBIG</li>
        <li><strong>Withholding Tax:</strong> Based on TRAIN Law 2023-2026 rates</li>
        <li><strong>Time Adjustments:</strong> Overtime, undertime, and late deductions</li>
        <li><strong>Complete Payroll Summary:</strong> Net take-home pay calculation</li>
    </ul>
    <p>Two main classes are provided:</p>
    <ul>
        <li><strong>PayrollCalculator:</strong> Core calculation engine</li>
        <li><strong>PayrollProcessor:</strong> Database integration and employee payroll processing</li>
    </ul>
</div>

<!-- ============ REQUIREMENTS ============ -->
<div class="section">
    <h2>2. System Requirements</h2>
    <ul>
        <li>PHP 7.4 or higher</li>
        <li>MySQL/MariaDB with updated payroll table schema</li>
        <li>Attendance records with overtime, undertime, and late data</li>
    </ul>
    <p><strong>Database Migration:</strong> Run <code>migrate_payroll_database.php</code> once to update the payroll table structure.</p>
</div>

<!-- ============ DEDUCTION RATES ============ -->
<div class="section">
    <h2>3. Mandatory Deduction Rates (2025-2026)</h2>
    
    <h4 class="example-title">3.1 SSS (Social Security System)</h4>
    <div class="formula">
        <strong>Employee Contribution Rate:</strong> 4.5% of gross salary<br>
        <strong>Employer Contribution Rate:</strong> 9.5% of gross salary<br>
        <strong>Monthly Salary Credit (MSC) Ceiling:</strong> ₱1,350 (applies to employee share for salaries ≥ ₱30,000)
    </div>
    <p><strong>Example:</strong> For a salary of ₱35,000/month:</p>
    <ul>
        <li>Employee Share: min(35,000 × 4.5%, 1,350) = ₱1,350.00</li>
        <li>Employer Share: min(35,000 × 9.5%, 1,350) = ₱1,350.00</li>
    </ul>

    <h4 class="example-title">3.2 PhilHealth (Philippine Health Insurance)</h4>
    <div class="formula">
        <strong>Total Premium Rate:</strong> 5% of gross salary<br>
        <strong>Employee Share:</strong> 2.5% (half of 5%)<br>
        <strong>Employer Share:</strong> 2.5% (half of 5%)<br>
        <strong>Minimum Total Contribution:</strong> ₱500 (₱250 per share)<br>
        <strong>Maximum Total Contribution:</strong> ₱5,000 (₱2,500 per share)
    </div>
    <p><strong>Example:</strong> For a salary of ₱25,000/month:</p>
    <ul>
        <li>Total Contribution: 25,000 × 5% = ₱1,250</li>
        <li>Employee Share: ₱1,250 ÷ 2 = ₱625</li>
        <li>Employer Share: ₱1,250 ÷ 2 = ₱625</li>
    </ul>

    <h4 class="example-title">3.3 Pag-IBIG (Home Development Mutual Fund)</h4>
    <div class="formula">
        <strong>Employee Contribution:</strong> Fixed ₱200<br>
        <strong>Employer Contribution:</strong> Fixed ₱200<br>
        <strong>Eligibility:</strong> Only for salaries above ₱5,000
    </div>

    <h4 class="example-title">3.4 Withholding Tax (TRAIN Law 2023-2026)</h4>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Taxable Income Range</th>
                <th>Tax Computation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>₱0 - ₱20,833</td>
                <td>0% (Tax Exempt)</td>
            </tr>
            <tr>
                <td>₱20,834 - ₱33,332</td>
                <td>15% of amount exceeding ₱20,833</td>
            </tr>
            <tr>
                <td>₱33,333 - ₱66,666</td>
                <td>₱1,875 + 20% of amount exceeding ₱33,333</td>
            </tr>
            <tr>
                <td>₱66,667 - ₱166,666</td>
                <td>₱8,542 + 25% of amount exceeding ₱66,667</td>
            </tr>
            <tr>
                <td>₱166,667 - ₱666,666</td>
                <td>₱33,958 + 30% of amount exceeding ₱166,667</td>
            </tr>
            <tr>
                <td>Above ₱666,667</td>
                <td>₱183,958 + 35% of amount exceeding ₱666,667</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- ============ USAGE EXAMPLES ============ -->
<div class="section">
    <h2>4. Usage Examples</h2>

    <h4 class="example-title">4.1 Basic Payroll Calculation</h4>
    <div class="code-block">
        <pre><code>&lt;?php
require 'includes/PayrollCalculator.php';

// Initialize calculator with gross salary
$calculator = new PayrollCalculator(25000, 'semi-monthly');

// Calculate each component
$sss = $calculator->calculateSSS();
$philhealth = $calculator->calculatePhilHealth();
$pagibig = $calculator->calculatePagIBIG();

// Calculate taxable income
$taxableIncome = $calculator->calculateTaxableIncome($sss, $philhealth, $pagibig);

// Calculate withholding tax
$withholdingTax = $calculator->calculateWithholdingTax($taxableIncome);

// Display results
echo "SSS Employee Share: ₱" . number_format($sss['employee_share'], 2);
echo "PhilHealth Employee Share: ₱" . number_format($philhealth['employee_share'], 2);
echo "Withholding Tax: ₱" . number_format($withholdingTax, 2);
?&gt;</code></pre>
    </div>

    <h4 class="example-title">4.2 Complete Payroll Summary with Time Adjustments</h4>
    <div class="code-block">
        <pre><code>&lt;?php
require 'includes/PayrollCalculator.php';

$calculator = new PayrollCalculator(30000, 'monthly');

// Calculate complete summary with overtime, undertime, late
$summary = $calculator->calculateCompleteSummary(
    $regularHours = 176,      // Expected regular hours (22 days × 8 hrs)
    $overtimeHours = 8,       // Approved overtime hours
    $undertimeHours = 4,      // Undertime hours
    $lateMinutes = 60,        // Total late minutes in the period
    $employeeData = [
        'employee_id' => 3,
        'name' => 'John Doe',
        'position' => 'Developer'
    ]
);

// Display complete summary
echo "Gross Salary: ₱" . number_format($summary['gross_salary'], 2);
echo "Overtime Pay: ₱" . number_format($summary['overtime_pay'], 2);
echo "Total Deductions: ₱" . number_format($summary['total_deductions'], 2);
echo "NET TAKE-HOME PAY: ₱" . number_format($summary['net_take_home_pay'], 2);
?&gt;</code></pre>
    </div>

    <h4 class="example-title">4.3 Using PayrollProcessor with Database</h4>
    <div class="code-block">
        <pre><code>&lt;?php
require 'database.php';
require 'includes/PayrollProcessor.php';

$processor = new PayrollProcessor($mysql);

// Calculate employee payroll from database
$summary = $processor->calculateEmployeePayroll(
    $userId = 3,
    $grossSalary = 25000,
    $startDate = '2026-04-01',
    $endDate = '2026-04-15',
    $payPeriod = 'semi-monthly'
);

// Save to database
$payrollId = $processor->savePayrollRecord(
    $userId,
    '2026-04-01',
    '2026-04-15',
    $summary,
    $status = 'Pending',
    $processedBy = 2 // HR Staff user_id
);

echo "Payroll Processed! ID: #" . $payrollId;
echo "Net Pay: ₱" . number_format($summary['net_take_home_pay'], 2);
?&gt;</code></pre>
    </div>

    <h4 class="example-title">4.4 Using the Payroll Processing Page</h4>
    <ol>
        <li>Navigate to <code>HR_Staff/payroll_processing.php</code></li>
        <li>Select an employee from the dropdown</li>
        <li>Choose pay period (Monthly, Semi-Monthly, or Daily)</li>
        <li>Enter gross salary for the period</li>
        <li>Select start and end dates</li>
        <li>Click "Calculate & Process Payroll"</li>
        <li>Review the detailed payroll summary</li>
    </ol>
</div>

<!-- ============ COMPUTATION EXAMPLES ============ -->
<div class="section">
    <h2>5. Complete Payroll Computation Examples</h2>

    <h4 class="example-title">5.1 Example: Monthly Employee (₱30,000 Salary)</h4>
    <table class="table table-striped">
        <tr>
            <td><strong>Gross Salary</strong></td>
            <td class="text-right">₱30,000.00</td>
        </tr>
        <tr class="table-info">
            <td colspan="2"><strong>Mandatory Deductions:</strong></td>
        </tr>
        <tr>
            <td>SSS Employee (4.5% capped at ₱1,350)</td>
            <td class="text-right">₱1,350.00</td>
        </tr>
        <tr>
            <td>PhilHealth Employee (2.5%)</td>
            <td class="text-right">₱750.00</td>
        </tr>
        <tr>
            <td>Pag-IBIG Employee (Fixed)</td>
            <td class="text-right">₱200.00</td>
        </tr>
        <tr>
            <td><strong>Total Mandatory Deductions</strong></td>
            <td class="text-right"><strong>₱2,300.00</strong></td>
        </tr>
        <tr class="table-warning">
            <td><strong>Taxable Income</strong> (30,000 - 2,300)</td>
            <td class="text-right"><strong>₱27,700.00</strong></td>
        </tr>
        <tr>
            <td><strong>Withholding Tax:</strong> 15% of (27,700 - 20,833)</td>
            <td class="text-right"><strong>₱1,030.05</strong></td>
        </tr>
        <tr class="table-success">
            <td colspan="2"></td>
        </tr>
        <tr style="background-color: #e8f5e9; font-size: 1.1rem;">
            <td><strong>NET TAKE-HOME PAY</strong></td>
            <td class="text-right"><strong>₱25,669.95</strong></td>
        </tr>
    </table>

    <h4 class="example-title">5.2 Example: With Overtime and Late (₱25,000 Salary)</h4>
    <table class="table table-striped">
        <tr>
            <td><strong>Base Gross Salary</strong></td>
            <td class="text-right">₱25,000.00</td>
        </tr>
        <tr class="table-success">
            <td>Add: Overtime Pay (8 hours @ 1.25x rate)</td>
            <td class="text-right">+₱295.45</td>
        </tr>
        <tr class="table-danger">
            <td>Less: Undertime Deduction (2 hours @ regular rate)</td>
            <td class="text-right">-₱59.09</td>
        </tr>
        <tr class="table-danger">
            <td>Less: Late Deduction (60 minutes)</td>
            <td class="text-right">-₱118.18</td>
        </tr>
        <tr>
            <td><strong>Adjusted Gross</strong></td>
            <td class="text-right"><strong>₱25,118.18</strong></td>
        </tr>
        <tr class="table-info">
            <td colspan="2"><strong>Mandatory Deductions:</strong></td>
        </tr>
        <tr>
            <td>SSS Employee (4.5%)</td>
            <td class="text-right">₱1,125.00</td>
        </tr>
        <tr>
            <td>PhilHealth Employee (2.5%)</td>
            <td class="text-right">₱625.00</td>
        </tr>
        <tr>
            <td>Pag-IBIG Employee (Fixed)</td>
            <td class="text-right">₱200.00</td>
        </tr>
        <tr>
            <td><strong>Total Mandatory</strong></td>
            <td class="text-right"><strong>₱1,950.00</strong></td>
        </tr>
        <tr class="table-warning">
            <td><strong>Taxable Income</strong></td>
            <td class="text-right"><strong>₱23,050.00</strong></td>
        </tr>
        <tr>
            <td><strong>Withholding Tax:</strong> 15% of (23,050 - 20,833)</td>
            <td class="text-right"><strong>₱332.55</strong></td>
        </tr>
        <tr style="background-color: #e8f5e9; font-size: 1.1rem;">
            <td><strong>NET TAKE-HOME PAY</strong></td>
            <td class="text-right"><strong>₱20,767.45</strong></td>
        </tr>
    </table>
</div>

<!-- ============ API USAGE ============ -->
<div class="section">
    <h2>6. API Endpoint Usage</h2>
    <p>The payroll calculation API is available at <code>POST /api/payroll_calculate.php</code></p>

    <h4 class="example-title">6.1 API Request</h4>
    <div class="code-block">
        <pre><code>POST /api/payroll_calculate.php HTTP/1.1
Host: localhost
Content-Type: application/json

{
    "gross_salary": 30000,
    "pay_period": "monthly",
    "regular_hours": 176,
    "overtime_hours": 8,
    "undertime_hours": 0,
    "late_minutes": 30
}</code></pre>
    </div>

    <h4 class="example-title">6.2 API Response</h4>
    <div class="code-block">
        <pre><code>{
    "success": true,
    "message": "Payroll calculation completed successfully",
    "data": {
        "gross_salary": 30000,
        "overtime_pay": 307.27,
        "undertime_deduction": 0,
        "late_deduction": 14.77,
        "adjusted_gross_salary": 30292.5,
        "sss": {
            "employee_share": 1350,
            "employer_share": 1350,
            "total": 2700
        },
        "philhealth": {
            "employee_share": 750,
            "employer_share": 750,
            "total": 1500
        },
        "pagibig": {
            "employee_share": 200,
            "employer_share": 200,
            "total": 400
        },
        "total_mandatory_deductions": 2300,
        "taxable_income": 27992.5,
        "withholding_tax": 1073.88,
        "total_deductions": 3373.88,
        "net_take_home_pay": 26918.62,
        "employer_contributions": 2300
    }
}</code></pre>
    </div>

    <h4 class="example-title">6.3 Using JavaScript/jQuery</h4>
    <div class="code-block">
        <pre><code>$.ajax({
    url: '/api/payroll_calculate.php',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
        gross_salary: 25000,
        pay_period: 'semi-monthly',
        overtime_hours: 5,
        undertime_hours: 2,
        late_minutes: 45
    }),
    success: function(response) {
        if (response.success) {
            console.log('Net Pay:', response.data.net_take_home_pay);
        }
    }
});</code></pre>
    </div>
</div>

<!-- ============ TROUBLESHOOTING ============ -->
<div class="section">
    <h2>7. Troubleshooting & FAQs</h2>

    <h4>Q: Why is my SSS contribution capped?</h4>
    <p>A: The SSS Monthly Salary Credit (MSC) ceiling of ₱1,350 applies to employees with gross salaries of ₱30,000 or more. This is per SSS regulations.</p>

    <h4>Q: How is overtime pay calculated?</h4>
    <p>A: Overtime is paid at 1.25x the regular hourly rate. E.g., if hourly rate is ₱295.45, 1 hour overtime = ₱295.45 × 1.25 = ₱369.31</p>

    <h4>Q: Are tax exemptions applied?</h4>
    <p>A: No. The system calculates withholding tax based on the TRAIN Law. Employees earning ₱20,833 or below monthly are tax-exempt, but the system still calculates for records.</p>

    <h4>Q: Can I include de minimis benefits?</h4>
    <p>A: Currently not included in the calculation. To add them, exclude from grossSalary before passing to calculator.</p>

    <h4>Q: How are absent days handled?</h4>
    <p>A: Absent days are not automatically deducted. They should be handled during gross salary calculation before passing to the calculator.</p>
</div>

<!-- ============ DATABASE SCHEMA ============ -->
<div class="section">
    <h2>8. Updated Database Schema</h2>
    <p>The payroll table has been updated with the following new columns:</p>
    <div class="code-block">
        <pre><code>ALTER TABLE payroll ADD COLUMN sss_employee_share DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN sss_employer_share DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN philhealth_employee_share DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN philhealth_employer_share DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN pagibig_employee_share DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN pagibig_employer_share DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN overtime_hours DECIMAL(5,2)
ALTER TABLE payroll ADD COLUMN overtime_pay DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN undertime_hours DECIMAL(5,2)
ALTER TABLE payroll ADD COLUMN undertime_deduction DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN late_minutes INT
ALTER TABLE payroll ADD COLUMN late_deduction DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN total_mandatory_deductions DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN taxable_income DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN withholding_tax DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN adjusted_gross_salary DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN total_employer_contributions DECIMAL(10,2)
ALTER TABLE payroll ADD COLUMN payment_method VARCHAR(50)
ALTER TABLE payroll ADD COLUMN remarks TEXT
ALTER TABLE payroll ADD COLUMN processed_by INT
ALTER TABLE payroll ADD COLUMN processed_date DATETIME</code></pre>
    </div>
</div>

<!-- ============ SUPPORT ============ -->
<div class="section">
    <h2>9. Support & Contact</h2>
    <p>For issues or feature requests related to the payroll module, please:</p>
    <ol>
        <li>Check this documentation</li>
        <li>Review the PayrollCalculator.php and PayrollProcessor.php class definitions</li>
        <li>Contact your system administrator</li>
    </ol>
</div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>hljs.highlightAll();</script>

</body>
</html>
