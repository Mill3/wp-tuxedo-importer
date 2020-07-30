<?php

namespace TDP_Tuxedo\Tuxedo;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Tuxedo_API
{

    /**
     * The logger
     *
     * @since  0.0.1
     * @access protected
     */
    protected $log;

    /**
     * The logger name
     *
     * @since  0.0.1
     * @access protected
     */
    protected $logname;

    /**
     * The HTTP client
     *
     * @since  0.0.1
     * @access protected
     */
    protected $http_client;

    /**
     * Defaults HTTP headers for curl requests
     *
     * @since  0.0.1
     * @access protected
     */
    protected $http_headers;

    /**
     * Tuxedo base URI
     *
     * @since  0.0.1
     * @access protected
     */
    protected $tuxedo_api_base_uri;

    /**
     * Tuxedo account name
     *
     * @since  0.0.1
     * @access protected
     */
    protected $tuxedo_api_account_name;

    /**
     * Tuxedo username
     *
     * @since  0.0.1
     * @access protected
     */
    protected $tuxedo_api_username;

    /**
     * Tuxedo password
     *
     * @since  0.0.1
     * @access protected
     */
    protected $tuxedo_api_password;

    /**
     * Construct method
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        $this->logname = 'tuxedo-run';
        $this->tuxedo_api_base_uri = TUXEDO_BASE_URI;
        $this->tuxedo_api_account_name = TUXEDO_ACCOUNT_NAME;
        $this->tuxedo_api_username = TUXEDO_USERNAME;
        $this->tuxedo_api_password = TUXEDO_PASSWORD;

        // create a logger
        $this->log = new Logger($this->logname);
        $this->log->pushHandler(new StreamHandler(__DIR__."/logs/{$this->logname}.log", Logger::DEBUG));

        // create client
        $this->http_client = new \GuzzleHttp\Client(
            [
                'base_uri' => $this->tuxedo_api_base_uri,
                'timeout'  => 2.0
            ]
        );

        // default http headers for client requests
        $this->http_headers = [
            'accept' => 'application/json',
            'Content-type' => 'application/json',
        ];
    }


    public function run()
    {
        $this->import_tuxedo_events();
    }

    public function import_tuxedo_events()
    {
        $importer = new \TDP_Tuxedo\Tuxedo\Events($parent_instance = $this);
        $importer->run();
    }
}
