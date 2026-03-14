<?php
namespace App\Controllers;

use App\Models\MotherCoilModel;
use App\Models\RawMaterialModel;
use App\Models\SlittingModel;

class SlittingController
{
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function add()
    {
        session_start();

        if (!isset($_SESSION['role'])) {
            header("Location: /login");
            exit;
        }

        // ONLY slitting can access
        if ($_SESSION['role'] !== 'slitting') {
            die("Access denied");
        }

        $from_stock = isset($_GET['stock_id']);
        $source_data = null;
        $source_type = '';

        if ($from_stock) {
            // From Stock After Cut
            $stock_id = intval($_GET['stock_id']);
            $rawMaterialModel = new RawMaterialModel($this->pdo);
            $source_data = $rawMaterialModel->getByIdAndStatus($stock_id, 'IN', 'cut_into_2');
            $source_type = 'stock';

            if (!$source_data) {
                die("Stock not found or already used.");
            }
        } else {
            // From Mother Coil
            $mother_id = intval($_GET['mother_id']);
            $motherModel = new MotherCoilModel($this->pdo);
            $source_data = $motherModel->getById($mother_id);
            $source_type = 'mother';

            if (!$source_data) {
                die("Mother coil not found.");
            }
        }

        // Pass data to view
        require_once __DIR__ . '/../views/add_slitting.php';
    }

    public function delete()
    {
        if (!isset($_GET['id'])) {
            die("Missing ID");
        }

        $id = intval($_GET['id']);

        $slittingModel = new SlittingModel($this->pdo);
        $result = $slittingModel->delete($id);

        if ($result) {
            header("Location: index.php?controller=slitting&action=list");
            exit;
        } else {
            die("Delete failed");
        }
    }

    public function edit()
    {
        $id = intval($_GET['id'] ?? 0);

        if ($id == 0) {
            die("Invalid product ID");
        }

        $slittingModel = new SlittingModel($this->pdo);
        $product = $slittingModel->getById($id);

        if (!$product) {
            die("Product not found. ID: " . $id);
        }

        // Pass to view
        require_once __DIR__ . '/../views/edit_slitting.php';
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Invalid request");
        }

        $id = intval($_POST['id']);
        $data = [
            'roll_no' => trim($_POST['roll_no']),
            'width' => trim($_POST['width']),
            'length' => trim($_POST['length']),
            'length_type' => $_POST['length_type'],
        ];

        $slittingModel = new SlittingModel($this->pdo);
        $result = $slittingModel->update($id, $data);

        if ($result) {
            header("Location: index.php?controller=finish&action=list"); // Assuming finish product list
            exit;
        } else {
            die("Update failed");
        }
    }
}
