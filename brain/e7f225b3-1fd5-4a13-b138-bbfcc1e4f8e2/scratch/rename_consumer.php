<?php
$root = 'c:/xampp/htdocs/REDUCTO';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    
    // Skip vendors or system files if any (none expected here)
    if (strpos($path, '.git') !== false) continue;
    if (strpos($path, '.gemini') !== false) continue;
    
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'js', 'html', 'css', 'sql'])) continue;

    $content = file_get_contents($path);
    $original = $content;

    // 1. Precise role strings
    $content = str_replace("'basic-user'", "'basic-user'", $content);
    $content = str_replace('"basic-user"', '"basic-user"', $content);
    
    // 2. Visible labels
    $content = str_replace('>Basic User<', '>Basic User<', $content);
    $content = str_replace(' Basic User ', ' Basic User ', $content);
    
    // 3. Enum in SQL
    $content = str_replace("enum('basic-user'", "enum('basic-user'", $content);
    
    // 4. Specific strings found in grep
    $content = str_replace('register_basic-user', 'register_basic-user', $content);
    $content = str_replace('New basic-user registration', 'New basic-user registration', $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Updated: $path\n";
    }
}

// Database Update
require_once $root . '/php/database/db_connect.php';

// A. Update users table enum and values
echo "Updating Database...\n";
$conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('basic-user','admin','superadmin') NOT NULL DEFAULT 'basic-user'");
$conn->query("UPDATE users SET role = 'basic-user' WHERE role = 'basic-user'");

// B. Update approvals table
$conn->query("UPDATE approvals SET action_type = 'register_basic-user' WHERE action_type = 'register_basic-user'");
$conn->query("UPDATE approvals SET reason = REPLACE(reason, 'basic-user', 'basic-user')");

echo "Done!\n";
