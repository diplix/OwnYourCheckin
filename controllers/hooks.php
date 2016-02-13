<?php

$app->post('/fsq/callback', function() use($app) {
  // Will be something like this
  /*
  [
    {
        "subscription_id": "1",
        "object": "user",
        "object_id": "1234",
        "changed_aspect": "media",
        "time": 1297286541
    },
    {
        "subscription_id": "2",
        "object": "tag",
        "object_id": "nofilter",
        "changed_aspect": "media",
        "time": 1297286541
    },
    ...
  ]
  */

  // Queue a job to process this request
  // foursquare sends the json url encoded
	/*
	$checkin = "";
	$params = $app->request()->params();
	if(!array_key_exists('checkin', $params)) {
		$checkin = urldecode($params->checkin);
	}
	*/

	$params = $app->request()->params();
	$checkin = urldecode($params['checkin']);
	//bs()->putInTube(Config::$hostname.'-worker', urldecode($app->request()->getBody()));
	bs()->putInTube(Config::$hostname.'-worker', $checkin);
});

// Respond to the callback challenge from Foursquare
// http://instagram.com/developer/realtime/
// probably won’t need this for Foursquare...
$app->get('/fsq/callback', function() use($app) {
  $params = $app->request()->params();
  if(array_key_exists('hub_challenge', $params))
    $app->response()->body($params['hub_challenge']);
  else
    $app->response()->body('error');
});
