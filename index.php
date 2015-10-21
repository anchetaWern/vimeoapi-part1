<?php
session_start();
require_once 'vendor/autoload.php';

define('CLIENT_ID', '');
define('CLIENT_SECRET', '');
define('REDIRECT_URI', 'http://localhost/tester/vimeo-slim/login');

$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig() //use twig for handling views
));


$view = $app->view();
$view->parserOptions = array(
    'debug' => true, //enable error reporting in the view
    'cache' => dirname(__FILE__) . '/cache' //set directory for caching views
);

$vimeo = new \Vimeo\Vimeo(CLIENT_ID, CLIENT_SECRET);

$app->get('/token', function() use ($app, $vimeo) {

	$token = $vimeo->clientCredentials();

	echo $token['body']['access_token'];

});

$app->get('/login', function () use ($app, $vimeo) {

	if($app->request->get('code') && $app->request->get('state') == $_SESSION['state']){
		
		$code = $app->request->get('code');

		$token = $vimeo->accessToken($code, REDIRECT_URI);

		$access_token = $token['body']['access_token'];
		$vimeo->setToken($access_token);

		$_SESSION['user.access_token'] = $access_token;

		$page_data = array(
			'user' => $token['body']['user']
		);

	}else{

		$scopes = array('public', 'private');
		$state = substr(str_shuffle(md5(time())), 0, 10);
		$_SESSION['state'] = $state;

		$url = $vimeo->buildAuthorizationEndpoint(REDIRECT_URI, $scopes, $state);

		$page_data = array(
			'url' => $url
		);
	}
	
	$app->render('login.php', $page_data);


});

$app->get('/me/feed', function () use ($app, $vimeo) {
	
	$vimeo->setToken($_SESSION['user.access_token']);
 	$response = $vimeo->request('/me/feed', array('per_page' => 10));
 	
 	$page_data = $response['body'];

 	$app->render('feed.php', $page_data);

});


$app->get('/videos', function () use ($app, $vimeo) {

	$page_data = array();

	if($app->request->get('query')){

		$vimeo->setToken($_SESSION['user.access_token']);
		$query = $app->request->get('query');
		$response = $vimeo->request('/videos', array('query' => $query, 'per_page' => 10));
		
		$page_data = array(
			'query' => $query,
			'results' => $response['body']
		);

		
	}

	$app->render('videos.php', $page_data);

});

$app->run();