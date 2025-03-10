<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];

    if (file_exists($file)) {
        $participants = [];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Assuming each line contains a name and surname separated by a space
            $parts = preg_split('/\s+/', trim($line));
            $words = array_filter($parts, function($word) {
                return preg_match('/[a-zA-ZöÖüÜıİçÇşŞğĞ]/', $word); // Keep only parts containing letters
            });
            if (count($words) >= 2) {
                $name = array_shift($words); // Get first word as name
                $surname = implode(' ', $words); // Join remaining words as surname
                $participants[] = [
                    'name' => $name . ' ' . $surname,
                    'team' => '',
                    'gender' => '',
                ];
            }
        }

        echo json_encode(['participants' => $participants]);
    } else {
        echo json_encode(['error' => 'File not found.']);
    }
} else {
    echo json_encode(['error' => 'Invalid request.']);
} 