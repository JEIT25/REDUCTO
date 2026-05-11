<?php
/**
 * Authentication check (AMORA-style).
 * Ensures user is logged in; redirects to auth/login if not.
 * Provides $user, $userRole, hasRole(), isAdmin(), isSuperadmin().
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/path_helper.php';
$user = requireLogin();
$userRole = $user['role'] ?? 'basic-user';
