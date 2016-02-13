<?php
class Config {
  public static $hostname = 'ownyourcheckin.dev';
  public static $ssl = false;
  public static $gaid = '';

  public static $fsqClientID = '';
  public static $fsqClientSecret = '';
  public static $fsqPushSecret = '';

  public static $beanstalkServer = '127.0.0.1';
  public static $beanstalkPort = 11300;

  public static $dbHost = '127.0.0.1';
  public static $dbName = 'ownyourcheckin';
  public static $dbUsername = 'ownyourcheckin';
  public static $dbPassword = '';

  public static function fsqRedirectURI() {
    return 'http'.(self::$ssl ? 's' : '').'://'.Config::$hostname.'/auth/fsq-callback';
  }
}

