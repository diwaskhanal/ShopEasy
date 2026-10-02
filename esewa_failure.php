<?php
// esewa_failure.php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed - ShopEasy</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #c62828; }
        .button { background: #ff4e50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <h1 class="error">Payment Failed</h1>
    <p>There was an issue with your payment. Please try again.</p>
    <a href="http://localhost:8888" class="button">Try Again</a>
    
    <script>
        // Notify the main window about failed payment
        if (window.opener) {
            window.opener.postMessage({ type: 'esewa_payment_failed' }, '*');
            setTimeout(() => window.close(), 2000);
        }
    </script>
</body>
</html>