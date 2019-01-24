<?php

/**
 * Script para la generaci�n de CAPTCHAS
 *
 * @author  Jose Rodriguez <jose.rodriguez@exec.cl>
 * @license GPLv3
 * @link    http://code.google.com/p/cool-php-captcha
 * @package captcha
 * @version 0.3
 *
 */
if (!isset($_SESSION)) {
	session_start();
	session_regenerate_id(TRUE);
}

// share the session name
require_once dirname(dirname(dirname(__FILE__))) . './config.php';

require_once 'captcha.class.php';

$captcha = new SimpleCaptcha();

// OPTIONAL Change configuration...
//$captcha->wordsFile = 'words/es.php';
$captcha->session_var = $captcha_session_name;
$captcha->imageFormat = 'png';
//$captcha->scale = 3;
//$captcha->blur = true;
//$captcha->resourcesPath = "/var/cool-php-captcha/resources";
//
// OPTIONAL Simple autodetect language example
/*
  if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
  $langs = array('en', 'es');
  $lang  = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
  if (in_array($lang, $langs)) {
  $captcha->wordsFile = "words/$lang.php";
  }
  }
 */

// Image generation
$captcha->CreateImage();

?>
