<?php
$c = mysqli_connect('localhost', 'root', '', 'littlelands_db');
$r = mysqli_query($c, "UPDATE users SET role = 'basic-user' WHERE role = '' OR role IS NULL");
echo mysqli_affected_rows($c) . " rows updated.\n";
