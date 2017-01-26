<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once 'db_connector.php';

use Symfony\Component\Yaml\Parser;

$yaml = new Parser();

$creds = $yaml->parse( file_get_contents( 'facebook_keys.yaml' ) );
$APP_ID = $creds['app_id'];
$APP_SECRET = $creds['app_secret'];

$db = new DBConnector();
if (!$db->exists())
{
    $db->prepareDB();
}

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
    return $response->withRedirect('/list/none', 301);
});

$app->get('/list/{groupid}', function ($request, $response, $args) use ($fb, $helper, $db) {

    $groupid = $request->getAttribute('groupid') ?: "none";
    $token = $_SESSION['facebook_access_token'];

    if (empty($token)) {
        $response->write("Unauthorized");
        return $response;
    }

    $fb->setDefaultAccessToken($token);
    try
    {
        $response->withHeader('Content-Type', 'text/html;charset=ISO-8859-2');
        $resp = $fb->get($groupid . '/feed');
        //$graphObj = $resp->getGraphEdge()->getPropertyAsArray("data");
        $respEdge = $resp->getGraphEdge();
        foreach($respEdge as $respNode)
        {
            $postid = $respNode['id'];
            $response->write("Post Id: " . $postid . "</br>");
            $comments = $fb->get($postid . '/comments');
            $commentObject = $comments->getGraphEdge();
            foreach($commentObject as $comment)
            {
                $db->insertComment($groupid, $postid, $comment['id'], $comment['message']);
                $response->write("Comments: " . $comment  . "</br/>");
            }
        }
        //$content = {};
        //$content[]
        return $response;
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        $response->write($e->getMessage());
        return $response;
    }
});

$app->run();
