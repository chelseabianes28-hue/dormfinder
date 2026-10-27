<?php
require_once __DIR__ . '/includes/auth.php';

unset($_SESSION['user']);
session_regenerate_id(true);
set_flash('success', 'You have been logged out.');
redirect('login.php');
