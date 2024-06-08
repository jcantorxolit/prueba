<?php
//note: Please copy this file and rename it to convention .env.{environment}.php, where {environment} could be development or staging. 
//      For production just rename to .env.php
//file: /.env.development.php
// return the configuration for the 'development' environment
return array(
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'DB_NAME', // specify database name 
    'db_user' => 'DB_USER', // specify database username 
    'db_pass' => 'DB_PASS', // specify database password

    /*
    |--------------------------------------------------------------------------
    | Debug App
    |--------------------------------------------------------------------------
    |
    | Specifies if the app is in debug mode
    |
    */
    'debug' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Root Base Dir
    |--------------------------------------------------------------------------
    |
    | Specifies the URI prefix used for accessing base in angular
    |
    */
    'rootUri' => '/sylogiWeb/',

    /*
    |--------------------------------------------------------------------------
    | Site Url
    |--------------------------------------------------------------------------
    |
    | Specifies the URI prefix used for accessing site url services (dont use / on start)
    |
    */
    'urlSite' => 'https://sylogisoftware.com',
    
    /*
    |--------------------------------------------------------------------------
    | Instance
    |--------------------------------------------------------------------------
    |
    | Specifies instance of the app
    |
    */
    'instance' => 'sylogi',

    /*
    |--------------------------------------------------------------------------
    | App Name
    |--------------------------------------------------------------------------
    |
    | Specifies the name (instance) of the app
    |
    */
    'appName' => 'SYLOGI',    

    /*
    |--------------------------------------------------------------------------
    | Proxy Enabled
    |--------------------------------------------------------------------------
    |
    | Specifies if proxy is enable
    |
    */
    'proxyEnabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Proxy Url
    |--------------------------------------------------------------------------
    |
    | Specifies the URI used for accessing proxy feature
    |
    */
    'proxyUrl' => "https://api.domain.com/",
    
    /*
    |--------------------------------------------------------------------------
    | Session Cookie
    |--------------------------------------------------------------------------
    |
    |  Here you may change the name of the cookie used to identify a session
    | instance by ID
    |
    */
    'sessionCookie' => "wg_sylogi",
        
    /*
    |--------------------------------------------------------------------------
    | Session Life Time
    |--------------------------------------------------------------------------
    |
    |  Here you may change the tiem session will be alive
    | 
    |
    */
    'sessionLifeTime' => 60,  
    
    
    /*
    |--------------------------------------------------------------------------
    | Cache Driver
    |--------------------------------------------------------------------------
    |
    |  Supported: "file", "database", "apc", "memcached", "redis", "array"
    | 
    |
    */
    'cacheDriver' => "file", 
    
    
    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    |  Here you may specify a value to get prefixed to all our keys so we can avoid collisions.
    | 
    |
    */
    'cachePrefix' => "sylogi", 
    
    
    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    |  Here you may change the name of the cookie used to identify a session
    | instance by ID
    |
    */
    'sessionDomain' => null,


    /*
    |--------------------------------------------------------------------------
    | Session Cookie Httponly
    |--------------------------------------------------------------------------
    |
    |  Here you may set if cookie is for http only
    |
    */
    'sessionHttponly' => true,    


    /*
    |--------------------------------------------------------------------------
    | Session Cookie Secure
    |--------------------------------------------------------------------------
    |
    |  Here you may set if the cookie is secure
    | 
    |
    */
    'sessionSecure' => true, 
    
    /*
    |--------------------------------------------------------------------------
    | Redis Host
    |--------------------------------------------------------------------------
    |
    |  Here you may specify the host to connect to redis.
    | 
    |
    */
    'redisHost' => "127.0.0.1", 
    
    
    /*
    |--------------------------------------------------------------------------
    | Redis Database
    |--------------------------------------------------------------------------
    |
    |  Here you may specify the database name for redis.
    | 
    |
    */
    'redisDatabase' => "waygroup_soft", 


    /*
    |--------------------------------------------------------------------------
    | Redis Port
    |--------------------------------------------------------------------------
    |
    |  Here you may specify the port to connect to redis.
    | 
    |
    */
    'redisPort' => 6379, 
    
    
    /*
    |--------------------------------------------------------------------------
    | snappy pdf path
    |--------------------------------------------------------------------------
    |
    | Specifies the path for the wkhtmltopdf bin
    |
    */
    'wkhtmltopdf' => base_path('vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64'),


    /*
    |--------------------------------------------------------------------------
    | snappy image path
    |--------------------------------------------------------------------------
    |
    | Specifies the path for the wkhtmltoimage bin
    |
    */
    'wkhtmltoimage' => base_path('vendor/h4cc/wkhtmltoimage-amd64/bin/wkhtmltoimage-amd64'),      
);