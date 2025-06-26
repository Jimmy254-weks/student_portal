<?php
class University
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getUniversityDetails()
    {
        $this->db->query('SELECT * FROM university LIMIT 1');
        $result = $this->db->single();

        if (!$result) {
            // Return default values if no university record exists
            return (object) [
                'name' => 'University of Nairobi',
                'address' => 'University Way',
                'city' => 'Nairobi',
                'country' => 'Kenya',
                'phone' => '+254 20 491 0000',
                'email' => 'info@uonbi.ac.ke',
                'paybill_number' => '123456',
                'bank_name' => 'Kenya Commercial Bank',
                'bank_account_name' => 'UoN Fees Account',
                'bank_account_number' => '1234567890',
                'logo_path' => 'assets/images/uon-logo.png'
            ];
        }
        return $result;
    }

    public function updateUniversityDetails($data)
    {
        $this->db->query('UPDATE university SET 
                         name = :name,
                         address = :address,
                         city = :city,
                         country = :country,
                         phone = :phone,
                         email = :email,
                         paybill_number = :paybill_number,
                         bank_name = :bank_name,
                         bank_account_name = :bank_account_name,
                         bank_account_number = :bank_account_number,
                         logo_path = :logo_path
                         WHERE id = :id');

        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':city', $data['city']);
        $this->db->bind(':country', $data['country']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':paybill_number', $data['paybill_number']);
        $this->db->bind(':bank_name', $data['bank_name']);
        $this->db->bind(':bank_account_name', $data['bank_account_name']);
        $this->db->bind(':bank_account_number', $data['bank_account_number']);
        $this->db->bind(':logo_path', $data['logo_path']);

        return $this->db->execute();
    }
}