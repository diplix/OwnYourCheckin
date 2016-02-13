<?php
chdir(dirname(__FILE__).'/..');
require 'vendor/autoload.php';
require 'lib/Savant.php';
require 'lib/config.php';
require 'lib/helpers.php';
require 'lib/markdown.php';
require 'lib/fsq.php';

$log = file_get_contents('scripts/worker.log');

$entries = explode('===============================================', $log);
echo count($entries);

foreach($entries as $entry) {
  if(preg_match('/\[object_id\] => (\d+)/', $entry, $match)) {
    $user_id = $match[1];

    echo "Foursquare user_id: $user_id\n";

    $user = ORM::for_table('users')->where('fsq_user_id', $user_id)->find_one();
    if($user) {

      if(preg_match('/Location: (.+)/', $entry, $match)) {
        $micropub_location = $match[1];
        echo "Found checkin: $micropub_location\n";

        $user->last_micropub_url = $micropub_location;
        $user->checkin_count = $user->checkin_count + 1;

        if(preg_match('/\[published\] => (.+)/', $entry, $match)) {
          $published = strtotime($match[1]);
          if($published > time() - (86400*7)) {
            $user->checkin_count_this_week = $user->checkin_count_this_week + 1;
          }
        }

        $user->save();
      }

    } else {
      echo "OwnYourCheckin account not found\n";
    }

  }
}

echo "\n";
