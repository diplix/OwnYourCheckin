<?php

ORM::configure('mysql:host=' . Config::$dbHost . ';dbname=' . Config::$dbName);
ORM::configure('username', Config::$dbUsername);
ORM::configure('password', Config::$dbPassword);

function render($page, $data) {
  global $app;
  return $app->render('layout.php', array_merge($data, array('page' => $page)));
};

function partial($template, $data, $debug=false) {
  global $app;

  if($debug) {
    $tpl = new Savant3(\Slim\Extras\Views\Savant::$savantOptions);
    echo '<pre>' . $tpl->fetch($template . '.php') . '</pre>';
    return '';
  }

  ob_start();
  $tpl = new Savant3(\Slim\Extras\Views\Savant::$savantOptions);
  foreach($data as $k=>$v) {
    $tpl->{$k} = $v;
  }
  $tpl->display($template . '.php');
  return ob_get_clean();
}

function session($key) {
  if(array_key_exists($key, $_SESSION))
    return $_SESSION[$key];
  else
    return null;
}

function k($a, $k, $default=null) {
  if(is_array($k)) {
    $result = true;
    foreach($k as $key) {
      $result = $result && array_key_exists($key, $a);
    }
    return $result;
  } else {
    if(is_array($a) && array_key_exists($k, $a) && $a[$k])
      return $a[$k];
    elseif(is_object($a) && property_exists($a, $k) && $a->$k)
      return $a->$k;
    else
      return $default;
  }
}

function friendly_url($url) {
  return preg_replace(['/https?:\/\//','/\/$/'],'',$url);
}

function bs()
{
  static $pheanstalk;
  if(!isset($pheanstalk))
  {
    $pheanstalk = new Pheanstalk\Pheanstalk(Config::$beanstalkServer, Config::$beanstalkPort);
  }
  return $pheanstalk;
}

function get_timezone($lat, $lng) {
  try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://atlas.p3k.io/api/timezone?latitude='.$lat.'&longitude='.$lng);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $tz = @json_decode($response);
    if($tz)
      return new DateTimeZone($tz->timezone);
  } catch(Exception $e) {
    return null;
  }
  return null;
}

function download_file($url, $ext='jpg') {
  $filename = tempnam(dirname(__FILE__).'../tmp/', 'ig').'.'.$ext;
  $fp = fopen($filename, 'w+');
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FILE, $fp);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_exec($ch);
  curl_close($ch);
  fclose($fp);
  return $filename;  
}

function micropub_post($endpoint, $access_token, $params, $photo_filename=false, $video_filename=false) {

  $postfields = array(
    'h' => 'entry',
    'access_token' => $access_token
  );

  if(k($params, 'mp-type'))
    $postfields['mp-type'] = $params['mp-type'];
  if(k($params, 'content'))
    $postfields['content'] = $params['content'];
  if(k($params, 'category'))
    $postfields['category'] = $params['category'];
  if(k($params, 'place_name'))
    $postfields['place_name'] = $params['place_name'];
  if(k($params, 'place_url'))
    $postfields['place_url'] = $params['place_url'];
  if(k($params, 'place_icon_url'))
    $postfields['place_icon_url'] = $params['place_icon_url'];
  if(k($params, '4sq_sticker_url'))
    $postfields['4sq_sticker_url'] = $params['4sq_sticker_url'];
  if(k($params, 'location'))
    $postfields['location'] = $params['location'];
  if(k($params, 'published'))
    $postfields['published'] = $params['published'];
  if(k($params, 'syndication'))
    $postfields['syndication'] = $params['syndication'];
  if(k($params, 'name'))
    $postfields['name'] = $params['name'];

  $multipart = new p3k\Multipart();

  $multipart->addArray($postfields);

  if($photo_filename)
    $multipart->addFile('photo', $photo_filename, 'image/jpeg');

  if($video_filename)
    $multipart->addFile('video', $video_filename, 'video/mp4');

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $endpoint);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $access_token,
    'Content-Type: ' . $multipart->contentType()
  ));
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart->data());
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, true);
  $response = curl_exec($ch);
  $error = curl_error($ch);
  return array(
    'response' => $response,
    'error' => $error,
    'curlinfo' => curl_getinfo($ch)
  );
}

// Given an Foursquare photo object, return an h-entry array with all the necessary keys
function h_entry_from_checkin(&$user, &$checkin) {
  $entry = array(
    'mp-type' => null,
    'published' => null,
    'location' => null,
    'place_name' => null,
    'place_url' => null,
    'category' => array(),
    'content' => '',
    'syndication' => ''
  );
  //print_r($checkin);
  $entry['published'] = date('c', $checkin->createdAt);

  // Look up the timezone of the photo if location data is present
  if(property_exists($checkin, 'location') && $checkin->venue->location) {
    if($tz = get_timezone($checkin->venue->location->lat, $checkin->venue->location->lng)) {
      $d = DateTime::createFromFormat('U', $checkin->createdAt);
      $d->setTimeZone($tz);
      $entry['published'] = $d->format('c');
    }
  }

  if($checkin->venue->location)
    $entry['location'] = 'geo:' . $checkin->venue->location->lat . ',' . $checkin->venue->location->lng;

  if($checkin->venue->name)
    $entry['place_name'] = $checkin->venue->name;
  if($checkin->venue->id)
    $entry['place_url'] = 'https://foursquare.com/venue/'.$checkin->venue->id."?ref=".Config::$fsqClientID;

   $entry['mp-type'] = "checkin";

  // Add 4sq tags to the category array
  if(count($checkin->venue->categories) > 0)
    foreach ($checkin->venue->categories as $category) {
    	$entry['category'][] = $category->name;
    }
    //$entry['category'] = array_merge($entry['category'], $checkin->tags);

   $entry['category'][] = "checkin";
   $entry['category'][] = "foursquare";
   //$entry['category'][] = "4sq"; // city, street, plz:plz

  // Add person-tags to the category array
  /*
  if($checkin->users_in_photo) {
    foreach($checkin->users_in_photo as $tag) {
      // Fetch the user's website
      try {
        if($profile = FSQ\get_profile($user, $tag->user->id)) {
          if($profile->website)
            $entry['category'][] = $profile->website;
          else
            $entry['category'][] = 'https://foursquare.com/' . $profile->username;
          // $entry['category'][] = [
          //   'type' => ['h-card'],
          //   'properties' => [
          //     'name' => [$profile->full_name],
          //     'url' => [$profile->website],
          //     'photo' => [$profile->profile_picture]
          //   ],
          //   'value' => $profile->website
          // ];
        }
      } catch(Exception $e) {
        $entry['category'][] = 'https://foursquare.com/' . $tag->user->username;
      }
    }
  }
  */

  if(isset($checkin->shout))
    $entry['content'] = $checkin->shout;
  else
    $entry['content'] = "";
  	

  $entry['syndication'] = 'https://foursquare.com/forward/checkin/'.$checkin->id;

  return $entry;
}

