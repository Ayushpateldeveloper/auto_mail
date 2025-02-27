<?php
if (isset($_GET['code'])) {
    // Assuming the code is returned by Google after user authorization
    $authorization_code = $_GET['code'];

    $url = 'https://oauth2.googleapis.com/token';
    $data = array(
        'code' => $authorization_code,
        'client_id' => 'YOUR_CLIENT_ID',
        'client_secret' => 'YOUR_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost/oauth2callback',
        'grant_type' => 'authorization_code'
    );

    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result);

    if (isset($response->access_token)) {
        // Store the refresh token securely
        $refresh_token = $response->refresh_token;
        file_put_contents('refresh_token.txt', $refresh_token);  // Example storage method

        echo '<p>Authorization successful. You can now send emails.</p>';
    } else {
        echo '<p>Authorization failed.</p>';
    }
} else {
    echo '<p>No code provided.</p>';
}
?>
