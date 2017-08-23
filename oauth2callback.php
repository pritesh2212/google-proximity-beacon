<?php
require_once '../vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setAuthConfigFile('client_secrets.json');
$client->setAccessType('offline'); // default: offline
$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/google/oauth/oauth2callback.php');
$client->addScope(Google_Service_Proximitybeacon::USERLOCATION_BEACON_REGISTRY);

//print_r($_REQUEST);
//print_r($_SESSION);
$beacon_data = $_SESSION['beacon_data'];
if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
//    echo '<pre>'; print_r($auth_url); echo __FILE__; echo __LINE__; exit;
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
	//echo "A";
    $client->authenticate($_GET['code']);
//    echo '<pre>'; print_r($_GET); echo __FILE__; echo __LINE__; exit;
    $_SESSION['access_token'] = $client->getAccessToken();
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/google/oauth/?data='.$beacon_data;
    /*print_r($_SESSION['access_token']);
    exit;*/
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
