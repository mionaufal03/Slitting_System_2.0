<?php
namespace App\Models;

class MotherCoilModel {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    public function productFromCoil(string $coil_no): string {
        $coil_no = strtoupper(trim($coil_no));
        if ($coil_no === '') return '';

        // 2. Extract characters and numbers only (Regex logic)
        preg_match('/^[A-Z0-9]+/', $coil_no, $m);
        $token = $m[0] ?? '';
        if ($token === '') return '';

        // 3. Loop logic to find matching prefix in the map table
        for ($len = strlen($token); $len >= 1; $len--) {
            $code = substr($token, 0, $len);

            $stmt = $this->db->prepare("SELECT product FROM coil_product_map WHERE coil_code = ? LIMIT 1");
            $stmt->execute([$code]);
            $row = $stmt->fetch();

            if ($row) {
                return $row['product'] ?? '';
            }
        }

        return '';
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM mother_coil ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM mother_coil WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function add($data) {
        $stmt = $this->db->prepare("INSERT INTO mother_coil (product, grade, lot_no, coil_no, width, length, date_created) VALUES (?,?,?,?,?,?,NOW())");
        return $stmt->execute([$data['product'], $data['grade'], $data['lot_no'], $data['coil_no'], $data['width'], $data['length']]);
    }

    public function update($data) {
        $stmt = $this->db->prepare("UPDATE mother_coil SET product=?, grade=?, lot_no=?, coil_no=?, width=?, length=? WHERE id=?");
        return $stmt->execute([$data['product'], $data['grade'], $data['lot_no'], $data['coil_no'], $data['width'], $data['length'], $data['id']]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM mother_coil WHERE id = ?");
        return $stmt->execute([$id]);
    }
}