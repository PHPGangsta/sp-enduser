<?php

/*
 * Don't invoke directly. Run as:
 * php cron.php.txt cleanmessagelog
 */

if (!isset($_SERVER['argc']))
        die('this file can only be run from command line');

define('BASE', dirname(__FILE__).'/..');
require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

$max = 5000000;
$chunks = 1000;

foreach ($settings->getMessagelogTables() as $table)
{
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare('SELECT id FROM '.$table.' ORDER BY id DESC LIMIT 1;');
	$statement->execute();
	$item = $statement->fetchObject();
	$maxId = $item->id;
	$keepId = $maxId - $max;

	$statement = $dbh->prepare('SELECT id FROM '.$table.' ORDER BY id ASC LIMIT 1;');
	$statement->execute();
	$item = $statement->fetchObject();
	$minId = $item->id;

	if ($keepId <= 0 || $keepId <= $minId)
	{
		echo "$table: range $minId to $maxId has less than $max items (skip)\n";
		continue;
	}

	echo "$table: deleting rows from $minId < $keepId (keeping $keepId -> $maxId)\n";

	$fromId = $minId;
	while ($toId != $keepId)
	{
		$toId = min($fromId + $chunks, $keepId);

		echo "$table: deleting rows from $fromId < $toId\n";

		$statement = $dbh->prepare('DELETE FROM '.$table.' WHERE id >= :f AND id < :t;');
		$statement->execute(array(':f' => $fromId, ':t' => $toId));
		$deleted = $statement->rowCount();
		echo "$table: Chunk $i deleted ".$deleted."\n";

		$fromId = $toId;
	}

	echo "$table: Done\n";
	break;
}
