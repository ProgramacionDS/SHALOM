<?php
require_once __DIR__ . '/../includes/auth.php';
flash('info', 'Sesión cerrada.');
logout();
