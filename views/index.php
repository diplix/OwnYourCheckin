  <div class="jumbotron">
    <h2>#OwnYourCheckin</h2>
    <p>How does it work?</p>
    <ol>
      <li>Sign in with your domain</li>
      <li>Connect your Foursquare account</li>
      <li>When you post to Foursquare, the checkins will be sent to your site!</li>
    </ol>
    <p><a href="/signin" class="btn btn-primary btn-lg" role="button">Get Started &raquo;</a></p>
  </div>

  <div class="alert alert-success">
    <i><strong><?= $this->total_checkins ?></strong> checkins owned by <?= $this->total_users ?> users on their own sites and counting!</i>
  </div>

  <div class="row top-users">
    <h3>Top users this week</h3>
    <?php
    if(count($this->users) == 0):
    ?>

    <p>Nobody yet! The stats reset every Sunday.</p>

    <?php
    endif;

    foreach($this->users as $user):
    ?>
      <div class="col-xs-6 col-md-3">
        <div class="thumbnail">
          <a href="<?= $user->last_micropub_url ?>"><img src="<?= $user->last_fsq_img_url ?>"></a>
          <div class="caption">
            <p><a href="<?= $user->url ?>"><?= friendly_url($user->url) ?></a></p>
            <p>
              <?= $user->checkin_count_this_week ?> this week<br>
              <?= $user->checkin_count ?> total<br>
            </p>
          </div>
        </div>
      </div>
    <?php
    endforeach;
    ?>
  </div>
