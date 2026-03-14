<?php
namespace App\Models;

class SlittingModel
{
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function delete($id) {
        // Delete related finish_product first
        $stmt1 = $this->pdo->prepare("DELETE FROM finish_product WHERE slit_id = ?");
        $stmt1->execute([$id]);

        // Then delete slitting_product
        $stmt2 = $this->pdo->prepare("DELETE FROM slitting_product WHERE id = ?");
        return $stmt2->execute([$id]);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM slitting_product WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE slitting_product 
            SET roll_no=?, width=?, length=?, length_type=? 
            WHERE id=?");
        return $stmt->execute([$data['roll_no'], $data['width'], $data['length'], $data['length_type'], $id]);
    }

    public function create($data) {
        $this->pdo->beginTransaction();

        try {
            $source_type = $data['source_type'];
            $product = $data['product'];
            $lot_no = $data['lot_no'];
            $coil_no = $data['coil_no'];
            $total = $data['total'];
            $cut_type = $data['cut_type'];
            $roll_nos = $data['roll_no'];
            $widths = $data['width'];
            $lengths = $data['length'];
            $cut_letters = $data['cut_letter'] ?? [];
            $slit_quantity = $data['slit_quantity'] ?? null;
            $stock = $data['stock'] ?? null;

            $mother_id = 0;
            $stock_id = 0;
            $source_data = null;

            if ($source_type === 'mother') {
                $mother_id = intval($data['mother_id']);
                $stmt = $this->pdo->prepare("SELECT * FROM mother_coil WHERE id = ?");
                $stmt->execute([$mother_id]);
                $source_data = $stmt->fetch();
            } else if ($source_type === 'stock') {
                $stock_id = intval($data['stock_id']);
                $stmt = $this->pdo->prepare("SELECT * FROM raw_material_log WHERE id = ?");
                $stmt->execute([$stock_id]);
                $source_data = $stmt->fetch();
            }

            for ($i = 0; $i < $total; $i++) {
                $roll_no_val = trim($roll_nos[$i]);
                $width_val = trim($widths[$i]);
                $length_val = trim($lengths[$i]);
                
                $final_lot_no = $lot_no;    
                if (isset($cut_letters[$i]) && $cut_letters[$i] !== '') {
                    $final_lot_no = $lot_no . $cut_letters[$i];
                }

                $stmt = $this->pdo->prepare("INSERT INTO slitting_product 
                    (mother_id, product, lot_no, coil_no, roll_no, width, length, cut_type, slit_quantity, stock, status, is_completed, stock_counted) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'IN', 0, 0)");
                
                $stmt->execute([
                    $mother_id,
                    $product,
                    $final_lot_no,
                    $coil_no,
                    $roll_no_val,
                    $width_val,
                    $length_val,
                    $cut_type,
                    $slit_quantity,
                    $stock
                ]);
            }

            if ($source_data) {
                $action_value = $cut_type;
                
                if ($source_type === 'mother') {
                    $stmt = $this->pdo->prepare("UPDATE raw_material_log 
                                           SET status='OUT', date_out=NOW(), action=? 
                                           WHERE product=? AND lot_no=? AND coil_no=? AND status='IN' LIMIT 1");
                    $stmt->execute([
                        $action_value,
                        $source_data['product'], 
                        $source_data['lot_no'], 
                        $source_data['coil_no']
                    ]);
                } else if ($source_type === 'stock') {
                    $stmt = $this->pdo->prepare("UPDATE raw_material_log SET status='OUT', date_out=NOW(), action=? WHERE id=?");
                    $stmt->execute([$action_value, $stock_id]);
                }
                
                if ($cut_type === 'cut_into_2' && !empty($stock) && $stock > 0) {
                    $remark = ($source_type === 'stock') ? 'Stock leftover from stock after cut' : 'Stock leftover from slitting';
                    
                    $stmt = $this->pdo->prepare("INSERT INTO raw_material_log 
                                           (product, lot_no, coil_no, length, width, status, date_in, action, remark) 
                                           VALUES (?, ?, ?, ?, ?, 'IN', NOW(), 'cut_into_2', ?)");
                    
                    $width_value = isset($source_data['width']) ? $source_data['width'] : 0;
                    
                    $stmt->execute([
                        $source_data['product'],
                        $source_data['lot_no'],
                        $source_data['coil_no'],
                        $stock,
                        $width_value,
                        $remark
                    ]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            // You can log the error message ($e->getMessage()) for debugging
            return false;
        }
    }
}