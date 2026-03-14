<?php
class MotherModel {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM mother_coil WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function productFromCoil(string $coil_no): string {
        $coil_no = strtoupper(trim($coil_no));
        if ($coil_no === '') return '';

        preg_match('/^[A-Z0-9]+/', $coil_no, $m);
        $token = $m[0] ?? '';
        if ($token === '') return '';

        for ($len = strlen($token); $len >= 1; $len--) {
            $code = substr($token, 0, $len);

            $stmt = $this->db->prepare("SELECT product FROM coil_product_map WHERE coil_code = ? LIMIT 1");
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($row = $res->fetch_assoc()) {
                return $row['product'] ?? '';
            }
        }

        return '';
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM mother_coil ORDER BY id ASC");
    }

    public function add($data) {
        $stmt = $this->db->prepare("INSERT INTO mother_coil (product, grade, lot_no, coil_no, width, length, date_created) VALUES (?,?,?,?,?,?,NOW())");
        $stmt->bind_param("ssssss", $data['product'], $data['grade'], $data['lot_no'], $data['coil_no'], $data['width'], $data['length']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        // Note: 'grade' is missing from the original edit_mother.php form, so it's not included here.
        // You may want to add it to your form and uncomment the line below.
        $stmt = $this->db->prepare("UPDATE mother_coil SET product=?, lot_no=?, coil_no=?, width=?, length=? WHERE id=?");
        $stmt->bind_param("sssssi", $data['product'], $data['lot_no'], $data['coil_no'], $data['width'], $data['length'], $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM mother_coil WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
