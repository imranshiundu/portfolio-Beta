<?php
/**
 * Database Configuration for ZoneVS
 */

return [
    'driver' => 'mysql',
    'host' => 'd141608.mysql.zonevs.eu',
    'database' => 'd141608sd612913',
    'username' => 'd141608sa559041',
    'password' => '#Imr@n2006',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
?>