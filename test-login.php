<?php
$ch = curl_init('https://sfp-web-app-production.up.railway.app/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
preg_match('/XSRF-TOKEN=([^;]+)/', $response, $xsrf);
preg_match('/laravel-session=([^;]+)/', $response, $session);

$token = urldecode($xsrf[1]);
$sess = $session[1];

preg_match('/name="_token" value="([^"]+)"/', $response, $csrf);
$csrfToken = $csrf[1];

$post = http_build_query([
    '_token' => $csrfToken,
    'username' => 'demo-admin',
    'password' => 'demo12345'
]);

$ch2 = curl_init('https://sfp-web-app-production.up.railway.app/login');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HEADER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch2, CURLOPT_COOKIE, "XSRF-TOKEN={$xsrf[1]}; laravel-session={$sess}");
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Referer: https://sfp-web-app-production.up.railway.app/login',
    'Origin: https://sfp-web-app-production.up.railway.app'
]);
$response2 = curl_exec($ch2);
echo "STATUS:\n" . curl_getinfo($ch2, CURLINFO_HTTP_CODE) . "\n";
echo "HEADERS:\n" . substr($response2, 0, strpos($response2, "\r\n\r\n")) . "\n";
