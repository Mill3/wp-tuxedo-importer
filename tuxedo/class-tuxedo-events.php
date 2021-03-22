<?php

namespace TDP_Tuxedo\Tuxedo;

use WP_Query;
use TDP_Tuxedo\Tuxedo;
use TDP_Tuxedo\Wp;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;


class Events extends \TDP_Tuxedo\Tuxedo\Tuxedo_API
{
    protected $parent_instance;

    public function __construct($parent_instance = null)
    {
        $this->parent_instance = $parent_instance;
    }

    public function run()
    {
        // log start
        $this->parent_instance->log->info('Starting show_date importation..');

        // start with auth
        $this->auth();
    }

    private function auth()
    {
        $body = json_encode(
            [
                'accountName' => $this->parent_instance->tuxedo_api_account_name,
                'username' => $this->parent_instance->tuxedo_api_username,
                'password' => $this->parent_instance->tuxedo_api_password,
            ]
        );

        $request = new Request('POST', 'v1/authentication', $this->parent_instance->http_headers, $body);
        $promise = $this->parent_instance->http_client->sendAsync($request);

        $promise->then(
            function (ResponseInterface $res) {
                // stop here anything not 200 or 201
                if ($res->getStatusCode() > 201) {
                    return;
                }

                $this->parent_instance->log->info('Authenticated!');

                // parse reponse
                $this->parse($res);
            },
            function (RequestException $e) {
                $this->parent_instance->log->info($e->getMessage());
            }
        );

        // make sure we wait for promise to complete
        $promise->wait();
    }

    private function parse($res)
    {
        $parsed_body = json_decode($res->getBody());
        $bearer = $parsed_body->jwt;

        $header = [
            'accept' => 'application/json',
            'Content-type' => 'application/json',
            'Authorization' => "Bearer $bearer",
        ];

        $request = new Request('GET', 'v1/events', $header);
        $promise = $this->parent_instance->http_client->sendAsync($request);

        $promise->then(
            function (ResponseInterface $res) {
                $items = json_decode($res->getBody());
                foreach ($items as $key => $item) {
                    $show_date = new \TDP_Tuxedo\Wp\ShowDate($item, $this->parent_instance->log);
                    $show_date->run();
                }
            }
        );

        $promise->wait();
    }


}
