<?php
chdir(dirname(__FILE__).'/..');
require 'vendor/autoload.php';
require 'lib/Savant.php';
require 'lib/config.php';
require 'lib/helpers.php';
require 'lib/markdown.php';
require 'lib/fsq.php';

$users = ORM::for_table('users')
  ->where_not_null('last_fsq_checkin')
  ->where_not_null('last_micropub_url')
  ->where_null('last_fsq_img_url')
  ->find_many();
foreach($users as $user) {
  echo $user->url . "\n";
  try {
    $checkin = FSQ\get_checkin($user, $user->last_fsq_checkin);
    $public = FSQ\user_is_public($user);

    $user->fsq_public = $public ? 1 : 0;
    if($checkin) {
      $user->last_fsq_img_url = $checkin->images->standard_resolution->url;
      echo $checkin->images->standard_resolution->url . "\n";
    } else {
      echo "no checkin found\n";
    }
    $user->save();
  } catch(Exception $e) {
    echo "Invalid Foursquare token\n";
  }
}

