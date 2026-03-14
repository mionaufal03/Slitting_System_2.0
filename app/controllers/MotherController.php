<?php
namespace App\Controllers;

use App\Models\MotherCoilModel;

class MotherController
{
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function edit()
    {
        if (!isset($_GET['id'])) {
            die("Missing ID");
        }

        $id = intval($_GET['id']);

        $motherModel = new MotherCoilModel($this->pdo);
        $data = $motherModel->getById($id);

        if (!$data) {
            die("Mother coil not found");
        }

        // Pass to view
        require_once __DIR__ . '/../views/edit_mother.php';
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Invalid request");
        }

        $id = intval($_POST['id']);
        $data = [
            'id' => $id,
            'product' => $_POST['product'],
            'lot_no' => $_POST['lot_no'],
            'coil_no' => $_POST['coil_no'],
            'length' => $_POST['length'],
            'width' => $_POST['width'],
            'grade' => $_POST['grade'] ?? '', // Assuming grade is added
        ];

        $motherModel = new MotherCoilModel($this->pdo);
        $result = $motherModel->update($data);

        if ($result) {
            header("Location: index.php?controller=mother&action=list"); // Assuming a list action
            exit;
        } else {
            die("Update failed");
        }
    }

    public function delete()
    {
        if (!isset($_GET['id'])) {
            die("Missing ID");
        }

        $id = intval($_GET['id']);

        $motherModel = new MotherCoilModel($this->pdo);
        $result = $motherModel->delete($id);

        if ($result) {
            header("Location: index.php?controller=mother&action=list");
            exit;
        } else {
            die("Delete failed");
        }
    }

    public function list()
    {
        $motherModel = new MotherCoilModel($this->pdo);
        $coilsResult = $motherModel->getAll();

        // Pass to view
        require_once __DIR__ . '/../views/mother_coil_list.php';
    }

    public function dual()
    {
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            die("Invalid ID");
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/Android|iPhone|iPad|Mobile/i', $userAgent)) {
            header("Location: index.php?controller=mother&action=view&id=$id");
            exit;
        }

        require_once __DIR__ . '/../views/mothers/dual.php';
    }

    public function view()
    {
        // Placeholder for view action
        $id = intval($_GET['id'] ?? 0);
        echo "<h1>Viewing Mother Coil ID: $id</h1>";
        // Here you would fetch data and show a view
    }

    public function scan()
    {
        // Placeholder for scan action
        $id = intval($_GET['id'] ?? 0);
        echo "<h1>Scanning Mother Coil ID: $id</h1>";
        // Here you would implement the scanner logic
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Invalid request");
        }

        $data = [
            'product' => $_POST['product'],
            'lot_no' => $_POST['lot_no'],
            'coil_no' => $_POST['coil_no'],
            'length' => $_POST['length'],
            'width' => $_POST['width'],
            'grade' => $_POST['grade'] ?? '',
        ];

        $motherModel = new MotherCoilModel($this->pdo);
        $result = $motherModel->add($data);

        if ($result) {
            header("Location: index.php?controller=mother&action=list");
            exit;
        } else {
            die("Create failed");
        }
    }

    public function print()
    {
        // Placeholder for print action
        $id = intval($_GET['id'] ?? 0);
        echo "<h1>Printing Mother Coil ID: $id</h1>";
        // Here you would implement the print logic
    }

    public function qr()
    {
        // Placeholder for qr action
        echo "<h1>Generating QR Code</h1>";
        // Here you would implement the QR code generation logic
    }

    public function getProductByCoil()
    {
        $coil_no = $_GET['coil'] ?? '';
        $motherModel = new MotherCoilModel($this->pdo);
        $product = $motherModel->productFromCoil($coil_no);

        header('Content-Type: application/json');
        if ($product) {
            echo json_encode(['ok' => true, 'product' => $product]);
        } else {
            echo json_encode(['ok' => false]);
        }
        exit;
    }

    // Add other methods as needed
}