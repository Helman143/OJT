<?php
// Simple script to generate password hash for admin account
// Usage: php generate_password_hash.php [password]

$password = $argv[1] ?? 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "\nSQL Update Command:\n";
echo "UPDATE admins SET password = '$hash' WHERE username = 'admin';\n";
?>

