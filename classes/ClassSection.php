<?php
// classes/ClassSection.php
class ClassSection {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function allWithCourse() {
        // JOIN classes with courses so each row shows the course name
        $sql = "SELECT c.*, co.course_code, co.course_name 
                FROM classes c 
                JOIN courses co ON c.course_id = co.course_id 
                ORDER BY c.class_id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT c.*, co.course_code, co.course_name 
                                    FROM classes c 
                                    JOIN courses co ON c.course_id = co.course_id 
                                    WHERE c.class_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($course_id, $code, $schedule, $instructor, $slots) {
        // Insert a class section
        $stmt = $this->db->prepare("INSERT INTO classes (course_id, class_code, schedule, instructor, slots) 
                                    VALUES (:course_id, :code, :schedule, :instructor, :slots)");
        return $stmt->execute([
            ':course_id' => $course_id,
            ':code' => $code,
            ':schedule' => $schedule,
            ':instructor' => $instructor,
            ':slots' => $slots
        ]);
    }

    public function update($id, $course_id, $code, $schedule, $instructor, $slots) {
        $stmt = $this->db->prepare("UPDATE classes 
                                    SET course_id = :course_id, class_code = :code, schedule = :schedule, instructor = :instructor, slots = :slots 
                                    WHERE class_id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':course_id' => $course_id,
            ':code' => $code,
            ':schedule' => $schedule,
            ':instructor' => $instructor,
            ':slots' => $slots
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM classes WHERE class_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getSlots($class_id) {
        // Return the remaining slots for a class
        $stmt = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :id");
        $stmt->execute([':id' => $class_id]);
        $row = $stmt->fetch();
        return $row !== false ? (int)$row['slots'] : 0;
    }

    public function availableSlots($class_id) {
        return $this->getSlots($class_id);
    }

    public function decrementSlot($class_id) {
        $stmt = $this->db->prepare("UPDATE classes SET slots = slots - 1 WHERE class_id = :id AND slots > 0");
        return $stmt->execute([':id' => $class_id]);
    }

    public function restoreSlot($class_id) {
        $stmt = $this->db->prepare("UPDATE classes SET slots = slots + 1 WHERE class_id = :id");
        return $stmt->execute([':id' => $class_id]);
    }
}
