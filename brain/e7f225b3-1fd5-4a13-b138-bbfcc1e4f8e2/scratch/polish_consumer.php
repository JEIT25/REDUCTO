<?php
$root = 'c:/xampp/htdocs/REDUCTO';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'js', 'html'])) continue;

    $content = file_get_contents($path);
    $original = $content;

    // UI Labels
    $content = str_replace('Manage Basic Users', 'Manage Basic Users', $content);
    $content = str_replace('Basic User Account', 'Basic User Account', $content);
    $content = str_replace('Basic User List', 'Basic User List', $content);
    $content = str_replace('Basic User Details', 'Basic User Details', $content);
    $content = str_replace('New basic-user', 'New basic-user', $content);
    $content = str_replace('register_basic-user', 'register_basic-user', $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Polished: $path\n";
    }
}
echo "Done!\n";
