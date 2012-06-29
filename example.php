<?php
/**
 * Basic example of how to use Prometheus. Don't forget to copy
 * the config file!
 *
 */
error_reporting(-1);
date_default_timezone_set('America/New_York');
ini_set('error_log', realpath('logs/error.log'));

// Prometheus uses Composer (http://getcomposer.org) to manage its
// dependencies. If you use it, it will automatically generate this
// autoloader file.
require 'vendor/autoload.php';

$cfgPath = realpath('src/Prometheus/Config/Config.php');

if (!is_readable($cfgPath)) {
    die(sprintf('Config not found in "%s"', $cfgPath));
}

require $cfgPath;

$p = new Prometheus\Prometheus();

// Pass log path to enable logging
$p->enableLogging(realpath('./logs/Prometheus.log'));  

// Pass backup path to enable database backups
$p->enableDatabaseBackup(getcwd());

// Pass an array of emails to receive mail when upgrades are completed
// Note: this uses a gmail account to send mail. Set MAIL_USER and MAIL_PASSWORD
// in the config.
$p->setReportRecipients(array('bill@prgmrbill.com'));

// Pass an array of directories which contain PHP files (updates)
$p->run(array(sprintf('%s/src/Prometheus/Test/Fixture/Updates', __DIR__)));


