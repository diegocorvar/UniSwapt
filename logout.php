<?php
require_once __DIR__ . '/php/includes/bootstrap.php';

$_SESSION = [];
session_destroy();

session_start();
flashMessage('success', 'Cerraste sesion correctamente.');
redirectTo('index.php');
