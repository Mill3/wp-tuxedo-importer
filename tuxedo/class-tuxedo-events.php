<?php

namespace WP_Tuxedo\Tuxedo;

use WP_Query;
use WP_Tuxedo\Tuxedo;
use WP_Tuxedo\Wp;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;


class Tuxedo_API_Events extends \WP_Tuxedo\Tuxedo\Tuxedo_API
{
    const STATS_OPTION = 'wp_tuxedo_last_import_stats';

    /** @var array */
    private array $stats = [];

    /** @var float */
    private float $start_time = 0.0;

    private function save_stats(): void
    {
        update_option(self::STATS_OPTION, $this->stats, false);
    }

    public function run()
    {
        do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', 'Starting show_date importation..', 'notice');

        $this->stats = [
            'run_at'              => (new \DateTime('now', new \DateTimeZone('America/Toronto')))->format('Y-m-d H:i:s'),
            'duration_seconds'    => 0,
            'fetched'             => 0,
            'created'             => 0,
            'updated'             => 0,
            'skipped_no_show'     => 0,
            'skipped_past'        => 0,
            'skipped_date_error'  => 0,
            'errors'              => 0,
        ];

        $this->start_time = microtime(true);
        $this->auth();
    }

    private function auth()
    {
        $body = json_encode(
            [
                'accountName' => $this->tuxedo_api_account_name,
                'username' => $this->tuxedo_api_username,
                'password' => $this->tuxedo_api_password,
            ]
        );

        $request = new Request('POST', 'v1/authentication', $this->http_headers, $body);
        $promise = $this->http_client->sendAsync($request);

        $promise->then(
            function (ResponseInterface $res) {
                // stop here anything not 200 or 201
                if ($res->getStatusCode() > 201) {
                    return;
                }

                do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', 'Authenticated to Tuxedo', 'notice');

                // parse reponse
                $this->parse($res);
            },
            function (RequestException $e) {
                do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', $e->getMessage(), 'error');
            }
        );

        // make sure we wait for promise to complete
        $promise->wait();
    }

    private function parse($res)
    {
        $parsed_body = json_decode($res->getBody());
        if (!$parsed_body || empty($parsed_body->jwt)) {
            do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', 'Tuxedo auth response invalid or missing JWT', 'error');
            return;
        }
        $bearer = $parsed_body->jwt;

        $header = [
            'accept' => 'application/json',
            'Content-type' => 'application/json',
            'Authorization' => "Bearer $bearer",
        ];

        $request = new Request('GET', 'v1/events', $header);
        $promise = $this->http_client->sendAsync($request);

        $promise->then(
            function (ResponseInterface $res) {
                $items = json_decode($res->getBody());
                if (!$items) {
                    do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', 'Tuxedo events response empty or invalid JSON', 'error');
                    $this->stats['errors']++;
                    $this->save_stats();
                    return;
                }

                $this->stats['fetched'] = count($items);
                do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', 'Fetched ' . $this->stats['fetched'] . ' Tuxedo events', 'notice');

                foreach ($items as $item) {
                    try {
                        // do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', 'Processing Tuxedo event with ID: ' . $item->id . ' (' . $item->tuxedoUrl . ')', 'notice');
                        $show_date = new \WP_Tuxedo\Wp\ShowDate($item);
                        $result    = $show_date->run();
                        $this->stats[$result] = ($this->stats[$result] ?? 0) + 1;
                    } catch (\Throwable $e) {
                        $this->stats['errors']++;
                        do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', 'Error processing Tuxedo event with ID: ' . (print_r($item, true)) . '. Error: ' . $e->getMessage(), 'error');
                    }
                }

                $this->stats['duration_seconds'] = round(microtime(true) - $this->start_time, 2);
                $this->save_stats();

                do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event',
                    sprintf(
                        'Import complete in %ss — fetched: %d, created: %d, updated: %d, skipped (no show): %d, skipped (past): %d, skipped (date error): %d, errors: %d',
                        $this->stats['duration_seconds'],
                        $this->stats['fetched'],
                        $this->stats['created'],
                        $this->stats['updated'],
                        $this->stats['skipped_no_show'],
                        $this->stats['skipped_past'],
                        $this->stats['skipped_date_error'],
                        $this->stats['errors']
                    ),
                    'notice'
                );
            },
            function (RequestException $e) {
                do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', $e->getMessage(), 'error');
            }
        );

        $promise->wait();
    }

}
