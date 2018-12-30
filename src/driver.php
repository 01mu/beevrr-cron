<?php
/*
 * beevrr-cron
 * github.com/01mu
 */

include_once 'beevrr-cron.php';

$server = '';
$user = '';
$pw = '';
$db = '';

$beevr_cron = new BeevrCron();

$beevr_cron->conn($server, $user, $pw, $db);
$beevr_cron->update();
