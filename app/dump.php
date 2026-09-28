<?php
$config = config('database.connections.mysql');
$cmd = sprintf(
    'mysqldump -u %s -p%s -h %s -P %s %s > storage/app/backup.sql',
    escapeshellarg($config['username']),
    escapeshellarg($config['password']),
    escapeshellarg($config['host']),
    escapeshellarg($config['port']),
    escapeshellarg($config['database'])
);
exec($cmd);
echo "Dumped to storage/app/backup.sql\n";
