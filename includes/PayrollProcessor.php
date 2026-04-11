<?php
/**
 * Payroll Processing Module
 * Integrates attendance data with payroll calculations
 */

require_once __DIR__ . '/PayrollCalculator.php';

class PayrollProcessor
{
    private $mysql;
    private $calculator;

    public function __construct($mysqlConnection)
    {
        $this->mysql = $mysqlConnection;
    }

    /**
     * Get employee attendance data for a specific period
     */
    public function getAttendanceData($userId, $startDate, $endDate)
    {
        $query = "SELECT 
                    SUM(regular_hours) as total_regular_hours,
                    SUM(overtime_hours) as total_overtime_hours,
                    SUM(CASE WHEN status = 'Late' THEN 30 ELSE 0 END) as late_minutes,
                    SUM(CASE WHEN status = 'Half-Day' THEN 4 ELSE 0 END) as undertime_hours,
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'Present' OR status = 'Late' THEN 1 ELSE 0 END) as days_present,
                    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as days_absent,
                    SUM(CASE WHEN status = 'Half-Day' THEN 1 ELSE 0 END) as half_days,
                    SUM(CASE WHEN overtime_status = 'Approved' THEN overtime_hours ELSE 0 END) as approved_overtime
                FROM attendance
                WHERE user_id = ? AND date BETWEEN ? AND ?";

        $stmt = $this->mysql->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->mysql->error);
        }

        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data ?: [
            'total_regular_hours' => 0,
            'total_overtime_hours' => 0,
            'late_minutes' => 0,
            'undertime_hours' => 0,
            'total_days' => 0,
            'days_present' => 0,
            'days_absent' => 0,
            'half_days' => 0,
            'approved_overtime' => 0
        ];
    }

    /**
     * Get employee salary information
     */
    public function getEmployeeSalary($userId)
    {
        $query = "SELECT 
                    u.user_id, 
                    u.first_name, 
                    u.last_name,
                    u.email,
                    ed.position,
                    ed.join_date
                FROM user u
                LEFT JOIN employee_details ed ON u.user_id = ed.user_id
                WHERE u.user_id = ?";

        $stmt = $this->mysql->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->mysql->error);
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data;
    }

    /**
     * Calculate payroll for an employee
     * $grossSalary: base gross salary
     * $startDate: payroll period start date
     * $endDate: payroll period end date
     * $payPeriod: 'monthly', 'semi-monthly', or 'daily'
     */
    public function calculateEmployeePayroll(
        $userId,
        $grossSalary,
        $startDate,
        $endDate,
        $payPeriod = 'semi-monthly'
    )
    {
        // Get employee information
        $employeeData = $this->getEmployeeSalary($userId);
        if (!$employeeData) {
            throw new Exception("Employee not found");
        }

        // Get attendance data
        $attendanceData = $this->getAttendanceData($userId, $startDate, $endDate);

        // Determine regular hours based on pay period
        $regularHours = $this->getExpectedRegularHours($payPeriod, $startDate, $endDate);

        // Use approved overtime, not total
        $overtimeHours = $attendanceData['approved_overtime'] ?? $attendanceData['total_overtime_hours'];
        $undertimeHours = $attendanceData['undertime_hours'] ?? 0;
        
        // Calculate late deduction based on days late
        // Assuming 30 minutes late per late day (standard grace period is usually exceeded)
        $lateCount = 0;
        $lateQuery = "SELECT COUNT(*) as late_count FROM attendance 
                      WHERE user_id = ? AND date BETWEEN ? AND ? AND status = 'Late'";
        $stmt = $this->mysql->prepare($lateQuery);
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $lateData = $result->fetch_assoc();
        $stmt->close();
        
        $lateMinutes = ($lateData['late_count'] ?? 0) * 30; // 30 minutes per late instance

        // Initialize calculator
        $this->calculator = new PayrollCalculator($grossSalary, $payPeriod);

        // Calculate complete summary
        $summary = $this->calculator->calculateCompleteSummary(
            $regularHours,
            $overtimeHours,
            $undertimeHours,
            $lateMinutes,
            $employeeData
        );

        // Add attendance details to summary
        $summary['attendance'] = [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'regular_hours' => $regularHours,
            'approved_overtime_hours' => $overtimeHours,
            'undertime_hours' => $undertimeHours,
            'late_instances' => $lateData['late_count'] ?? 0,
            'days_present' => $attendanceData['days_present'] ?? 0,
            'days_absent' => $attendanceData['days_absent'] ?? 0,
            'half_days' => $attendanceData['half_days'] ?? 0
        ];

        return $summary;
    }

    /**
     * Calculate expected regular working hours based on pay period
     */
    private function getExpectedRegularHours($payPeriod, $startDate, $endDate)
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        // Count business days (Monday-Friday)
        $businessDays = 0;
        $current = clone $start;
        
        while ($current <= $end) {
            $dayOfWeek = $current->format('N'); // 1-7 (Monday-Sunday)
            if ($dayOfWeek < 6) { // Monday to Friday
                $businessDays++;
            }
            $current->modify('+1 day');
        }

        // Standard 8 hours per business day
        return $businessDays * 8;
    }

    /**
     * Save payroll record to database
     */
    public function savePayrollRecord($userId, $startDate, $endDate, $summary, $status = 'Pending', $processedBy = null, $processedDate = null)
    {
        $query = "INSERT INTO payroll (
            user_id, payroll_period, days_worked, daily_rate, gross_salary,
            sss_employee_share, sss_employer_share,
            philhealth_employee_share, philhealth_employer_share,
            pagibig_employee_share, pagibig_employer_share,
            overtime_hours, overtime_pay,
            undertime_hours, undertime_deduction,
            late_minutes, late_deduction,
            total_mandatory_deductions,
            taxable_income, withholding_tax,
            adjusted_gross_salary, total_employer_contributions,
            deductions, net_salary, status, processed_by, processed_date, date_created
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?,
            ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?, NOW()
        )";

        $stmt = $this->mysql->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->mysql->error);
        }

        $payrollPeriod = $startDate . ' to ' . $endDate;
        $daysWorked = $summary['attendance']['days_present'] ?? 0;
        $dailyRate = $daysWorked > 0 ? ($summary['gross_salary'] / $daysWorked) : 0;
        $processedDate = $processedDate ?? ($status === 'Released' ? date('Y-m-d H:i:s') : null);

        // Extract values to variables for bind_param
        $overtimeHours = $summary['overtime_hours'] ?? 0;
        $undertimeHours = $summary['undertime_hours'] ?? 0;

        $bindTypes = 'isid' . str_repeat('d', 20) . 'sis';
        $stmt->bind_param(
            $bindTypes,
            $userId,
            $payrollPeriod,
            $daysWorked,
            $dailyRate,
            $summary['gross_salary'],
            $summary['sss']['employee_share'],
            $summary['sss']['employer_share'],
            $summary['philhealth']['employee_share'],
            $summary['philhealth']['employer_share'],
            $summary['pagibig']['employee_share'],
            $summary['pagibig']['employer_share'],
            $overtimeHours,
            $summary['overtime_pay'],
            $undertimeHours,
            $summary['undertime_deduction'],
            $summary['late_minutes'],
            $summary['late_deduction'],
            $summary['total_mandatory_deductions'],
            $summary['taxable_income'],
            $summary['withholding_tax'],
            $summary['adjusted_gross_salary'],
            $summary['employer_contributions'],
            $summary['total_deductions'],
            $summary['net_take_home_pay'],
            $status,
            $processedBy,
            $processedDate
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $payrollId = $this->mysql->insert_id;
        $stmt->close();

        return $payrollId;
    }

    /**
     * Get payroll summary for display
     */
    public function getPayrollSummary($payrollId)
    {
        $query = "SELECT p.*, u.first_name, u.last_name, u.email
                  FROM payroll p
                  JOIN user u ON p.user_id = u.user_id
                  WHERE p.payroll_id = ?";

        $stmt = $this->mysql->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->mysql->error);
        }

        $stmt->bind_param('i', $payrollId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data;
    }

    /**
     * Get all payroll records for an employee
     */
    public function getEmployeePayrollHistory($userId, $limit = 12)
    {
        $query = "SELECT p.*, u.first_name, u.last_name
                  FROM payroll p
                  JOIN user u ON p.user_id = u.user_id
                  WHERE p.user_id = ?
                  ORDER BY p.date_created DESC
                  LIMIT ?";

        $stmt = $this->mysql->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->mysql->error);
        }

        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = [];

        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }

        $stmt->close();
        return $records;
    }
}
?>
