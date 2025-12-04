<?php
require 'db.php';

$adminEmail = "admin@southernct.edu";
$adminPass = password_hash("AdminPassword123", PASSWORD_DEFAULT);
$role = "admin";

$sql = "INSERT INTO Accounts (email, password, role) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sss", $adminEmail, $adminPass, $role);
mysqli_stmt_execute($stmt);
echo "Admin created. Account ID: " . mysqli_insert_id($conn);
