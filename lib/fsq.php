<?php
namespace FSQ;

class AccessTokenException extends \Exception {
}

function get_latest_checkin(&$user, $since=false, $limit=1) {
  $params = array(
    'oauth_token' => $user->fsq_access_token
  );

  if($limit !== false)
    $params['limit'] = $limit;

  if($since !== false)
    $params['afterTimestamp'] = $since;

  $params['v'] = 20160212;

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.foursquare.com/v2/users/self/checkins?'.http_build_query($params));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  $data = @json_decode($response);
  if($data && is_object($data)) {
    if(property_exists($data->response->checkins, 'items')) {
      return $data->response->checkins->items;
    } elseif(property_exists($data, 'meta') && property_exists($data->meta, 'error_message')) {
      throw new AccessTokenException($data->meta->error_message);
    } else {
      return null;
    }
  } else {
    return null;
  }
}

function get_profile(&$user, $fsq_user_id) {
// todo
  $params = array(
    'access_token' => $user->fsq_access_token
  );

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.instagram.com/v1/users/'.$fsq_user_id.'?'.http_build_query($params));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  $data = @json_decode($response);
  if($data && is_object($data)) {
    if(property_exists($data, 'data')) {
      return $data->data;
    } elseif(property_exists($data, 'meta') && property_exists($data->meta, 'error_message')) {
      throw new AccessTokenException($data->meta->error_message);
    } else {
      return null;
    }
  } else {
    return null;
  }  
}

function user_is_public(&$user) {
// to delete (no unpublic users @ foursquare)
  $params = array(
    'access_token' => $user->fsq_access_token
  );

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.instagram.com/v1/users/'.$user->fsq_user_id.'/relationship?'.http_build_query($params));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  $data = @json_decode($response);
  if($data && is_object($data)) {
    if(property_exists($data, 'data') && property_exists($data->data, 'target_user_is_private')) {
      return $data->data->target_user_is_private != 1;
    } elseif(property_exists($data, 'meta') && property_exists($data->meta, 'error_message')) {
      throw new AccessTokenException($data->meta->error_message);
    } else {
      return null;
    }
  } else {
    return null;
  }  
}

function get_checkin(&$user, $checkin_id) {
  $params = array(
    'oauth_token' => $user->fsq_access_token,
    'v' => '20160212'
  );

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.foursquare.com/v2/checkins/'.$checkin_id.'?'.http_build_query($params));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  $data = @json_decode($response);
  if($data && is_object($data)) {
	return $data->response->checkin;
  } else {
    return null;
  }
}

function delete_comment(&$user, $media_id, $comment_id) {
  $params = array(
    'access_token' => $user->fsq_access_token
  );

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.instagram.com/v1/media/'.$media_id.'/comments/'.$comment_id.'?'.http_build_query($params));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
  $response = curl_exec($ch);
  $data = @json_decode($response);
  if($data)
    return $data->data;
  else
    return null;
}

function add_comment(&$user, $media_id, $text) {
  $params = array(
    'access_token' => $user->fsq_access_token,
    'text' => $text
  );

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.instagram.com/v1/media/'.$media_id.'/comments?'.http_build_query($params));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
  $response = curl_exec($ch);
  $data = @json_decode($response);
  if($data)
    return $data->data;
  else
    return array('error'=>$response);
}

