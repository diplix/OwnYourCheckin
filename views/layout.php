<!doctype html>
<html lang="en">
  <head>
    <title><?= $this->title ?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="pingback" href="http://webmention.io/webmention?forward=http://<?=$_SERVER['SERVER_NAME']?>/webmention" />
    <link rel="webmention" href="http://<?=$_SERVER['SERVER_NAME']?>/webmention" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="/css/style.css">

    <script src="/js/jquery-1.7.1.min.js"></script>
  </head>

<body role="document">

<div class="navbar navbar-inverse navbar-fixed-top">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="/">OwnYourCheckin</a>
    </div>
    <div class="navbar-collapse collapse">
      <ul class="nav navbar-nav">
        <?php if(session('me')) { ?>
          <li><a href="/fsq">Foursquare</a></li>
        <?php } ?>
        <!-- <li><a href="/about">About</a></li> -->
        <!-- <li><a href="/contact">Contact</a></li> -->
      </ul>
      <?php if(session('me')) { ?>
        <ul class="nav navbar-nav navbar-right">
          <li><a href="/user?domain=<?= urlencode(session('me')) ?>"><?= session('me') ?></a></li>
          <li><a href="/signout">Sign Out</a></li>
        </ul>
      <?php } else if(property_exists($this, 'authorizing')) { ?>
        <ul class="nav navbar-right">
          <li class="navbar-text"><?= $this->authorizing ?></li>
        </ul>
      <?php } else { ?>
        <ul class="nav navbar-right" style="font-size: 8pt;">
          <li><a href="https://indieauth.com/setup">What's This?</a></li>
        </ul>
        <form action="/auth/start" method="get" class="navbar-form navbar-right">
          <input type="text" name="me" placeholder="yourdomain.com" class="form-control" />
          <button type="submit" class="btn">Sign In</button>
          <input type="hidden" name="redirect_uri" value="https://<?= $_SERVER['SERVER_NAME'] ?>/indieauth" />
        </form>
      <?php } ?>
    </div>
  </div>
</div>

<div class="page">

  <div class="container">
    <?= $this->fetch($this->page . '.php') ?>
  </div>

  <div class="footer">
    <p class="credits">&copy; <?=date('Y')?> by <a href="http://aaronparecki.com">Aaron Parecki</a> and <a href="http://wirres.net">Felix Schwenzel</a>.
      This code is <a href="https://github.com/diplix/OwnYourCheckin">open source</a>.
      Feel free to send a pull request, or <a href="https://github.com/diplix/OwnYourCheckin/issues">file an issue</a>.</p>
  </div>
</div>

</body>
</html>
