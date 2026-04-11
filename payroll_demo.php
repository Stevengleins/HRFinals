<?php
/**
 * Payroll Calculator - Demo & Testing File
 * This file demonstrates how to use the PayrollCalculator class
 * Access: http://localhost/HRFinals-main/payroll_demo.php
 */

require 'includes/PayrollCalculator.php';
require 'database.php';

$results = [];
$selectedTest = $_GET['test'] ?? 'all';

// Test Cases
$testCases = [
    'basic_25k' => [
        'name' => 'Basic Calculation - ₱25,000 Monthly',
        'gross_salary' => 25000,
        'pay_period' => 'monthly',
        'regular_hours' => 176,
        'overtime_hours' => 0,
        'undertime_hours' => 0,
        'late_minutes' => 0
    ],
    'basic_30k' => [
        'name' => 'Basic Calculation - ₱30,000 Monthly (MSC Ceiling)',
        'gross_salary' => 30000,
        'pay_period' => 'monthly',
        'regular_hours' => 176,
        'overtime_hours' => 0,
        'undertime_hours' => 0,
        'late_minutes' => 0
    ],
    'with_ot' => [
        'name' => 'With Overtime - ₱25,000 + 8 hrs OT',
        'gross_salary' => 25000,
        'pay_period' => 'monthly',
        'regular_hours' => 176,
        'overtime_hours' => 8,
        'undertime_hours' => 0,
        'late_minutes' => 0
    ],
    'with_adjustments' => [
        'name' => 'With All Adjustments - ₱30,000 + OT + UT + Late',
        'gross_salary' => 30000,
        'pay_period' => 'monthly',
        'regular_hours' => 176,
        'overtime_hours' => 10,
        'undertime_hours' => 4,
        'late_minutes' => 90
    ],
    'semi_monthly' => [
        'name' => 'Semi-Monthly Pay Period - ₱12,500',
        'gross_salary' => 12500,
        'pay_period' => 'semi-monthly',
        'regular_hours' => 88,
        'overtime_hours' => 4,
        'undertime_hours' => 2,
        'late_minutes' => 30
    ],
    'low_salary' => [
        'name' => 'Low Salary - ₱8,000 (No Pag-IBIG)',
        'gross_salary' => 8000,
        'pay_period' => 'daily',
        'regular_hours' => 8,
        'overtime_hours' => 2,
        'undertime_hours' => 0,
        'late_minutes' => 0
    ],
    'high_salary' => [
        'name' => 'High Salary - ₱100,000 Monthly',
        'gross_salary' => 100000,
        'pay_period' => 'monthly',
        'regular_hours' => 176,
        'overtime_hours' => 20,
        'undertime_hours' => 0,
        'late_minutes' => 0
    ]
];

// Run tests
if ($selectedTest === 'all' || isset($testCases[$selectedTest])) {
    if ($selectedTest === 'all') {
        foreach ($testCases as $key => $testCase) {
            $calculator = new PayrollCalculator($testCase['gross_salary'], $testCase['pay_period']);
            $results[$key] = array_merge(
                ['test_name' => $testCase['name']],
                $calculator->calculateCompleteSummary(
                    $testCase['regular_hours'],
                    $testCase['overtime_hours'],
                    $testCase['undertime_hours'],
                    $testCase['late_minutes']
                )
            );
        }
    } else {
        $testCase = $testCases[$selectedTest];
        $calculator = new PayrollCalculator($testCase['gross_salary'], $testCase['pay_period']);
        $results[$selectedTest] = array_merge(
            ['test_name' => $testCase['name']],
            $calculator->calculateCompleteSummary(
                $testCase['regular_hours'],
                $testCase['overtime_hours'],
                $testCase['undertime_hours'],
                $testCase['late_minutes']
            )
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Calculator Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 0; }
        .container { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .section { margin-top: 40px; }
        .result-card { background: #f8f9fa; border-left: 4px solid #007bff; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .summary-row { padding: 10px 0; border-bottom: 1px solid #e9ecef; }
        .summary-row:last-child { border-bottom: none; }
        .summary-label { font-weight: 500; color: #495057; }
        .summary-value { font-weight: bold; color: #007bff; text-align: right; }
        .summary-positive { color: #28a745; }
        .summary-negative { color: #dc3545; }
        .nav-link.active { background: #007bff; color: white; border-radius: 6px; }
        .nav-link { padding: 10px 15px; margin: 5px; border-radius: 6px; transition: all 0.3s; }
        .final-summary { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 20px; border-radius: 8px; margin-top: 20px; }
        .net-pay { font-size: 1.5rem; color: #2e7d32; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="mb-4">
        <h1 style="color: #667eea;"><i class="fas fa-calculator mr-2"></i> Payroll Calculator Demo</h1>
        <p class="text-muted">Philippine HRMS - Test the payroll calculation engine</p>
    </div>

    <!-- Test Selection -->
    <div class="section">
        <h5 class="mb-3">Select Test Case:</h5>
        <div class="btn-group flex-wrap" role="group">
            <a href="?test=all" class="btn btn-outline-primary <?php echo $selectedTest === 'all' ? 'active' : ''; ?>">
                All Tests
            </a>
            <?php foreach ($testCases as $key => $testCase): ?>
                <a href="?test=<?php echo $key; ?>" class="btn btn-outline-primary <?php echo $selectedTest === $key ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($testCase['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Results -->
    <?php if (!empty($results)): ?>
        <div class="section">
            <h3 class="mb-4">Results</h3>
            
            <?php foreach ($results as $resultKey => $result): ?>
                <div class="result-card">
                    <h5 class="mb-3">
                        <i class="fas fa-check-circle text-success mr-2"></i>
                        <?php echo htmlspecialchars($result['test_name']); ?>
                    </h5>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3">Gross Compensation</h6>
                            <div class="summary-row">
                                <span class="summary-label">Base Salary:</span>
                                <span class="summary-value">₱<?php echo number_format($result['gross_salary'], 2); ?></span>
                            </div>
                            <?php if ($result['overtime_pay'] > 0): ?>
                            <div class="summary-row">
                                <span class="summary-label"><i class="fas fa-plus text-success"></i> Overtime Pay (1.25x):</span>
                                <span class="summary-value summary-positive">+₱<?php echo number_format($result['overtime_pay'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($result['undertime_deduction'] > 0): ?>
                            <div class="summary-row">
                                <span class="summary-label"><i class="fas fa-minus text-danger"></i> Undertime:</span>
                                <span class="summary-value summary-negative">-₱<?php echo number_format($result['undertime_deduction'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($result['late_deduction'] > 0): ?>
                            <div class="summary-row">
                                <span class="summary-label"><i class="fas fa-minus text-danger"></i> Late Deduction:</span>
                                <span class="summary-value summary-negative">-₱<?php echo number_format($result['late_deduction'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="summary-row" style="border-top: 2px solid #007bff; margin-top: 10px; padding-top: 10px;">
                                <span class="summary-label font-weight-bold">Adjusted Gross:</span>
                                <span class="summary-value">₱<?php echo number_format($result['adjusted_gross_salary'], 2); ?></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3">Mandatory Deductions</h6>
                            <div class="summary-row">
                                <span class="summary-label">SSS (4.5% EE, capped):</span>
                                <span class="summary-value">₱<?php echo number_format($result['sss']['employee_share'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">PhilHealth (2.5% EE):</span>
                                <span class="summary-value">₱<?php echo number_format($result['philhealth']['employee_share'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Pag-IBIG (Fixed ₱200):</span>
                                <span class="summary-value">₱<?php echo number_format($result['pagibig']['employee_share'], 2); ?></span>
                            </div>
                            <div class="summary-row" style="border-top: 2px dashed #ccc; margin-top: 10px; padding-top: 10px;">
                                <span class="summary-label font-weight-bold">Total Mandatory:</span>
                                <span class="summary-value">₱<?php echo number_format($result['total_mandatory_deductions'], 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3">Tax Computation</h6>
                            <div class="summary-row">
                                <span class="summary-label">Taxable Income:</span>
                                <span class="summary-value">₱<?php echo number_format($result['taxable_income'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Withholding Tax (TRAIN):</span>
                                <span class="summary-value summary-negative">₱<?php echo number_format($result['withholding_tax'], 2); ?></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3">Summary</h6>
                            <div class="summary-row">
                                <span class="summary-label">Total Deductions:</span>
                                <span class="summary-value">₱<?php echo number_format($result['total_deductions'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label text-muted small">Employer Contributions (ref):</span>
                                <span class="summary-value">₱<?php echo number_format($result['employer_contributions'], 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="final-summary">
                        <div class="summary-row">
                            <span class="summary-label">Gross Pay:</span>
                            <span class="summary-value">₱<?php echo number_format($result['adjusted_gross_salary'], 2); ?></span>
                        </div>
                        <div class="summary-row" style="border: none; padding: 15px 0;">
                            <span class="net-pay">NET TAKE-HOME PAY:</span>
                            <span class="net-pay" style="float: right;">₱<?php echo number_format($result['net_take_home_pay'], 2); ?></span>
                        </div>
                    </div>

                    <div class="small text-muted mt-3">
                        <p><strong>Pay Period:</strong> <?php echo ucfirst($result['pay_period']); ?></p>
                        <p><strong>Calculated:</strong> <?php echo $result['calculation_date']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Comparison Table (if testing all) -->
    <?php if ($selectedTest === 'all' && !empty($results)): ?>
        <div class="section">
            <h3 class="mb-4">Comparison Summary</h3>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Test Case</th>
                            <th class="text-right">Gross</th>
                            <th class="text-right">Deductions</th>
                            <th class="text-right">Withholding Tax</th>
                            <th class="text-right">Net Pay</th>
                            <th class="text-right">Deduction %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): 
                            $deductionPercent = ($result['total_deductions'] / $result['adjusted_gross_salary']) * 100;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['test_name']); ?></td>
                                <td class="text-right">₱<?php echo number_format($result['adjusted_gross_salary'], 2); ?></td>
                                <td class="text-right">₱<?php echo number_format($result['total_deductions'] - $result['withholding_tax'], 2); ?></td>
                                <td class="text-right text-danger">₱<?php echo number_format($result['withholding_tax'], 2); ?></td>
                                <td class="text-right text-success font-weight-bold">₱<?php echo number_format($result['net_take_home_pay'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($deductionPercent, 2); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Instructions -->
    <div class="section alert alert-info">
        <h5><i class="fas fa-info-circle mr-2"></i> How to Use</h5>
        <ol>
            <li>Select a test case above or run all tests</li>
            <li>Review the detailed payroll computation</li>
            <li>Check against expected values per Philippine payroll regulations</li>
            <li>Use the <code>compare table</code> to analyze deduction percentages</li>
        </ol>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
