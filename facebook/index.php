<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
$APP_ID = "<APP_ID>";
$APP_SECRET = "<APP_SECRET>";

$fb = new Facebook\Facebook([
  'app_id' => $APP_ID,
  'app_secret' => $APP_SECRET,
  'default_graph_version' => 'v2.5',
]);
$helper = $fb->getRedirectLoginHelper();

$app = new Slim\App();

$app->get('/login', function ($request, $response, $args) use ($helper) {
    $permissions = ['user_posts']; // optional
    $loginUrl = $helper->getLoginUrl('http://localhost:8000/callback', $permissions);
    $response->write("<a href='". $loginUrl ."'>LoginWithFaceBook</a>");
    return $response;
});

$app->get('/callback', function ($request, $response, $args) use ($app, $helper) {
    try {
      $accessToken = $helper->getAccessToken();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        $response->write("Exception occured:" . $e->getMessage());
        return $response;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        $response->write("Exception occured:" . $e->getMessage());
        return $response;
    }

    if (isset($accessToken)) {
        $_SESSION['facebook_access_token'] = (string) $accessToken;
    }
    return $response->withRedirect('/list', 301);
});

$app->get('/list/{groupid}', function ($request, $response, $args) use ($fb, $helper) {

    $groupid = $request->getAttribute('groupid') ?: "none";
    $token = $_SESSION['facebook_access_token'];

    if (empty($token)) {
        $response->write("Unauthorized");
        return $response;
    }

    $fb->setDefaultAccessToken($token);
    try
    {
        $resp = $fb->get($groupid . '/feed');
        $wholeResponse = json_encode((array)$resp);
        $response->write($wholeResponse);
        return $response;
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        $response->write($e->getMessage());
        return $response;
    }
});

$app->run();
