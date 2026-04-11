<?php
require 'includes/PayrollCalculator.php';

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
    'semi_monthly' => [
        'name' => 'Semi-Monthly Pay Period - ₱12,500',
        'gross_salary' => 12500,
        'pay_period' => 'semi-monthly',
        'regular_hours' => 88,
        'overtime_hours' => 4,
        'undertime_hours' => 2,
        'late_minutes' => 30
    ]
];

foreach ($testCases as $key => $testCase) {
    echo "Testing $key\n";
    $calculator = new PayrollCalculator($testCase['gross_salary'], $testCase['pay_period']);
    $result = $calculator->calculateCompleteSummary(
        $testCase['regular_hours'],
        $testCase['overtime_hours'],
        $testCase['undertime_hours'],
        $testCase['late_minutes']
    );
    echo "Net pay: " . $result['net_take_home_pay'] . "\n";
}
?>