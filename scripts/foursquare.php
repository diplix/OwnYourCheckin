<?php
chdir(dirname(__FILE__).'/..');
require 'vendor/autoload.php';
require 'lib/Savant.php';
require 'lib/config.php';
require 'lib/helpers.php';
require 'lib/markdown.php';
require 'lib/fsq.php';

echo "Watching tube: " . Config::$hostname . "-worker\n";
bs()->watch(Config::$hostname.'-worker')->ignore('default');

if(isset($pcntl_continue)) {

  while($pcntl_continue)
  {
    if(($job=bs()->reserve(2)) == FALSE)
      continue;

    process_job($job);
  } // while true

  echo "\nBye from pid " . posix_getpid() . "!\n";

} else {
  if(($job=bs()->reserve())) {
    process_job($job);
  }  
}


function process_job(&$jobData) {
  $data = json_decode($jobData->getData());

  //if(!is_array($data)) {
  if(!is_object($data)) {
    echo "Found bad job:\n";
    print_r($data);
    echo "\n";
    bs()->delete($jobData);
    continue;
  }

  echo "===============================================\n";
  echo date('Y-m-d H:i:s')."\n";
  echo "# Beginning job\n";
  print_r($data);
  $job = $data;

    // Get the foursquare access token for this user ID
    $user = ORM::for_table('users')->where('fsq_user_id', $job->user->id)->find_one();

    if($user) {
      echo "User: ".$user->url."\n";

      if($user->micropub_success) {
        // checkin data is included in the ping ($job), just the photos need to be fetched separatly

        $timestamp = $job->createdAt;
        $entry = h_entry_from_checkin($user, $job);

        // fetch photo
        // photos don’t show up in the api instantaneous, so lets wait half a minute
        sleep(30);

        if($checkin = FSQ\get_checkin($user, $job->id)) {
          // remove!
          print_r($checkin);

          $filename = false;
          if ( count($checkin->photos->items) > 0 ) {
          	$photo_url = $checkin->photos->items[0]->prefix.'original'.$checkin->photos->items[0]->suffix;
	        // Download photo to a temp folder
          	echo "Downloading photo...\n";
          	$filename = download_file($photo_url);
          }

           // i think foursquare does not send video?
           $video_filename = false;
          /*
          if(property_exists($checkin, 'videos')) {
            $video_url = $checkin->videos->standard_resolution->url;
            echo "Downloading video...\n";
            $video_filename = download_file($video_url,'mp4');
          } else {
            $video_filename = false;
          }
          */
			if($checkin->venue->categories[0]->icon)
				$entry['place_icon_url'] = $checkin->venue->categories[0]->icon->prefix."bg_120".$checkin->venue->categories[0]->icon->suffix;
			if($checkin->sticker->image)
				$entry['4sq_sticker_url'] = $checkin->sticker->image->prefix."50x50".$checkin->sticker->image->name;
          
        }
        // Send the checkin to the micropub endpoint
        echo "Sending checkin" . ($video_filename ? " and video" : "") . " to micropub endpoint: ".$user->micropub_endpoint."\n";

        // Collapse category to a comma-separated list if they haven't upgraded yet
        if($user->send_category_as_array != 1) {
        	if($entry['category'] && is_array($entry['category']) && count($entry['category'])) {
              $entry['category'] = implode(',', $entry['category']);
            }
          }

        print_r($entry);
        echo "\n";
        $response = micropub_post($user->micropub_endpoint, $user->micropub_access_token, $entry, $filename, $video_filename);
        print_r($response);
        echo "\n";
        unlink($filename);
        if($video_filename)
          unlink($video_filename);

        // Store the request and response from the micropub endpoint in the DB so it can be displayed to the user
        $user->last_micropub_response = json_encode($response);
        $user->last_fsq_checkin = $checkin->id;
        $user->last_checkin_date = date('Y-m-d H:i:s');

        if($response && preg_match('/Location: (.+)/', $response['response'], $match)) {
          $user->last_micropub_url = $match[1];
          $user->last_fsq_img_url = $photo_url;
          $user->checkin_count = $user->checkin_count + 1;
          $user->checkin_count_this_week = $user->checkin_count_this_week + 1;
        } else {
          // Their micropub endpoint didn't return a location, notify them there's a problem somehow
        }

        $user->save();

        /*
        // Add the link to the photo caption

        $comment_text = '';

        if($photo->caption && $photo->caption->id) {
          $comment_id = $photo->caption->id;
          $comment_text = $photo->caption->text;

          // Now delete the comment (caption) if there is one
          $result = IG\delete_comment($user, $media_id, $comment_id);
          print_r($result);
        }

        // Re-add the caption with the citation 
        $canonical = 'http://aaron.pk/xxxxx';
        $comment_text .= ' ('.$canonical.')';
        $result = IG\add_comment($user, $media_id, $comment_text);
        print_r($result);
        */

      } else {
        echo "This user has not successfully completed a test micropub post yet\n";
      }
    } else {
      echo "No user account found for Foursquare user ".$job->object_id."\n";
    }

  
  echo "# Job Complete\n-----------------------------------------------\n\n";
  bs()->delete($jobData);
}


