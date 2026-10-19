<?php
// Temporary page to confirm XAMPP is serving the project. Replaced by the real landing page later.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DormFinder</title>
  <style>
    body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; background: #f4f6f8; color: #222; }
    main { text-align: center; padding: 1rem; }
    h1 { margin-bottom: .25rem; }
    p { margin: .25rem 0; }
  </style>
</head>
<body>
  <main>
    <h1>DormFinder</h1>
    <p>XAMPP is working and the project folder is set up.</p>
    <p>PHP version: <?php echo htmlspecialchars(PHP_VERSION); ?></p>
  </main>
</body>
</html>
