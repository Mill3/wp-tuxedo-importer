<?php

namespace TDP_Tuxedo\Wp;

use WP_Query;

class ShowDate
{

    /**
     * The target post type of this class
     *
     * @since    0.0.4
     * @access   public
     * @var      string    $post_type
     */
    public $post_type = 'show_date';

    /**
     * Related post type
     *
     * @since    0.0.4
     * @access   public
     * @var      string    $post_type_related
     */
    public $post_type_related = 'show';

    /**
     * The parsed item from Tuxedo API
     *
     * Refer to API documentation here : https://api.tuxedoticket.ca/documentation#/Events
     *
     * @since    0.0.4
     * @access   protected
     * @var      object    $item
     */
    protected $item;

    /**
     * A logger instance sent to class
     *
     * @since    0.0.4
     * @access   protected
     * @var      object    $logger
     */
    protected $logger;

    /**
     * A unique ID generated based on item values
     *
     * @since    0.0.4
     * @access   protected
     * @var      string    $uuid
     */
    protected $uuid;

    /**
     * WP new or existing post_ID
     *
     * @since    0.0.4
     * @access   protected
     * @var      integer    $uuid
     */
    protected $post_ID = null;

    /**
     * Construct method
     *
     * @since    0.0.4
     * @access   public
     */
    public function __construct($item = null, $logger = null)
    {
        $this->item = $item;

        $this->logger = $logger;

        $this->uuid = $this->generate_uuid();
    }

    /**
     * Run method
     *
     * @since    0.0.4
     * @access   public
     */
    public function run() {
        $this->post_ID = $this->get_post();
        $this->logger->info($this->post_ID);

        if ( ! $this->post_ID) {
            $this->create_post();
            return;
        }

        // post exist, update fields
        $this->save_or_update_fields();

        // send update info to log
        $this->logger->info('Updated show ID : ' . $post_ID);

        return $this->post_ID;
    }

    /**
     * Create a new post when not existing
     *
     * @since    0.0.4
     * @access   private
     */
    private function create_post()
    {
        $this->logger->info('should create a post');

        $related_show = $this->get_related_show();

        // no related, stop here
        if ( ! $related_show) {
            return;
        }

        $fields = [
            'post_title' => $this->item->date,
            'post_type' => $this->post_type,
            'post_status' => 'publish'
        ];

        // create post
        $this->post_ID = wp_insert_post($fields);

        // set ACF fields meta
        $this->save_or_update_fields();

        $this->logger->info('Created show_date with ID : ' . $this->post_ID);

        return $post_ID;
    }

    /**
     * Update ACF Fields
     *
     * @since    0.0.4
     * @access   private
     */
    private function save_or_update_fields()
    {
        // stop here if ACF is not installed
        if ( ! class_exists('ACF') ) return;

        update_field('uuid', $this->uuid, $this->post_ID);
        update_field('date', $this->item->date, $this->post_ID);
        update_field('tuxedo_url', $this->item->tuxedoUrl, $this->post_ID);
        update_field('tuxedo_venue_id', $this->item->venueId, $this->post_ID);
        update_field('tuxedo_soldout', isset($this->item->isSoldOut), $this->post_ID);
        update_field('show', $this->get_related_show(), $this->post_ID);
    }

    /**
     * Generate a Unique ID for item
     *
     * @since    0.0.4
     * @access   private
     */
    private function generate_uuid()
    {
        // use ID + Date + ShowID has parts for the hash
        $parts = [$this->item->id, $this->item->date, $this->item->showId];

        // join and hash it
        return hash('sha1', implode($parts, '-'));
    }

    /**
     * Try to get post based on its UUID value
     *
     * @since    0.0.4
     * @access   private
     */
    private function get_post()
    {
        $args = array(
            'post_type' => $this->post_type,
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key'     => 'uuid',
                    'compare' => '==',
                    'value'   => $this->uuid,
                )
            )
        );

        $query = new \WP_Query($args);

        return isset($query->posts[0]) ? $query->posts[0]->ID : null;
    }

    /**
     * Get the related show post item
     *
     * @since    0.0.4
     * @access   private
     */
    private function get_related_show()
    {
        $args = array(
            'post_type' => $this->post_type_related,
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key'     => 'tuxedo_show_id',
                    'compare' => '==',
                    'value'   => $this->item->showId,
                )
            )
        );

        $query = new \WP_Query($args);

        return isset($query->posts[0]) ? $query->posts[0]->ID : null;
    }

}
