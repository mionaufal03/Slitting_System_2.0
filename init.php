<?php
// Define the root directory of your project
define('BASE_PATH', __DIR__ . '/');

// Define paths to your new MVC folders
define('MODEL_PATH', BASE_PATH . 'models/');
define('VIEW_PATH', BASE_PATH . 'views/');
define('ASSET_PATH', 'assets/'); // For HTML tags like <img>

// Automatically include the database configuration
require_once BASE_PATH . 'config.php';