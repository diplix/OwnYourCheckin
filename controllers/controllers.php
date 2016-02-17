<?php

function require_login(&$app) {
  if(!array_key_exists('user_id', $_SESSION)) {
    $app->redirect('/');
    return false;
  } else {
    return ORM::for_table('users')->find_one($_SESSION['user_id']);
  }
}

$app->get('/', function($format='html') use($app) {
  $res = $app->response();

  // Total number of checkins
  $total_checkins = ORM::for_table('users')
    ->sum('checkin_count');

  $total_users = ORM::for_table('users')
    ->where_gt('checkin_count', 0)
    ->where_not_null('last_micropub_response')
    ->count();

  // Find the top ranked users this week
  $date = new DateTime();
  while($date->format('D') != 'Sun') {
    $date->modify('-1 day');
  }

  $users = ORM::for_table('users')
    //->where('fsq_public', 1)
    ->where('micropub_success', 1)
    ->where_not_null('last_fsq_img_url')
    ->where_gte('last_checkin_date', $date->format('Y-m-d'))
    ->where_gt('checkin_count_this_week', 0)
    ->order_by_desc('checkin_count_this_week')
    ->find_many();

  ob_start();
  render('index', array(
    'title' => 'OwnYourCheckin',
    'meta' => '',
    'users' => $users,
    'total_checkins' => $total_checkins,
    'total_users' => $total_users
  ));
  $html = ob_get_clean();
  $res->body($html);
});


$app->get('/creating-a-token-endpoint', function() use($app) {
  $app->redirect('http://indiewebcamp.com/token-endpoint', 301);
});
$app->get('/creating-a-micropub-endpoint', function() use($app) {
  $html = render('creating-a-micropub-endpoint', array('title' => 'Creating a Micropub Endpoint'));
  $app->response()->body($html);
});

$app->get('/fsq', function() use($app) {
  if($user=require_login($app)) {

    // If the user hasn't connected their Foursquare account yet, redirect to the page to auth Foursquare
    if($user->fsq_access_token == '') {
      $app->redirect('/auth/fsq-start');
    } else {
      // Go fetch the latest Foursquare checkin and show it to them for testing the micropub endpoint
      try {
        if($checkins = FSQ\get_latest_checkin($user)) {
          $entry = h_entry_from_checkin($user, $checkins[0]);
          //check if there’s a photo, if not?
          if (count($checkins[0]->photos->items)>0) {
          	$photo_url = $checkins[0]->photos->items[0]->prefix.'original'.$checkins[0]->photos->items[0]->suffix;
          } else {
          	$photo_url = "";
          }
        } else {
          $entry = false;
          $checkin_url = false;
        }
      } catch(FSQ\AccessTokenException $e) {
        $user->fsq_access_token = '';
        $user->fsq_response = '';
        $user->save();
        $app->redirect('/auth/fsq-start');
      } catch(Exception $e) {
        $html = render('auth_error', array(
          'title' => 'Error',
          'error' => 'Error',
          'errorDescription' => $e->getMessage()
        ));
        $app->response()->body($html);
        return;
      }

      $test_response = '';
      if($user->last_micropub_response) {
        try {
          if(@json_decode($user->last_micropub_response)) {
            $d = json_decode($user->last_micropub_response);
            $test_response = $d->response;
          }
        } catch(Exception $e) {
        }
      }

      $html = render('fsq', array(
        'title' => 'fsq',
        'entry' => $entry,
        'photo_url' => $photo_url,
        'micropub_endpoint' => $user->micropub_endpoint,
        'test_response' => $test_response,
        'user' => $user
      ));
      $app->response()->body($html);
    }
  }
});

$app->post('/prefs/array', function() use($app) {
  if($user=require_login($app)) {
    $user->send_category_as_array = 1;
    $user->save();

    $app->response()->body(json_encode(array(
      'result' => 'ok'
    )));
  }
});

$app->post('/micropub/test', function() use($app) {
  if($user=require_login($app)) {
    $params = $app->request()->params();

    if($user->send_category_as_array != 1) {
      if(is_array($params['category']))
        $params['category'] = implode(',', $params['category']);
    }

    $params['mp-type'] = 'checkin';

    // Download the file to a temp folder
    $filename = download_file($params['url']);

    // Now send to the micropub endpoint
    $r = micropub_post($user->micropub_endpoint, $user->micropub_access_token, $params, $filename);
    $response = $r['response'];

    #unlink($filename);

    $user->last_micropub_response = json_encode($r);

    // Check the response and look for a "Location" header containing the URL
    if($response && preg_match('/Location: (.+)/', $response, $match)) {
      $location = $match[1];
      $user->micropub_success = 1;
      $user->last_micropub_url = $location;
      $user->checkin_count = $user->checkin_count + 1;
    } else {
      $location = false;
    }

    $user->save();

    $app->response()->body(json_encode(array(
      'response' => htmlspecialchars($response),
      'location' => $location,
      'error' => $r['error'],
      'curlinfo' => $r['curlinfo'],
      'filename' => $filename
    )));
  }
});
