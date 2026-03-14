<div class="mb-3 d-flex justify-content-between align-items-center">
    <nav>
        <a href="index.php" class="btn btn-outline-primary btn-sm">Dashboard</a>
        <a href="mother_coil.php" class="btn btn-outline-primary btn-sm">Mother Coil</a>
        <a href="finish_product.php" class="btn btn-outline-primary btn-sm">Finish Product</a>
        <a href="reslit.php" class="btn btn-outline-primary btn-sm">Reslit</a>
        <a href="recoiling.php" class="btn btn-outline-primary btn-sm">Recoiling</a>
    </nav>

    <?php if (isset($_SESSION['role'])): ?>
        <div>
            <?php if ($_SESSION['role'] === 'slitting'): ?>
                <a href="index.php" class="btn btn-secondary btn-sm">← Back</a>
            <?php else: ?>
                <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<hr>