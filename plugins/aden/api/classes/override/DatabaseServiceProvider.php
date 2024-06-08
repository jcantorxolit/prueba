<?php

namespace AdeN\Api\Classes\Override;


use October\Rain\Database\DatabaseServiceProvider as DatabaseServiceProviderBase;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Grammars\MySqlGrammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;

class DatabaseServiceProvider extends DatabaseServiceProviderBase
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->app->singleton('db', function ($app) {
            //return new DatabaseManager($app, $app['db.factory']);
            $dbm = new DatabaseManager($app, $app['db.factory']);
            //Extend to include the custom connection (MySql in this example)
            $dbm->extend('mysql', function ($config, $name) use ($app) {
                //Create default connection from factory
                $connection = $app['db.factory']->make($config, $name);
                //Instantiate our connection with the default connection data
                $newConnection = new MySqlConnection(
                    $connection->getPdo(),
                    $connection->getDatabaseName(),
                    $connection->getTablePrefix(),
                    $config
                );
                //Set the appropriate grammar object
                $newConnection->setQueryGrammar(new QueryGrammar());
                $newConnection->setSchemaGrammar(new SchemaGrammar());
                return $newConnection;
            });
            return $dbm;
        });
    }
}
