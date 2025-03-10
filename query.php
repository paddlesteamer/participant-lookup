<?php

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$name = strtoupper(str_replace(['Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö'], ['C', 'S', 'G', 'U', 'I', 'O'], $data['name']));

function queryItra($name) {
    // Initialize cURL
    $ch = curl_init();

    // Prepare data
    $postData = array(
        'name' => $name,
        'start' => '1', 
        'count' => '10',
        'echoToken' => rand() / getrandmax()
    );

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, 'https://itra.run/api/runner/find');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:135.0) Gecko/20100101 Firefox/135.0');

    // Execute request
    $response = curl_exec($ch);
    curl_close($ch);

    // Parse response
    $result = json_decode($response, true);

    // Return empty string if no results, otherwise return PI of first result
    return $result['resultCount'] == 0 ? null : [
        'pi' => $result['results'][0]['pi'],
        'gender' => ($result['results'][0]['gender'] == 'H' ? 'M' : 'F'),
        'ageGroup' => $result['results'][0]['ageGroup'],
        'runnerId' => $result['results'][0]['runnerId']
    ];
}

$itra = queryItra($name);

if ($itra != null) {
    echo json_encode($itra);
    exit;
}

// Try splitting name and searching with first and last name only
$nameParts = explode(' ', $name);
if (count($nameParts) > 2) {
    $simplifiedName = $nameParts[0] . ' ' . end($nameParts);
    $itra = queryItra($simplifiedName);
    if ($itra != null) {
        echo json_encode($itra);
        exit;
    }
}

// No results found
echo json_encode(['pi' => '', 'gender' => '', 'ageGroup' => '', 'runnerId' => '']);

?>