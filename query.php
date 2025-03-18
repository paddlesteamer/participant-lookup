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
    
    $response1 = $result['response1'];
    $response2 = $result['response2'];
    $response3 = $result['response3'];

    $key = base64_decode($response3);
    $iv = base64_decode($response2);

    // Decrypt using the key
    $decrypted = openssl_decrypt($response1, 'AES-256-CBC', $key, 0, $iv);

    if ($decrypted == false) {
        $result = array(
            'ResultCount' => 0,
            'Results' => array()
        );
    } else {
        $result = json_decode($decrypted, true);
    }

    // Return empty string if no results, otherwise return PI of first result
    return $result['ResultCount'] == 0 ? null : [
        'pi' => $result['Results'][0]['Pi'],
        'gender' => ($result['Results'][0]['Gender'] == 'H' ? 'M' : 'F'),
        'ageGroup' => $result['Results'][0]['AgeGroup'],
        'runnerId' => $result['Results'][0]['RunnerId']
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