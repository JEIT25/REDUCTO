<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('basic-user');

$user_id = $_SESSION['user']['id'];
$address = trim($_POST['address'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'cod';
$notes = trim($_POST['notes'] ?? '');
$from_cart = isset($_POST['from_cart']);

if (!$address) {
    echo json_encode(['success' => false, 'error' => 'Address is required']);
    exit;
}

// Transaction
$conn->begin_transaction();

try {
    if ($from_cart) {
        // Fetch cart items grouped by playground
        $sql = "SELECT ci.package_id, ci.quantity, m.price, m.playground_id
                FROM booking_cart c
                JOIN cart_packages ci ON c.id = ci.cart_id
                JOIN play_packages m ON ci.package_id = m.id
                WHERE c.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $items_by_resto = [];
        while ($row = $res->fetch_assoc()) {
            $items_by_resto[$row['playground_id']][] = $row;
        }
        $stmt->close();

        if (empty($items_by_resto)) {
            throw new Exception("Cart is empty");
        }

        // Create one order per playground
        foreach ($items_by_resto as $resto_id => $items) {
            $total_amount = 0;
            foreach ($items as $item)
                $total_amount += $item['price'] * $item['quantity'];

            // Insert Order
            $stmt = $conn->prepare("INSERT INTO package_orders (user_id, playground_id, total_amount, delivery_address, notes, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param('sidss', $user_id, $resto_id, $total_amount, $address, $notes);
            $stmt->execute();
            $order_id = $stmt->insert_id;
            $stmt->close();

            // Insert Order Items
            $stmt = $conn->prepare("INSERT INTO package_order_items (order_id, package_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $stmt->bind_param('iiidd', $order_id, $item['package_id'], $item['quantity'], $item['price'], $subtotal);
                $stmt->execute();
            }
            $stmt->close();
        }

        // Clear Cart
        $stmt = $conn->prepare("DELETE FROM booking_cart WHERE user_id = ?");
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        $stmt->close();

    }
    else {
        // Single item checkout (legacy/direct buy) - Optional support
        // For now, assume cart flow
        throw new Exception("Direct buy not implemented yet");
    }

    $conn->commit();
    echo json_encode(['success' => true]);

}
catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>


