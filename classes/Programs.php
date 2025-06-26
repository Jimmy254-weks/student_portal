<?php
class Programs
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Get all programs
    public function getAllPrograms()
    {
        $this->db->query('SELECT * FROM programs ORDER BY name');
        return $this->db->resultSet();
    }

    // Get program by ID
    public function getProgramById($id)
    {
        $this->db->query('SELECT * FROM programs WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Get courses for a program
    public function getProgramCourses($program_id)
    {
        $this->db->query('SELECT * FROM courses WHERE program_id = :program_id ORDER BY code');
        $this->db->bind(':program_id', $program_id);
        return $this->db->resultSet();
    }

    // Add new program
    public function addProgram($data)
    {
        $this->db->query('INSERT INTO programs (name, code, duration, description, degree_type, department) 
                         VALUES (:name, :code, :duration, :description, :degree_type, :department)');

        $this->db->bind(':name', $data['name']);
        $this->db->bind(':code', $data['code']);
        $this->db->bind(':duration', $data['duration']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':degree_type', $data['degree_type']);
        $this->db->bind(':department', $data['department']);

        return $this->db->execute();
    }

    // Update program
    public function updateProgram($data)
    {
        $this->db->query('UPDATE programs SET 
                         name = :name,
                         code = :code,
                         duration = :duration,
                         description = :description,
                         degree_type = :degree_type,
                         department = :department
                         WHERE id = :id');

        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':code', $data['code']);
        $this->db->bind(':duration', $data['duration']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':degree_type', $data['degree_type']);
        $this->db->bind(':department', $data['department']);

        return $this->db->execute();
    }

    // Delete program
    public function deleteProgram($id)
    {
        $this->db->query('DELETE FROM programs WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}