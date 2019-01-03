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

$beevrr_cron = new beevr_cron();
$beevrr_cron->conn($server, $user, $pw, $db);

if($argv[1] === 'clear-tables')
{
    $beevrr_cron->clear_tables();
}
else if($argv[1] === 'drop-tables')
{
    $beevrr_cron->drop_tables();
}
else
{
    $beevrr_cron->update();
}
