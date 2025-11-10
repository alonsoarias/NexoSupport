<?php
/**
 * Debug POST data
 */

header('Content-Type: application/json');

$debug = [
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
    'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
    '_POST' => $_POST,
    '_REQUEST' => $_REQUEST,
    'php://input' => file_get_contents('php://input'),
];

echo json_encode($debug, JSON_PRETTY_PRINT);
