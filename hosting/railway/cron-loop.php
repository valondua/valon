<?php
/** Independent, serialized WordPress cron runner. Run as www-data. */
if (PHP_SAPI !== 'cli') exit(1);
$state = '/data/.valon-state-v1';
$lock = fopen($state . '/cron/worker.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) exit(0);
while (true) {
    $started = time();
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($state . '/wordpress/wp-cron.php'), $status);
    file_put_contents($state . '/cron/status.json', json_encode(['completed_utc'=>gmdate('c'), 'exit_code'=>$status]));
    sleep(max(1, 60 - (time() - $started)));
}
