<?php
// esewa_success.php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful - ShopEasy</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .success { color: #2e7d32; }
        .button { background: #ff4e50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <h1 class="success">Payment Successful!</h1>
    <p>Thank you for your purchase. Your order has been confirmed.</p>
    <a href="http://localhost:8888" class="button">Continue Shopping</a>
    
    <script>
        // Notify the main window about successful payment
        if (window.opener) {
            window.opener.postMessage({ type: 'esewa_payment_success' }, '*');
            setTimeout(() => window.close(), 2000);
        }
    </script>
</body>
</html>