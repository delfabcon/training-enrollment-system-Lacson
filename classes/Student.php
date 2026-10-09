<?php
// classes/Student.php
class Student {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function find($id) {
        // Return one student by id (prepared statement)
        $stmt = $this->db->prepare("SELECT * FROM students WHERE student_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function all() {
        // Return all students ordered by full_name
        $stmt = $this->db->query("SELECT * FROM students ORDER BY full_name ASC");
        return $stmt->fetchAll();
    }

    public function record($fullName, $email, $phone) {
        $stmt = $this->db->prepare("INSERT INTO students (full_name, email, phone) VALUES (:name, :email, :phone)");
        $stmt->execute([
            ':name' => $fullName,
            ':email' => $email,
            ':phone' => $phone
        ]);
        return $this->db->lastInsertId();
    }
}
