<?php
require_once('Task.php');

$task = new Task($argv[1], $argv[2]);
var_dump($task->performTask());