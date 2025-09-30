<?php
session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_to_cart') {
        $product_id = $_POST['product_id'] ?? '';
        if (!empty($product_id)) {
            if (!isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] = 0;
            }
            $_SESSION['cart'][$product_id]++;
            $success_message = 'Product added to cart!';
        }
    } elseif ($action === 'remove_from_cart') {
        $product_id = $_POST['product_id'] ?? '';
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $success_message = 'Product removed from cart!';
        }
    } elseif ($action === 'update_quantity') {
        $product_id = $_POST['product_id'] ?? '';
        $quantity = intval($_POST['quantity'] ?? 0);
        if (isset($_SESSION['cart'][$product_id]) && $quantity > 0) {
            $_SESSION['cart'][$product_id] = $quantity;
        }
    } elseif ($action === 'clear_cart') {
        $_SESSION['cart'] = [];
        $success_message = 'Cart cleared!';
    }
}

// Sample products
$products = [
    [
        'id' => 1,
        'name' => 'Laptop Pro 15"',
        'description' => 'High-performance laptop with 16GB RAM and 512GB SSD',
        'price' => 1299.99,
        'image' => '💻'
    ],
    [
        'id' => 2,
        'name' => 'Wireless Mouse',
        'description' => 'Ergonomic wireless mouse with precision tracking',
        'price' => 29.99,
        'image' => '🖱️'
    ],
    [
        'id' => 3,
        'name' => 'Mechanical Keyboard',
        'description' => 'RGB backlit mechanical keyboard with blue switches',
        'price' => 89.99,
        'image' => '⌨️'
    ],
    [
        'id' => 4,
        'name' => 'USB-C Hub',
        'description' => '7-in-1 USB-C hub with HDMI and SD card reader',
        'price' => 45.99,
        'image' => '🔌'
    ],
    [
        'id' => 5,
        'name' => 'Webcam HD',
        'description' => '1080p HD webcam with built-in microphone',
        'price' => 69.99,
        'image' => '📷'
    ],
    [
        'id' => 6,
        'name' => 'Headphones Pro',
        'description' => 'Noise-cancelling over-ear headphones',
        'price' => 199.99,
        'image' => '🎧'
    ]
];

// Calculate cart total
$cart_total = 0;
$cart_items = [];
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    foreach ($products as $product) {
        if ($product['id'] == $product_id) {
            $cart_items[] = array_merge($product, ['quantity' => $quantity]);
            $cart_total += $product['price'] * $quantity;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Store - Hidden Page</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .store-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .store-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .product-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .cart-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        
        .cart-item {
            border-bottom: 1px solid #e5e7eb;
            padding: 15px 0;
        }
        
        .cart-total {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #65408b 100%);
        }
        
        .badge-cart {
            background: var(--danger);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<div class="store-container">
    <div class="store-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fas fa-store me-2"></i>Tech Store</h1>
                <p class="text-muted mb-0">Hidden store page - Premium tech accessories</p>
            </div>
            <div>
                <span class="badge-cart">
                    <i class="fas fa-shopping-cart me-1"></i>
                    <?= count($_SESSION['cart']) ?> items
                </span>
            </div>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Products Section -->
        <div class="col-lg-8">
            <h3 class="text-white mb-4"><i class="fas fa-box-open me-2"></i>Our Products</h3>
            <div class="row">
                <?php foreach ($products as $product): ?>
                <div class="col-md-6 mb-4">
                    <div class="product-card">
                        <div class="text-center">
                            <div class="product-icon"><?= $product['image'] ?></div>
                            <h5><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                            <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cart Section -->
        <div class="col-lg-4">
            <div class="cart-section">
                <h4 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h4>
                
                <?php if (empty($cart_items)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-shopping-basket fa-3x mb-3"></i>
                        <p>Your cart is empty</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6><?= htmlspecialchars($item['name']) ?></h6>
                                <p class="text-muted mb-0">$<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></p>
                            </div>
                            <div class="text-end">
                                <strong>$<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove_from_cart">
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger ms-2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-total">
                        Total: $<?= number_format($cart_total, 2) ?>
                    </div>
                    
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-success btn-lg" onclick="checkout()">
                            <i class="fas fa-credit-card me-2"></i>Checkout
                        </button>
                        <form method="POST">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-2"></i>Clear Cart
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function checkout() {
    alert('🎉 Checkout successful!\n\nTotal: $<?= number_format($cart_total, 2) ?>\n\nThank you for your purchase!\n\nThis is a demo store.');
    <?php $_SESSION['cart'] = []; ?>
    location.reload();
}

// Auto-hide alerts
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 3000);
</script>

</body>
</html>
