<?php
/**
 * Quick Reference Guide - Payroll Module
 * For HR Staff to quickly understand and use the system
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Module - Quick Reference</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; }
        .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .section-divider { height: 3px; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); margin: 30px 0; }
        .quick-tip { background: #e8f4f8; border-left: 4px solid #0066cc; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .formula-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .checklist { margin-left: 20px; }
        .checklist li { margin: 8px 0; }
        .rate-table { background: white; }
        table { margin: 20px 0; }
    </style>
</head>
<body>
<div class="container-fluid py-5">

    <div class="row mb-5">
        <div class="col-md-12">
            <h1 class="mb-2"><i class="fas fa-calculator"></i> Payroll Module - Quick Reference</h1>
            <p class="text-muted">A quick guide for HR Staff to understand and use the payroll calculation system</p>
        </div>
    </div>

    <!-- Quick Start -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-rocket"></i> Quick Start in 5 Steps</h5>
        </div>
        <div class="card-body">
            <ol class="checklist">
                <li><strong>Log in as HR Staff</strong> - Access from the main dashboard</li>
                <li><strong>Navigate to Payroll</strong> → Click "Calculate Payroll" button</li>
                <li><strong>Fill the form:</strong>
                    <ul>
                        <li>Select an Employee from dropdown</li>
                        <li>Choose Pay Period (Monthly, Semi-Monthly, or Daily)</li>
                        <li>Enter Gross Salary for this period</li>
                        <li>Select Start and End dates</li>
                    </ul>
                </li>
                <li><strong>Click "Calculate & Process Payroll"</strong></li>
                <li><strong>Review the Summary</strong> - Check all calculations before saving</li>
            </ol>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- What Gets Deducted -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> What Gets Deducted from Salary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="font-weight-bold">SSS (Social Security)</h6>
                    <p><strong>Rate:</strong> 4.5% of gross salary</p>
                    <p><strong>Cap:</strong> Maximum ₱1,350 (for salaries ≥ ₱30,000)</p>
                    <p class="text-muted"><small>Example: ₱25,000 × 4.5% = ₱1,125</small></p>
                </div>
                <div class="col-md-4">
                    <h6 class="font-weight-bold">PhilHealth (Health Insurance)</h6>
                    <p><strong>Rate:</strong> 2.5% of gross salary</p>
                    <p><strong>Limits:</strong> Min ₱250, Max ₱2,500</p>
                    <p class="text-muted"><small>Example: ₱25,000 × 2.5% = ₱625</small></p>
                </div>
                <div class="col-md-4">
                    <h6 class="font-weight-bold">Pag-IBIG (Home Fund)</h6>
                    <p><strong>Rate:</strong> Fixed ₱200</p>
                    <p><strong>Only if:</strong> Salary is above ₱5,000</p>
                    <p class="text-muted"><small>Applies to: Most full-time employees</small></p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="font-weight-bold">Withholding Tax (Income Tax)</h6>
                    <p><strong>Based on:</strong> TRAIN Law 2023-2026</p>
                    <p><strong>Tax-Free if earning:</strong> ₱20,833 or less/month</p>
                    <p class="text-muted"><small>Rest of salaries are taxed progressively</small></p>
                </div>
                <div class="col-md-6">
                    <h6 class="font-weight-bold">Time-Based Adjustments</h6>
                    <p><strong>ADD:</strong> Overtime at 1.25× pay (overtime premium)</p>
                    <p><strong>SUBTRACT:</strong> Undertime & Late deductions</p>
                    <p class="text-muted"><small>Automatically calculated from attendance</small></p>
                </div>
            </div>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- Deduction Rates -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-percentage"></i> Official 2025-2026 Deduction Rates</h5>
        </div>
        <div class="card-body">
            <h6 class="font-weight-bold mb-3">SSS & PhilHealth</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>SSS Employee Share:</strong></td>
                    <td>4.5% (capped at ₱1,350)</td>
                </tr>
                <tr>
                    <td><strong>PhilHealth Employee Share:</strong></td>
                    <td>2.5% (₱250 - ₱2,500 range)</td>
                </tr>
                <tr>
                    <td><strong>Pag-IBIG Employee Share:</strong></td>
                    <td>Fixed ₱200</td>
                </tr>
            </table>

            <h6 class="font-weight-bold mb-3 mt-4">Withholding Tax (TRAIN Law)</h6>
            <table class="table table-sm table-striped">
                <thead class="table-secondary">
                    <tr>
                        <th>Monthly Taxable Income</th>
                        <th>Tax Calculation</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Up to ₱20,833</td>
                        <td><strong>No tax</strong></td>
                        <td>Earning ₱15,000? No tax.</td>
                    </tr>
                    <tr>
                        <td>₱20,834 - ₱33,332</td>
                        <td>15% of excess above ₱20,833</td>
                        <td>₱25,000: (25,000-20,833) × 15% = ₱625.55</td>
                    </tr>
                    <tr>
                        <td>₱33,333 - ₱66,666</td>
                        <td>₱1,875 + 20% above ₱33,333</td>
                        <td>₱40,000: ₱1,875 + (40,000-33,333)×20% = ₱2,208.60</td>
                    </tr>
                    <tr>
                        <td>₱66,667 - ₱166,666</td>
                        <td>₱8,542 + 25% above ₱66,667</td>
                        <td>₱80,000: ₱8,542 + (80,000-66,667)×25% = ₱12,875.25</td>
                    </tr>
                </tbody>
            </table>

            <div class="quick-tip">
                <strong>💡 Tip:</strong> The system automatically calculates all these. You just need to enter the gross salary and dates!
            </div>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- Time Adjustments -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-clock"></i> Time-Based Adjustments</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="font-weight-bold text-success">OVERTIME PAY (Added)</h6>
                    <p><strong>Rate:</strong> 1.25× regular hourly rate</p>
                    <p><strong>Applied if:</strong> Attendance shows "Approved" overtime</p>
                    <p class="text-muted"><small>Example: 1 hr O.T. @ ₱295/hr = ₱369</small></p>
                </div>
                <div class="col-md-4">
                    <h6 class="font-weight-bold text-warning">UNDERTIME (Deducted)</h6>
                    <p><strong>Rate:</strong> Regular hourly rate</p>
                    <p><strong>Applied if:</strong> Employee worked less than full day</p>
                    <p class="text-muted"><small>Example: 2 hrs undertime @ ₱295/hr = ₱590</small></p>
                </div>
                <div class="col-md-4">
                    <h6 class="font-weight-bold text-danger">LATE DEDUCTION (Deducted)</h6>
                    <p><strong>Rate:</strong> Proportional to hourly rate</p>
                    <p><strong>Applied if:</strong> Attendance shows "Late" status</p>
                    <p class="text-muted"><small>Example: 60 min late ≈ ₱147/hr deduction</small></p>
                </div>
            </div>

            <div class="formula-box mt-3">
                <strong>Formula Example: ₱25,000 salary with adjustments</strong><br><br>
                Hourly Rate = ₱25,000 ÷ (22 working days × 8 hrs) = ₱141.45/hr<br><br>
                <strong>Adjusted Gross = ₱25,000 + O.T. Pay - Undertime - Late</strong><br>
                = ₱25,000 + (8 hrs × ₱141.45 × 1.25) - (2 hrs × ₱141.45) - (60 min penalty)<br>
                = ₱25,000 + ₱1,131.60 - ₱282.90 - ₱59.13<br>
                = <strong>₱25,789.57</strong>
            </div>

            <div class="quick-tip mt-3">
                <strong>⚠️ Important:</strong> These adjustments are calculated automatically from your Attendance records. Ensure attendance is properly recorded for accurate payroll!
            </div>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- Complete Example -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Complete Payroll Example</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Let's calculate payroll for an employee earning ₱30,000/month with 8 hours overtime:</p>

            <table class="table table-borderless">
                <tr style="background: #f0f7ff; font-weight: bold;">
                    <td colspan="2">BASE COMPENSATION</td>
                </tr>
                <tr>
                    <td>Base Salary</td>
                    <td class="text-right">₱30,000.00</td>
                </tr>
                <tr>
                    <td><span class="text-success">+ Overtime (8 hrs @ 1.25x ₱136.36/hr)</span></td>
                    <td class="text-right text-success">+₱1,090.91</td>
                </tr>
                <tr style="background: #e8f5e9;">
                    <td><strong>TOTAL GROSS</strong></td>
                    <td class="text-right"><strong>₱31,090.91</strong></td>
                </tr>

                <tr style="background: #f0f7ff; font-weight: bold; margin-top: 20px;">
                    <td colspan="2">MANDATORY DEDUCTIONS</td>
                </tr>
                <tr>
                    <td>SSS (4.5%, capped @ ₱1,350)</td>
                    <td class="text-right">-₱1,350.00</td>
                </tr>
                <tr>
                    <td>PhilHealth (2.5%)</td>
                    <td class="text-right">-₱750.00</td>
                </tr>
                <tr>
                    <td>Pag-IBIG (Fixed)</td>
                    <td class="text-right">-₱200.00</td>
                </tr>
                <tr style="background: #fff3cd;">
                    <td><strong>Subtotal Mandatory</strong></td>
                    <td class="text-right"><strong>-₱2,300.00</strong></td>
                </tr>

                <tr>
                    <td colspan="2"></td>
                </tr>

                <tr style="background: #f0f7ff; font-weight: bold;">
                    <td colspan="2">TAX COMPUTATION</td>
                </tr>
                <tr>
                    <td>Taxable Income (Gross - Mandatory)</td>
                    <td class="text-right">₱28,790.91</td>
                </tr>
                <tr>
                    <td>Withholding Tax: 15% of (28,790.91 - 20,833)</td>
                    <td class="text-right text-danger">-₱1,193.69</td>
                </tr>

                <tr style="background: #e8f5e9;">
                    <td colspan="2"></td>
                </tr>

                <tr style="background: #e8f5e9; font-weight: bold; font-size: 1.1rem;">
                    <td>NET TAKE-HOME PAY</td>
                    <td class="text-right" style="color: #2e7d32;">₱27,597.22</td>
                </tr>
            </table>

            <div class="quick-tip">
                <strong>✓ Summary:</strong>
                <ul style="margin: 10px 0;">
                    <li>Total Deductions: ₱3,493.69 (11.2% of gross)</li>
                    <li>Employee keeps: ₱27,597.22 (88.8% of gross)</li>
                    <li>Employer pays extra: ₱2,300 (SSS+PhilHealth+Pag-IBIG employer share)</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- Common Questions -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-question-circle"></i> Common Questions</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><strong>Q: What if an employee earns ₱20,000/month?</strong></h6>
                    <p class="text-muted">A: They pay SSS, PhilHealth, and Pag-IBIG deductions but <strong>NO WITHHOLDING TAX</strong> (tax-exempt). The system still calculates it for records.</p>

                    <h6 class="mt-3"><strong>Q: How is overtime exactly calculated?</strong></h6>
                    <p class="text-muted">A: Overtime = (Hourly Rate × 1.25) × Overtime Hours.<br>
                    Example: If hourly rate is ₱295/hr, 1 hour overtime = ₱295 × 1.25 = ₱369</p>

                    <h6 class="mt-3"><strong>Q: What if someone hasn't worked the full month?</strong></h6>
                    <p class="text-muted">A: Calculate their gross salary for the days they worked. The system will deduct accordingly. No special logic needed.</p>
                </div>
                <div class="col-md-6">
                    <h6><strong>Q: Why is SSS capped at ₱1,350?</strong></h6>
                    <p class="text-muted">A: This is the Monthly Salary Credit (MSC) ceiling set by SSS for salaries ≥ ₱30,000. It's a government regulation.</p>

                    <h6 class="mt-3"><strong>Q: Do I manually enter overtime, or does it come from Attendance?</strong></h6>
                    <p class="text-muted">A: The system <strong>automatically pulls</strong> approved overtime from Attendance records. Just make sure attendance is properly recorded.</p>

                    <h6 class="mt-3"><strong>Q: Can I pay different rates for different employees?</strong></h6>
                    <p class="text-muted">A: Yes! The "Gross Salary" you enter is each employee's specific rate. No standard rate - just what you enter.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- Tools & Resources -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-tools"></i> Tools & Resources</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="font-weight-bold">🧪 Test the Calculator</h6>
                    <p>Visit <code>payroll_demo.php</code> to run 7 test cases and see how calculations work.</p>
                    <a href="../payroll_demo.php" class="btn btn-sm btn-info" target="_blank">
                        <i class="fas fa-flask"></i> Open Demo
                    </a>
                </div>
                <div class="col-md-6">
                    <h6 class="font-weight-bold">📚 Full Documentation</h6>
                    <p>Read the complete guide with code examples at <code>docs/PAYROLL_DOCUMENTATION.php</code></p>
                    <a href="../docs/PAYROLL_DOCUMENTATION.php" class="btn btn-sm btn-info" target="_blank">
                        <i class="fas fa-book"></i> Read Docs
                    </a>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6 class="font-weight-bold">⚙️ Process Payroll</h6>
                    <p>Start calculating payroll for employees through the form interface.</p>
                    <a href="payroll_processing.php" class="btn btn-sm btn-success">
                        <i class="fas fa-plus-circle"></i> Go to Payroll Processing
                    </a>
                </div>
                <div class="col-md-6">
                    <h6 class="font-weight-bold">📋 View Records</h6>
                    <p>See all processed payroll records and employee history.</p>
                    <a href="payrollhr.php" class="btn btn-sm btn-success">
                        <i class="fas fa-list"></i> View Payroll Records
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- Key Reminders -->
    <div class="card border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Important Reminders</h5>
        </div>
        <div class="card-body">
            <ul style="margin: 0; padding-left: 20px;">
                <li><strong>Attendance matters:</strong> Correct attendance records = correct payroll. Update daily!</li>
                <li><strong>Overtime approval:</strong> Only "Approved" overtime is included. Mark in attendance system.</li>
                <li><strong>Government compliance:</strong> Rates are based on 2025-2026 SSS/PhilHealth/Pag-IBIG and TRAIN Law regulations.</li>
                <li><strong>Double-check:</strong> Always review the payroll summary before finalizing. Mistakes are hard to undo.</li>
                <li><strong>Keep records:</strong> Store all payroll calculations for audit and compliance purposes.</li>
                <li><strong>Privacy:</strong> Payroll information is sensitive. Only authorized HR staff should access.</li>
            </ul>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
