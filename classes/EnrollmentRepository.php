<?php
// classes/EnrollmentRepository.php
// Data-access class that records students, enrollments and slot updates
// inside a single database transaction.
class EnrollmentRepository {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Record a NEW student AND enroll them into a class inside ONE transaction.
     * 1. beginTransaction()
     * 2. Check that the class still has available slots
     * 3. INSERT INTO students (full_name, email, phone)
     * 4. $student_id = lastInsertId()
     * 5. INSERT INTO enrollments (student_id, class_id)
     * 6. UPDATE classes SET slots = slots - 1
     * 7. commit(); otherwise rollBack()
     * 
     * @return array|false Returns ['student_id' => int, 'enrollment_id' => int] on success, false on failure (e.g. no slots).
     */
    public function recordStudent($full_name, $email, $phone, $class_id) {
        try {
            $this->db->beginTransaction();

            // Check that the class exists and still has available slots
            $stmtClass = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :class_id FOR UPDATE");
            $stmtClass->execute([':class_id' => $class_id]);
            $class = $stmtClass->fetch();

            if (!$class || (int)$class['slots'] <= 0) {
                // No slots available -> rollback and abort
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return false;
            }

            // Insert new student record
            $stmtStudent = $this->db->prepare("INSERT INTO students (full_name, email, phone) VALUES (:name, :email, :phone)");
            $stmtStudent->execute([
                ':name' => $full_name,
                ':email' => $email,
                ':phone' => $phone
            ]);
            $student_id = (int)$this->db->lastInsertId();

            // Insert enrollment record
            $stmtEnroll = $this->db->prepare("INSERT INTO enrollments (student_id, class_id, status) VALUES (:student_id, :class_id, 'active')");
            $stmtEnroll->execute([
                ':student_id' => $student_id,
                ':class_id' => $class_id
            ]);
            $enrollment_id = (int)$this->db->lastInsertId();

            // Decrement remaining slots
            $stmtSlot = $this->db->prepare("UPDATE classes SET slots = slots - 1 WHERE class_id = :class_id");
            $stmtSlot->execute([':class_id' => $class_id]);

            $this->db->commit();
            return [
                'student_id' => $student_id,
                'enrollment_id' => $enrollment_id
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("EnrollmentRepository::recordStudent error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enroll an EXISTING student into a class inside ONE transaction.
     * 
     * @return int|string|false Returns enrollment_id on success, 'already_enrolled' if duplicate, false if no slots/failure.
     */
    public function enroll($student_id, $class_id) {
        try {
            $this->db->beginTransaction();

            // Check student existence
            $stmtStudent = $this->db->prepare("SELECT student_id FROM students WHERE student_id = :student_id");
            $stmtStudent->execute([':student_id' => $student_id]);
            if (!$stmtStudent->fetch()) {
                $this->db->rollBack();
                return false;
            }

            // Prevent duplicate active enrollments in the same class
            $stmtCheck = $this->db->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = :student_id AND class_id = :class_id AND status = 'active'");
            $stmtCheck->execute([
                ':student_id' => $student_id,
                ':class_id' => $class_id
            ]);
            if ($stmtCheck->fetch()) {
                $this->db->rollBack();
                return 'already_enrolled';
            }

            // Check that the class has available slots
            $stmtClass = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :class_id FOR UPDATE");
            $stmtClass->execute([':class_id' => $class_id]);
            $class = $stmtClass->fetch();

            if (!$class || (int)$class['slots'] <= 0) {
                $this->db->rollBack();
                return false;
            }

            // Insert enrollment
            $stmtEnroll = $this->db->prepare("INSERT INTO enrollments (student_id, class_id, status) VALUES (:student_id, :class_id, 'active')");
            $stmtEnroll->execute([
                ':student_id' => $student_id,
                ':class_id' => $class_id
            ]);
            $enrollment_id = (int)$this->db->lastInsertId();

            // Decrement slots
            $stmtSlot = $this->db->prepare("UPDATE classes SET slots = slots - 1 WHERE class_id = :class_id");
            $stmtSlot->execute([':class_id' => $class_id]);

            $this->db->commit();
            return $enrollment_id;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("EnrollmentRepository::enroll error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel an enrollment and restore available class slots in ONE transaction.
     * 
     * @return bool True on success, false on failure
     */
    public function cancel($enrollment_id) {
        try {
            $this->db->beginTransaction();

            // Locate active enrollment
            $stmt = $this->db->prepare("SELECT class_id, status FROM enrollments WHERE enrollment_id = :id FOR UPDATE");
            $stmt->execute([':id' => $enrollment_id]);
            $enrollment = $stmt->fetch();

            if (!$enrollment || $enrollment['status'] === 'cancelled') {
                $this->db->rollBack();
                return false;
            }

            $class_id = (int)$enrollment['class_id'];

            // Update enrollment status to 'cancelled'
            $stmtUpdate = $this->db->prepare("UPDATE enrollments SET status = 'cancelled' WHERE enrollment_id = :id");
            $stmtUpdate->execute([':id' => $enrollment_id]);

            // Restore the available slot
            $stmtRestore = $this->db->prepare("UPDATE classes SET slots = slots + 1 WHERE class_id = :class_id");
            $stmtRestore->execute([':class_id' => $class_id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("EnrollmentRepository::cancel error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch all enrollments joined with students, classes, and courses.
     * Supports optional status filtering and search query.
     */
    public function allWithDetails($status = null, $search = null) {
        $sql = "SELECT e.enrollment_id, e.enrollment_date, e.status,
                       s.student_id, s.full_name, s.email, s.phone,
                       c.class_id, c.class_code, c.schedule, c.instructor, c.slots,
                       co.course_id, co.course_code, co.course_name
                FROM enrollments e
                JOIN students s ON e.student_id = s.student_id
                JOIN classes c ON e.class_id = c.class_id
                JOIN courses co ON c.course_id = co.course_id
                WHERE 1=1";
        $params = [];

        if (!empty($status) && in_array($status, ['active', 'cancelled'])) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (s.full_name LIKE :search OR co.course_name LIKE :search OR c.class_code LIKE :search OR s.email LIKE :search)";
            $params[':search'] = "%" . $search . "%";
        }

        $sql .= " ORDER BY e.enrollment_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
