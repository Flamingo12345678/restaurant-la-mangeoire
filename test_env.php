<?php
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->safeLoad();
}

echo "MYSQLHOST=" . getenv('MYSQLHOST') . PHP_EOL;
echo "MYSQLDATABASE=" . getenv('MYSQLDATABASE') . PHP_EOL;
echo "MYSQLUSER=" . getenv('MYSQLUSER') . PHP_EOL;
echo "MYSQLPASSWORD=" . getenv('MYSQLPASSWORD') . PHP_EOL;
echo "MYSQLPORT=" . getenv('MYSQLPORT') . PHP_EOL;
echo "RAILWAY_ENVIRONMENT=" . getenv('RAILWAY_ENVIRONMENT') . PHP_EOL;
