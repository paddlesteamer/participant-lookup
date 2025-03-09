<?php
    function getParticipantsFromIframe($iframe_url) {
        // Get iframe content
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $iframe_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:135.0) Gecko/20100101 Firefox/135.0');
        $iframe_content = curl_exec($ch);
        curl_close($ch);

        // Parse iframe content
        $iframe_dom = new DOMDocument('1.0', 'UTF-8');
        @$iframe_dom->loadHTML(mb_convert_encoding($iframe_content, 'HTML-ENTITIES', 'UTF-8'));
        $iframe_xpath = new DOMXPath($iframe_dom);
        
        // Get all rows from the table, skipping header row
        $rows = $iframe_xpath->query('//table//tr[position()>1]');
        $participants = [];

        foreach ($rows as $row) {
            $cells = $iframe_xpath->query('.//td', $row);
            if ($cells->length >= 5) {
                $participant = [
                    'name' => trim($cells->item(0)->textContent) . ' ' . trim($cells->item(1)->textContent),
                    'team' => trim($cells->item(2)->textContent), 
                    'gender' => $cells->item(4)->getElementsByTagName('i')->length > 0 ? 
                        (strpos($cells->item(4)->getElementsByTagName('i')->item(0)->getAttribute('class'), 'Erkek') !== false ? 'Erkek' : 
                        (strpos($cells->item(4)->getElementsByTagName('i')->item(0)->getAttribute('class'), 'Kadin') !== false ? 'Kadin' : ''))
                        : trim($cells->item(4)->textContent)
                ];
                $participants[] = $participant;
            }
        }
        return $participants;
    }

    header('Content-Type: application/json');

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $url = $data['url'];

    // Check if URL is empty
    if (empty($url)) {
        echo json_encode(['error' => 'URL cannot be empty']);
        exit;
    }

    // Parse URL to get domain
    $parsed_url = parse_url($url);
    $domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';

    // If domain is apphurra.com, call function directly
    if (strpos($domain, 'apphurra.com') !== false) {
        $participants = getParticipantsFromIframe($url);
        echo json_encode(['participants' => $participants]);
        exit;
    }

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    // Get the page content
    $content = curl_exec($ch);
    curl_close($ch);

    // Create DOM parser
    $dom = new DOMDocument();
    @$dom->loadHTML($content);
    $xpath = new DOMXPath($dom);

    // Find iframe with apphurra.com domain
    $iframes = $xpath->query('//iframe[contains(@src, "apphurra.com")]');
    if ($iframes->length > 0) {
        $iframe_url = $iframes->item(0)->getAttribute('src');

        $participants = getParticipantsFromIframe($iframe_url);

        
        echo json_encode(['participants' => $participants]);
    } else {
        echo json_encode(['error' => 'Iframe not found']);
    }
    ?>
