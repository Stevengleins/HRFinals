<?php
/**
 * Philippine HRMS Payroll Calculator
 * Handles all mandatory government deductions and withholding taxes
 * Based on 2025/2026 TRAIN Law rates
 */

class PayrollCalculator
{
    // SSS Configuration
    const SSS_EMPLOYEE_RATE = 0.045;      // 4.5%
    const SSS_EMPLOYER_RATE = 0.095;      // 9.5%
    const SSS_MSC_CEILING = 1350;         // Monthly Salary Credit ceiling (employee share)
    const SSS_MSC_BASE = 30000;           // Base salary for MSC ceiling application

    // PhilHealth Configuration
    const PHILHEALTH_RATE = 0.05;         // 5% total (2.5% each)
    const PHILHEALTH_MIN = 500;           // Minimum total contribution (250 per share)
    const PHILHEALTH_MAX = 5000;          // Maximum total contribution (2500 per share)

    // Pag-IBIG Configuration
    const PAGIBIG_EMPLOYEE_SHARE = 200;   // Fixed PHP 200
    const PAGIBIG_EMPLOYER_SHARE = 200;   // Fixed PHP 200
    const PAGIBIG_SALARY_THRESHOLD = 5000; // Minimum salary for Pag-IBIG

    // Withholding Tax Brackets (TRAIN Law 2023-2026)
    const WITHHOLDING_TAX_BRACKETS = [
        ['min' => 0, 'max' => 20833, 'base_tax' => 0, 'rate' => 0, 'excess_base' => 0],
        ['min' => 20834, 'max' => 33332, 'base_tax' => 0, 'rate' => 0.15, 'excess_base' => 20833],
        ['min' => 33333, 'max' => 66666, 'base_tax' => 1875, 'rate' => 0.20, 'excess_base' => 33333],
        ['min' => 66667, 'max' => 166666, 'base_tax' => 8542, 'rate' => 0.25, 'excess_base' => 66667],
        ['min' => 166667, 'max' => 666666, 'base_tax' => 33958, 'rate' => 0.30, 'excess_base' => 166667],
        ['min' => 666667, 'max' => PHP_INT_MAX, 'base_tax' => 183958, 'rate' => 0.35, 'excess_base' => 666667],
    ];

    private $grossSalary = 0;
    private $payPeriod = 'monthly'; // monthly, semi-monthly, daily

    /**
     * Initialize calculator with gross salary and pay period
     */
    public function __construct($grossSalary = 0, $payPeriod = 'monthly')
    {
        $this->grossSalary = (float) $grossSalary;
        $this->payPeriod = strtolower($payPeriod);
    }

    /**
     * Calculate SSS contribution
     * Returns array with employee_share and employer_share
     */
    public function calculateSSS()
    {
        $employee_share = $this->grossSalary * self::SSS_EMPLOYEE_RATE;

        // Apply MSC ceiling for salaries 30,000 and above
        if ($this->grossSalary >= self::SSS_MSC_BASE) {
            $employee_share = min($employee_share, self::SSS_MSC_CEILING);
        }

        $employer_share = $this->grossSalary * self::SSS_EMPLOYER_RATE;
        if ($this->grossSalary >= self::SSS_MSC_BASE) {
            $employer_share = min($employer_share, self::SSS_MSC_CEILING);
        }

        return [
            'employee_share' => round($employee_share, 2),
            'employer_share' => round($employer_share, 2),
            'total' => round($employee_share + $employer_share, 2)
        ];
    }

    /**
     * Calculate PhilHealth contribution
     * Returns array with employee_share and employer_share
     */
    public function calculatePhilHealth()
    {
        $total_contribution = $this->grossSalary * self::PHILHEALTH_RATE;

        // Apply maximum limit only; low salaries contribute 2.5% employee and 2.5% employer
        $total_contribution = min($total_contribution, self::PHILHEALTH_MAX);

        // Divide equally between employee and employer
        $employee_share = $total_contribution / 2;
        $employer_share = $total_contribution / 2;

        return [
            'employee_share' => round($employee_share, 2),
            'employer_share' => round($employer_share, 2),
            'total' => round($total_contribution, 2)
        ];
    }

    /**
     * Calculate Pag-IBIG (HDMF) contribution
     * Applied only for salaries above 5,000 PHP
     */
    public function calculatePagIBIG()
    {
        if ($this->grossSalary < self::PAGIBIG_SALARY_THRESHOLD) {
            return [
                'employee_share' => 0,
                'employer_share' => 0,
                'total' => 0
            ];
        }

        return [
            'employee_share' => self::PAGIBIG_EMPLOYEE_SHARE,
            'employer_share' => self::PAGIBIG_EMPLOYER_SHARE,
            'total' => self::PAGIBIG_EMPLOYEE_SHARE + self::PAGIBIG_EMPLOYER_SHARE
        ];
    }

    /**
     * Calculate taxable income
     * Gross Salary - (SSS + PhilHealth + Pag-IBIG Employee Shares)
     */
    public function calculateTaxableIncome($sss, $philhealth, $pagibig)
    {
        $total_mandatory_deductions = $sss['employee_share'] + $philhealth['employee_share'] + $pagibig['employee_share'];
        return round($this->grossSalary - $total_mandatory_deductions, 2);
    }

    /**
     * Calculate withholding tax based on TRAIN Law 2023-2026
     * Graduated tax rates
     */
    public function calculateWithholdingTax($taxableIncome)
    {
        // Tax exempt if taxable income is 20,833 or below
        if ($taxableIncome <= 20833) {
            return round(0, 2);
        }

        foreach (self::WITHHOLDING_TAX_BRACKETS as $bracket) {
            if ($taxableIncome >= $bracket['min'] && $taxableIncome <= $bracket['max']) {
                $excess = $taxableIncome - $bracket['excess_base'];
                $tax = $bracket['base_tax'] + ($excess * $bracket['rate']);
                return round($tax, 2);
            }
        }

        return round(0, 2);
    }

    /**
     * Calculate adjustments for overtime, undertime, and late
     * Parameters:
     * - regularHours: normal working hours in the period
     * - overtimeHours: approved overtime hours
     * - undertimeHours: undertime hours
     * - lateMinutes: total late minutes
     * - hourlyRate: hourly rate
     */
    public function calculateTimeAdjustments($regularHours, $overtimeHours = 0, $undertimeHours = 0, $lateMinutes = 0, $hourlyRate = null)
    {
        if ($hourlyRate === null) {
            // Calculate hourly rate from gross salary and regular hours
            if ($this->payPeriod === 'monthly') {
                // Assuming 22 working days, 8 hours per day = 176 hours
                $hourlyRate = $this->grossSalary / 176;
            } elseif ($this->payPeriod === 'semi-monthly') {
                // Assuming 11 working days, 8 hours per day = 88 hours
                $hourlyRate = $this->grossSalary / 88;
            } elseif ($this->payPeriod === 'daily') {
                // Assuming 8 hours per day
                $hourlyRate = $this->grossSalary / 8;
            }
        }

        $adjustments = [];

        // Overtime at 1.25x rate (25% premium)
        if ($overtimeHours > 0) {
            $adjustments['overtime_pay'] = round($overtimeHours * $hourlyRate * 1.25, 2);
        } else {
            $adjustments['overtime_pay'] = 0;
        }

        // Undertime deduction (full deduction)
        if ($undertimeHours > 0) {
            $adjustments['undertime_deduction'] = round($undertimeHours * $hourlyRate, 2);
        } else {
            $adjustments['undertime_deduction'] = 0;
        }

        // Late deduction (1 minute late = 1/480 of hourly rate deduction per 8-hour day)
        // Or simplified: deduct proportionally
        if ($lateMinutes > 0) {
            $adjustments['late_deduction'] = round(($lateMinutes / 480) * $hourlyRate * 8, 2);
        } else {
            $adjustments['late_deduction'] = 0;
        }

        $adjustments['total_adjustments'] = round(
            $adjustments['overtime_pay'] - $adjustments['undertime_deduction'] - $adjustments['late_deduction'],
            2
        );

        return $adjustments;
    }

    /**
     * Calculate complete payroll summary
     * Parameters:
     * - regularHours: normal working hours
     * - overtimeHours: approved overtime hours
     * - undertimeHours: undertime hours
     * - lateMinutes: total late minutes
     * - employeeData: array with employee info (optional, for reference)
     */
    public function calculateCompleteSummary(
        $regularHours = null,
        $overtimeHours = 0,
        $undertimeHours = 0,
        $lateMinutes = 0,
        $employeeData = []
    )
    {
        // Calculate mandatory deductions
        $sss = $this->calculateSSS();
        $philhealth = $this->calculatePhilHealth();
        $pagibig = $this->calculatePagIBIG();

        // Calculate time-based adjustments
        $adjustments = $this->calculateTimeAdjustments(
            $regularHours,
            $overtimeHours,
            $undertimeHours,
            $lateMinutes
        );

        // Adjust gross salary with time adjustments
        $adjusted_gross = $this->grossSalary + $adjustments['total_adjustments'];
        $adjusted_gross = max($adjusted_gross, 0); // Cannot be negative

        // Calculate taxable income based on adjusted gross
        $taxableIncome = $this->calculateTaxableIncome($sss, $philhealth, $pagibig);

        // Calculate withholding tax
        $withholding_tax = $this->calculateWithholdingTax($taxableIncome);

        // Calculate total deductions
        $total_mandatory = $sss['employee_share'] + $philhealth['employee_share'] + $pagibig['employee_share'];
        $total_deductions = round($total_mandatory + $withholding_tax + $adjustments['undertime_deduction'] + $adjustments['late_deduction'], 2);

        // Calculate net take-home pay
        $net_take_home = round($this->grossSalary + $adjustments['total_adjustments'] - $total_deductions, 2);
        $net_take_home = max($net_take_home, 0);

        // Compile complete summary
        $summary = [
            // Base Information
            'employee_data' => $employeeData,
            
            // Gross Compensation
            'gross_salary' => round($this->grossSalary, 2),
            'overtime_hours' => $overtimeHours,
            'overtime_pay' => $adjustments['overtime_pay'],
            'undertime_hours' => $undertimeHours,
            'undertime_deduction' => $adjustments['undertime_deduction'],
            'late_minutes' => $lateMinutes,
            'late_deduction' => $adjustments['late_deduction'],
            'adjusted_gross_salary' => round($adjusted_gross, 2),

            // Mandatory Deductions
            'sss' => $sss,
            'philhealth' => $philhealth,
            'pagibig' => $pagibig,
            'total_mandatory_deductions' => round($total_mandatory, 2),

            // Tax Computation
            'taxable_income' => round($taxableIncome, 2),
            'withholding_tax' => $withholding_tax,

            // Time Adjustments Details
            'time_adjustments' => $adjustments,

            // Final Summary
            'total_deductions' => $total_deductions,
            'net_take_home_pay' => $net_take_home,

            // Employer Contributions (for reference, not deducted from employee)
            'employer_contributions' => round($sss['employer_share'] + $philhealth['employer_share'] + $pagibig['employer_share'], 2),

            // Additional Details
            'pay_period' => $this->payPeriod,
            'calculation_date' => date('Y-m-d H:i:s')
        ];

        return $summary;
    }

    /**
     * Format summary for display
     */
    public static function formatSummary($summary)
    {
        return [
            'gross_pay' => 'PHP ' . number_format($summary['gross_salary'], 2),
            'overtime_pay' => 'PHP ' . number_format($summary['overtime_pay'], 2),
            'undertime_deduction' => 'PHP ' . number_format($summary['undertime_deduction'], 2),
            'late_deduction' => 'PHP ' . number_format($summary['late_deduction'], 2),
            'adjusted_gross_salary' => 'PHP ' . number_format($summary['adjusted_gross_salary'], 2),
            
            'sss_employee' => 'PHP ' . number_format($summary['sss']['employee_share'], 2),
            'sss_employer' => 'PHP ' . number_format($summary['sss']['employer_share'], 2),
            
            'philhealth_employee' => 'PHP ' . number_format($summary['philhealth']['employee_share'], 2),
            'philhealth_employer' => 'PHP ' . number_format($summary['philhealth']['employer_share'], 2),
            
            'pagibig_employee' => 'PHP ' . number_format($summary['pagibig']['employee_share'], 2),
            'pagibig_employer' => 'PHP ' . number_format($summary['pagibig']['employer_share'], 2),
            
            'total_mandatory_deductions' => 'PHP ' . number_format($summary['total_mandatory_deductions'], 2),
            'taxable_income' => 'PHP ' . number_format($summary['taxable_income'], 2),
            'withholding_tax' => 'PHP ' . number_format($summary['withholding_tax'], 2),
            'total_deductions' => 'PHP ' . number_format($summary['total_deductions'], 2),
            'net_take_home_pay' => 'PHP ' . number_format($summary['net_take_home_pay'], 2),
        ];
    }
}
?>
