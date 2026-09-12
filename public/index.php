<?php
header('Content-Type: text/plain');
echo "artifact-release staging example\n";
echo "APP_ENV=" . getenv('APP_ENV') . "\n";
echo "DB_HOST=" . getenv('DB_HOST') . "\n";
echo "MAIL_HOST=" . getenv('MAIL_HOST') . "\n";
