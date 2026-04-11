<?php
/**
 * Database Migration Script
 * Alters the payroll table to include all Philippine mandatory deductions
 * Run this file once to update the database schema
 */

require 'database.php';

try {
    $mysql->begin_transaction();

    // Check if 'sss_employee' column exists, if not add all new columns
    $result = $mysql->query("SHOW COLUMNS FROM payroll LIKE 'sss_employee_share'");
    
    if ($result->num_rows == 0) {
        // Add all new columns for Philippine payroll requirements
        $alterQueries = [
            // SSS Contribution
            "ALTER TABLE payroll ADD COLUMN sss_employee_share DECIMAL(10,2) DEFAULT 0.00 AFTER gross_salary",
            "ALTER TABLE payroll ADD COLUMN sss_employer_share DECIMAL(10,2) DEFAULT 0.00 AFTER sss_employee_share",
            
            // PhilHealth Contribution
            "ALTER TABLE payroll ADD COLUMN philhealth_employee_share DECIMAL(10,2) DEFAULT 0.00 AFTER sss_employer_share",
            "ALTER TABLE payroll ADD COLUMN philhealth_employer_share DECIMAL(10,2) DEFAULT 0.00 AFTER philhealth_employee_share",
            
            // Pag-IBIG Contribution (HDMF)
            "ALTER TABLE payroll ADD COLUMN pagibig_employee_share DECIMAL(10,2) DEFAULT 0.00 AFTER philhealth_employer_share",
            "ALTER TABLE payroll ADD COLUMN pagibig_employer_share DECIMAL(10,2) DEFAULT 0.00 AFTER pagibig_employee_share",
            
            // Time Adjustments
            "ALTER TABLE payroll ADD COLUMN overtime_hours DECIMAL(5,2) DEFAULT 0.00 AFTER pagibig_employer_share",
            "ALTER TABLE payroll ADD COLUMN overtime_pay DECIMAL(10,2) DEFAULT 0.00 AFTER overtime_hours",
            "ALTER TABLE payroll ADD COLUMN undertime_hours DECIMAL(5,2) DEFAULT 0.00 AFTER overtime_pay",
            "ALTER TABLE payroll ADD COLUMN undertime_deduction DECIMAL(10,2) DEFAULT 0.00 AFTER undertime_hours",
            "ALTER TABLE payroll ADD COLUMN late_minutes INT DEFAULT 0 AFTER undertime_deduction",
            "ALTER TABLE payroll ADD COLUMN late_deduction DECIMAL(10,2) DEFAULT 0.00 AFTER late_minutes",
            
            // Total Mandatory Deductions
            "ALTER TABLE payroll ADD COLUMN total_mandatory_deductions DECIMAL(10,2) DEFAULT 0.00 AFTER late_deduction",
            
            // Taxable Income and Withholding Tax
            "ALTER TABLE payroll ADD COLUMN taxable_income DECIMAL(10,2) DEFAULT 0.00 AFTER total_mandatory_deductions",
            "ALTER TABLE payroll ADD COLUMN withholding_tax DECIMAL(10,2) DEFAULT 0.00 AFTER taxable_income",
            
            // Total Deductions (Mandatory + Withholding Tax)
            "ALTER TABLE payroll ADD COLUMN total_deductions DECIMAL(10,2) DEFAULT 0.00 AFTER withholding_tax",
            
            // Adjusted Gross Salary
            "ALTER TABLE payroll ADD COLUMN adjusted_gross_salary DECIMAL(10,2) DEFAULT 0.00 AFTER total_deductions",
            
            // Total Employer Contributions (for reference)
            "ALTER TABLE payroll ADD COLUMN total_employer_contributions DECIMAL(10,2) DEFAULT 0.00 AFTER adjusted_gross_salary",
            
            // Payment Details
            "ALTER TABLE payroll ADD COLUMN payment_method VARCHAR(50) DEFAULT 'Bank Transfer' AFTER total_employer_contributions",
            "ALTER TABLE payroll ADD COLUMN remarks TEXT AFTER payment_method",
            
            // Process Information
            "ALTER TABLE payroll ADD COLUMN processed_by INT AFTER remarks",
            "ALTER TABLE payroll ADD COLUMN processed_date DATETIME AFTER processed_by",
        ];

        foreach ($alterQueries as $query) {
            if (!$mysql->query($query)) {
                throw new Exception("Error executing: " . $query . " | Error: " . $mysql->error);
            }
            echo "✓ " . $query . "<br>";
        }

        // Update existing records with default calculations (simple deduction)
        $mysql->query("UPDATE payroll SET 
            sss_employee_share = gross_salary * 0.045,
            sss_employer_share = gross_salary * 0.095,
            philhealth_employee_share = (gross_salary * 0.025),
            philhealth_employer_share = (gross_salary * 0.025),
            pagibig_employee_share = CASE WHEN gross_salary >= 5000 THEN 200 ELSE 0 END,
            pagibig_employer_share = CASE WHEN gross_salary >= 5000 THEN 200 ELSE 0 END,
            total_mandatory_deductions = (gross_salary * 0.045) + (gross_salary * 0.025) + CASE WHEN gross_salary >= 5000 THEN 200 ELSE 0 END,
            taxable_income = gross_salary - ((gross_salary * 0.045) + (gross_salary * 0.025) + CASE WHEN gross_salary >= 5000 THEN 200 ELSE 0 END)
        ");

        echo "<br><strong style='color: green;'>✓ Database migration completed successfully!</strong><br>";
        echo "New payroll fields have been added to the payroll table.";

        $mysql->commit();
    } else {
        echo "<strong style='color: blue;'>ℹ Database is already up to date.</strong>";
    }

} catch (Exception $e) {
    $mysql->rollback();
    echo "<strong style='color: red;'>✗ Migration Error: " . htmlspecialchars($e->getMessage()) . "</strong>";
}

?>
