<?php
class Config {
  public static $hostname = 'ownyourcheckin.dev';
  public static $ssl = false;
  public static $gaid = '';

  public static $4sqClientID = '';
  public static $4sqClientSecret = '';

  public static $beanstalkServer = '127.0.0.1';
  public static $beanstalkPort = 11300;

  public static $dbHost = '127.0.0.1';
  public static $dbName = 'ownyourcheckin';
  public static $dbUsername = 'ownyourcheckin';
  public static $dbPassword = '';

  public static function 4sqRedirectURI() {
    return 'http'.(self::$ssl ? 's' : '').'://'.Config::$hostname.'/auth/4sq-callback';
  }
}

