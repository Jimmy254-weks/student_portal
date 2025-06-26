<?php
class Student
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Create student profile
    public function createProfile($data)
    {
        $this->db->query('INSERT INTO students (user_id, admission_no, first_name, last_name, gender, date_of_birth, phone, address, county) 
                         VALUES (:user_id, :admission_no, :first_name, :last_name, :gender, :date_of_birth, :phone, :address, :county)');

        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':admission_no', $data['admission_no']);
        $this->db->bind(':first_name', $data['first_name']);
        $this->db->bind(':last_name', $data['last_name']);
        $this->db->bind(':gender', $data['gender']);
        $this->db->bind(':date_of_birth', $data['date_of_birth']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':county', $data['county']);

        return $this->db->execute();
    }

    // Get student by user ID
    public function getStudentByUserId($user_id)
    {
        $this->db->query('SELECT * FROM students WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        return $this->db->single();
    }

    // Get student fees with optional filtering
    public function getStudentFees($student_id, $semester = null, $academic_year = null)
    {
        $sql = 'SELECT * FROM fees WHERE student_id = :student_id';
        $params = [':student_id' => $student_id];

        if ($semester) {
            $sql .= ' AND semester = :semester';
            $params[':semester'] = $semester;
        }

        if ($academic_year) {
            $sql .= ' AND academic_year = :academic_year';
            $params[':academic_year'] = $academic_year;
        }

        $sql .= ' ORDER BY due_date ASC';

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        return $this->db->resultSet() ?: [];
    }

    /**
     * Get comprehensive fee statements (both invoices and payments)
     * with properly formatted reference numbers and complete data
     */
    public function getFeeStatements($student_id)
    {
        $this->db->query('SELECT 
                            f.due_date as transaction_date,
                            CONCAT("INV-", LPAD(f.id, 5, "0")) as reference_number,
                            f.description,
                            f.amount as debit_amount,
                            0 as credit_amount,
                            "invoice" as transaction_type,
                            f.semester,
                            f.academic_year
                          FROM fees f
                          WHERE f.student_id = :student_id
                          
                          UNION ALL
                          
                          SELECT 
                            p.payment_date as transaction_date,
                            CASE 
                                WHEN p.receipt_number IS NOT NULL AND p.receipt_number != "" 
                                THEN CONCAT("RCPT-", p.receipt_number)
                                ELSE CONCAT("PYMT-", LPAD(p.id, 5, "0"))
                            END as reference_number,
                            CONCAT("Payment for ", f.description, " (", p.payment_method, ")") as description,
                            0 as debit_amount,
                            p.amount as credit_amount,
                            "payment" as transaction_type,
                            f.semester,
                            f.academic_year
                          FROM payments p
                          JOIN fees f ON p.fee_id = f.id
                          WHERE f.student_id = :student_id
                          
                          ORDER BY transaction_date DESC, transaction_type');

        $this->db->bind(':student_id', $student_id);
        $results = $this->db->resultSet();

        // Ensure all objects have the expected properties
        return array_map(function ($item) {
            return (object) [
                'transaction_date' => $item->transaction_date ?? null,
                'reference_number' => $item->reference_number ?? 'N/A',
                'description' => $item->description ?? '',
                'debit_amount' => $item->debit_amount ?? 0,
                'credit_amount' => $item->credit_amount ?? 0,
                'transaction_type' => $item->transaction_type ?? 'unknown',
                'semester' => $item->semester ?? null,
                'academic_year' => $item->academic_year ?? null
            ];
        }, $results ?: []);
    }

    // Calculate total fees with optional filtering
    public function calculateTotalFees($student_id, $semester = null, $academic_year = null)
    {
        $sql = 'SELECT 
                COALESCE(SUM(amount), 0) as total_billed, 
                COALESCE(SUM(paid_amount), 0) as total_paid 
                FROM fees WHERE student_id = :student_id';

        $params = [':student_id' => $student_id];

        if ($semester) {
            $sql .= ' AND semester = :semester';
            $params[':semester'] = $semester;
        }

        if ($academic_year) {
            $sql .= ' AND academic_year = :academic_year';
            $params[':academic_year'] = $academic_year;
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $result = $this->db->single();
        return $result ?: (object) ['total_billed' => 0, 'total_paid' => 0];
    }

    /**
     * Get complete student payment history with all required fields
     */
    public function getStudentPayments($student_id, $limit = null)
    {
        $sql = 'SELECT 
                    p.id,
                    p.amount,
                    p.payment_date,
                    p.payment_method,
                    COALESCE(p.reference_number, "N/A") as reference_number,
                    COALESCE(p.receipt_number, "N/A") as receipt_number,
                    p.confirmed_by,
                    p.notes,
                    f.description as fee_description,
                    f.id as fee_id
                FROM payments p
                JOIN fees f ON p.fee_id = f.id
                WHERE f.student_id = :student_id 
                ORDER BY p.payment_date DESC';

        if ($limit) {
            $sql .= ' LIMIT :limit';
        }

        $this->db->query($sql);
        $this->db->bind(':student_id', $student_id);

        if ($limit) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        }

        $results = $this->db->resultSet();

        // Ensure all payment objects have consistent properties
        return array_map(function ($payment) {
            return (object) [
                'id' => $payment->id ?? null,
                'amount' => $payment->amount ?? 0,
                'payment_date' => $payment->payment_date ?? null,
                'payment_method' => $payment->payment_method ?? 'Unknown',
                'reference_number' => $payment->reference_number ?? 'N/A',
                'receipt_number' => $payment->receipt_number ?? 'N/A',
                'confirmed_by' => $payment->confirmed_by ?? null,
                'notes' => $payment->notes ?? '',
                'fee_description' => $payment->fee_description ?? '',
                'fee_id' => $payment->fee_id ?? null
            ];
        }, $results ?: []);
    }

    // Get student courses
    public function getStudentCourses($student_id)
    {
        $this->db->query('SELECT sc.*, c.code, c.name, c.description as course_description
                         FROM student_courses sc 
                         JOIN courses c ON sc.course_id = c.id 
                         WHERE sc.student_id = :student_id');
        $this->db->bind(':student_id', $student_id);
        $results = $this->db->resultSet();

        return array_map(function ($course) {
            return (object) [
                'id' => $course->id ?? null,
                'student_id' => $course->student_id ?? null,
                'course_id' => $course->course_id ?? null,
                'enrollment_date' => $course->enrollment_date ?? null,
                'completion_date' => $course->completion_date ?? null,
                'status' => $course->status ?? 'active',
                'code' => $course->code ?? '',
                'name' => $course->name ?? '',
                'course_description' => $course->course_description ?? ''
            ];
        }, $results ?: []);
    }

    // Get student's primary program
    public function getStudentProgram($student_id)
    {
        $this->db->query('SELECT p.* FROM programs p
                         JOIN courses c ON p.id = c.program_id
                         JOIN student_courses sc ON c.id = sc.course_id
                         WHERE sc.student_id = :student_id
                         GROUP BY p.id
                         LIMIT 1');
        $this->db->bind(':student_id', $student_id);
        $result = $this->db->single();

        return $result ?: (object) [
            'id' => null,
            'name' => 'Not Assigned',
            'code' => 'N/A',
            'duration' => 0,
            'description' => ''
        ];
    }

    // Update student profile
    public function updateProfile($data)
    {
        $this->db->query('UPDATE students SET 
                         first_name = :first_name,
                         last_name = :last_name,
                         gender = :gender,
                         date_of_birth = :date_of_birth,
                         phone = :phone,
                         address = :address,
                         county = :county
                         WHERE user_id = :user_id');

        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':first_name', $data['first_name']);
        $this->db->bind(':last_name', $data['last_name']);
        $this->db->bind(':gender', $data['gender']);
        $this->db->bind(':date_of_birth', $data['date_of_birth']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':county', $data['county']);

        return $this->db->execute();
    }

    // Get current semester fees
    public function getCurrentSemesterFees($student_id)
    {
        $current_month = date('n');
        $semester = ($current_month >= 1 && $current_month <= 6) ? 'First Semester' : 'Second Semester';
        $academic_year = date('Y') . '/' . (date('Y') + 1);

        return $this->getStudentFees($student_id, $semester, $academic_year);
    }

    // Get comprehensive fee balance summary
    public function getFeeBalanceSummary($student_id)
    {
        $this->db->query('SELECT 
                            COALESCE(SUM(f.amount), 0) as total_billed,
                            COALESCE(SUM(p.amount), 0) as total_paid,
                            COALESCE(SUM(f.amount), 0) - COALESCE(SUM(p.amount), 0) as balance,
                            COUNT(DISTINCT f.id) as fee_count,
                            COUNT(DISTINCT p.id) as payment_count
                          FROM fees f
                          LEFT JOIN payments p ON f.id = p.fee_id
                          WHERE f.student_id = :student_id');

        $this->db->bind(':student_id', $student_id);
        $result = $this->db->single();

        return $result ?: (object) [
            'total_billed' => 0,
            'total_paid' => 0,
            'balance' => 0,
            'fee_count' => 0,
            'payment_count' => 0
        ];
    }

    /**
     * Generate a standardized receipt number
     */
    private function generateReceiptNumber($payment_id, $payment_date)
    {
        $prefix = 'RCT';
        $year = date('Y', strtotime($payment_date));
        $month = date('m', strtotime($payment_date));
        $sequence = str_pad($payment_id, 5, '0', STR_PAD_LEFT);

        return $prefix . '-' . $year . $month . '-' . $sequence;
    }

    /**
     * Record a new payment with automatic receipt number generation
     */
    public function recordPayment($fee_id, $amount, $payment_date, $payment_method, $reference_number = null, $receipt_number = null, $notes = null)
    {
        // First insert the payment to get the ID
        $this->db->query('INSERT INTO payments 
                         (fee_id, amount, payment_date, payment_method, reference_number, receipt_number, notes)
                         VALUES (:fee_id, :amount, :payment_date, :payment_method, :reference_number, :receipt_number, :notes)');

        $this->db->bind(':fee_id', $fee_id);
        $this->db->bind(':amount', $amount);
        $this->db->bind(':payment_date', $payment_date);
        $this->db->bind(':payment_method', $payment_method);
        $this->db->bind(':reference_number', $reference_number);
        $this->db->bind(':receipt_number', $receipt_number);
        $this->db->bind(':notes', $notes);

        if ($this->db->execute()) {
            // Get the inserted payment ID
            $payment_id = $this->db->lastInsertId();

            // Generate receipt number if none was provided
            if (empty($receipt_number)) {
                $receipt_number = $this->generateReceiptNumber($payment_id, $payment_date);

                // Update the payment with the generated receipt number
                $this->db->query('UPDATE payments SET receipt_number = :receipt_number WHERE id = :payment_id');
                $this->db->bind(':receipt_number', $receipt_number);
                $this->db->bind(':payment_id', $payment_id);
                $this->db->execute();
            }

            // Update the fee's paid amount and status
            $this->updateFeeStatus($fee_id);
            return true;
        }

        return false;
    }

    /**
     * Update fee status based on payments
     */
    private function updateFeeStatus($fee_id)
    {
        // Get current payment total
        $this->db->query('SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE fee_id = :fee_id');
        $this->db->bind(':fee_id', $fee_id);
        $result = $this->db->single();
        $total_paid = $result->total_paid ?? 0;

        // Get fee amount
        $this->db->query('SELECT amount FROM fees WHERE id = :fee_id');
        $this->db->bind(':fee_id', $fee_id);
        $fee = $this->db->single();
        $fee_amount = $fee->amount ?? 0;

        // Determine status
        $status = 'pending';
        if ($total_paid >= $fee_amount) {
            $status = 'paid';
        } elseif ($total_paid > 0) {
            $status = 'partial';
        }

        // Update fee record
        $this->db->query('UPDATE fees SET paid_amount = :paid_amount, status = :status WHERE id = :fee_id');
        $this->db->bind(':paid_amount', $total_paid);
        $this->db->bind(':status', $status);
        $this->db->bind(':fee_id', $fee_id);

        return $this->db->execute();
    }

    /**
     * Get the next payment ID (for receipt number generation)
     */
    public function getNextPaymentId()
    {
        $this->db->query("SHOW TABLE STATUS LIKE 'payments'");
        $result = $this->db->single();
        return $result->Auto_increment ?? 1;
    }
}