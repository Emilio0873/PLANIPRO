<?php
require_once __DIR__ . '/../includes/init.php';
logout_user();
session_start();
flash('success', 'Vous êtes déconnecté.');
redirect('index.php');
