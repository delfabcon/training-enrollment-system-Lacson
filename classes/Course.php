<?php
// classes/Course.php
class Course {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function all() {
        // Return all courses ordered by course_name
        $stmt = $this->db->query("SELECT * FROM courses ORDER BY course_name ASC");
        return $stmt->fetchAll();
    }

    public function find($id) {
        // Return one course by id (prepared statement)
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE course_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function findByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE course_code = :code");
        $stmt->execute([':code' => $code]);
        return $stmt->fetch();
    }

    public function create($code, $name, $description) {
        // Insert a course
        $stmt = $this->db->prepare("INSERT INTO courses (course_code, course_name, description) VALUES (:code, :name, :description)");
        return $stmt->execute([
            ':code' => $code,
            ':name' => $name,
            ':description' => $description
        ]);
    }

    public function update($id, $code, $name, $description) {
        // Update a course
        $stmt = $this->db->prepare("UPDATE courses SET course_code = :code, course_name = :name, description = :description WHERE course_id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':code' => $code,
            ':name' => $name,
            ':description' => $description
        ]);
    }

    public function delete($id) {
        // Delete a course
        $stmt = $this->db->prepare("DELETE FROM courses WHERE course_id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
