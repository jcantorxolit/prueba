<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => envi('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => envi('DB_DATABASE', 'storage/database.sqlite'),
            'prefix'   => '',
        ],

        'mysql' => [
            'driver'     => 'mysql',
            'engine'     => 'InnoDB',
            'host' => envi('DB_HOST', 'localhost'),
            'port' => envi('DB_PORT', 3306),
            'database' => envi('DB_DATABASE', 'waygroup_soft_migration'),
            'username' => envi('DB_USERNAME', ''),
            'password' => envi('DB_PASSWORD', ''),
            'charset'    => 'utf8mb4',
            'collation'  => 'utf8mb4_unicode_ci',
            'prefix'     => '',
            'varcharmax' => 191,
            'strict' => false,
        ],

        'pgsql' => [
            'driver'   => 'pgsql',
            'host' => envi('DB_HOST', 'localhost'),
            'port' => envi('DB_PORT', 5432),
            'database' => envi('DB_DATABASE', 'database'),
            'username' => envi('DB_USERNAME', ''),
            'password' => envi('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
        ],

        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host' => envi('DB_HOST', 'localhost'),
            'port' => envi('DB_PORT', 5432),
            'database' => envi('DB_DATABASE', 'database'),
            'username' => envi('DB_USERNAME', ''),
            'password' => envi('DB_PASSWORD', ''),
            'prefix'   => '',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk have not actually be run in the databases.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'cluster' => false,

        'default' => [
            'host' => envi('REDIS_HOST', '127.0.0.1'),
            'password' => envi('REDIS_PASSWORD', null),
            'port' => envi('REDIS_PORT', 6379),
            'database' => 0,
        ],

        'session' => [
            'host' => envi('REDIS_HOST', '127.0.0.1'),
            'password' => envi('REDIS_PASSWORD', null),
            'port' => envi('REDIS_PORT', 6379),
            'database' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Use DB configuration for testing
    |--------------------------------------------------------------------------
    |
    | When running plugin tests OctoberCMS by default uses SQLite in memory.
    | You can override this behavior by setting `useConfigForTesting` to true.
    |
    | After that OctoberCMS will take DB parameters from the config.
    | If file `/config/testing/database.php` exists, config will be read from it,
    | but remember that when not specified it will use parameters specified in
    | `/config/database.php`.
    |
    */

    'useConfigForTesting' => envi('DB_USE_CONFIG_FOR_TESTING', false),
];
