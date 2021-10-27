<?php

namespace WP_Tuxedo\Tuxedo;

use WP_Query;
use WP_Tuxedo\Tuxedo;
use WP_Tuxedo\Wp;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;


class Tuxedo_API_Shows extends \WP_Tuxedo\Tuxedo\Tuxedo_API
{

    public function run()
    {
        return $this->get_shows();
    }

    public function filter_get_shows()
    {
        return $this->get_shows();
    }

    private function get_shows()
    {

        // stop here if no settings saved
        if(!$this->tuxedo_api_account_name || !$this->tuxedo_api_username || !$this->tuxedo_api_password) {
            do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', 'Tuxedo credentials not found in WP installation settings', 'error');
            return;
        };

        $body = json_encode(
            [
                'accountName' => $this->tuxedo_api_account_name,
                'username' => $this->tuxedo_api_username,
                'password' => $this->tuxedo_api_password,
            ]
        );

        // First promise is for authentication
        $request = new Request('POST', 'v1/authentication', $this->http_headers, $body);
        $promise = $this->http_client->sendAsync($request)->then(
            function ($res) {
                // stop here anything not 200 or 201
                if ($res->getStatusCode() > 201) {
                    return null;
                }

                // $body = $this->parse($res);
                $parsed_body = json_decode($res->getBody());
                $bearer = $parsed_body->jwt;
                $header = [
                    'accept' => 'application/json',
                    'Content-type' => 'application/json',
                    'Authorization' => "Bearer $bearer",
                ];

                $request_shows = new Request('GET', 'v1/shows', $header);
                $promise_shows = $this->http_client->sendAsync($request_shows)->then(
                    function($res_shows) {
                        $data = [];
                        $items = json_decode($res_shows->getBody(), true);
                        foreach ($items as $key => $item) {
                            $data[] = array(
                                'id' => $item['id'],
                                'label' => $item['title']['french'],
                                'tuxedoUrl' => $item['tuxedoUrl'],
                            );
                        }
                        return $data;
                    },
                    function (RequestException $e) {
                        do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', $e->getMessage(), 'error');
                    }
                );

                // wait for shows promise
                return $promise_shows->wait();

            },
            function (RequestException $e) {
                do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', $e->getMessage(), 'error');
            }
        );

        // make sure we wait for promise to complete
        $response = $promise->wait();

        return $response;
    }



}
