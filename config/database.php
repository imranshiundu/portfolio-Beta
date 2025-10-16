<?php
/**
 * Database Configuration - Updated for ZoneVS
 */

return [
    'driver' => 'mysql',
    'host' => 'd141608.mysql.zonevs.eu', // Your ZoneVS MySQL server
    'database' => 'd141608sd612913', // Your database name
    'username' => 'd141608sa559041', // Your MySQL username
    'password' => '#Imr@n2006', //
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