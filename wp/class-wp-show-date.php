<?php

namespace TDP_Tuxedo\Wp;

use WP_Query;
use Carbon\Carbon;

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
        $related_show = $this->get_related_show();

        $this->logger->info(print_r($this->item, true));

        // no show found or $item has bool excludedFromTheWeb, stops here
        if (!$related_show || $this->item->excludedFromTheWeb == true) {
            return;
        }

        // try to get post
        $this->post_ID = $this->get_post();

        // no post found, create new
        if ( ! $this->post_ID) {
            $this->create_post();
            return;
        }

        // post exist, update fields
        $this->save_or_update_fields();

        // send update info to log
        $this->logger->info('Updated show ID : ' . $this->post_ID);

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
        $this->logger->info('Create a new post for show : ' . $this->item->showId);

        $fields = [
            'post_title' => $this->uuid,
            'post_name' => $this->uuid,
            'post_type' => $this->post_type,
            'post_content' => $this->uuid,
            'post_status' => 'publish'
        ];

        // create post
        $post_ID = wp_insert_post($fields);

        // set class created post id
        $this->post_ID = $post_ID;

        // post not created
        if(!$this->post_ID) {
            $this->logger->info('Error : not post inserted for : ' . $this->item->showId);
            return;
        };

        // set ACF fields meta
        $this->save_or_update_fields();


        // $this->force_update();

        // return post ID
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

        // parse and set timezone to Tuxedo item date
        $parsed_date = Carbon::parse($this->item->date)->setTimezone('America/Toronto');

        update_field('uuid', $this->uuid, $this->post_ID);
        update_field('date', $parsed_date, $this->post_ID);
        update_field('tuxedo_url', $this->item->tuxedoUrl, $this->post_ID);
        update_field('tuxedo_venue_id', $this->item->venueId, $this->post_ID);
        update_field('tuxedo_soldout', $this->check_is_soldout(), $this->post_ID);
        // TODO: implement
        // update_field('school_only', $this->check_is_school_only(), $this->post_ID);
        update_field('show', $this->get_related_show(), $this->post_ID);

        // force update post, will populate Algolia with all fields data
        $this->force_update();
    }

     /**
     * Check if item is sold out
     *
     * @since    0.0.5
     * @access   private
     */
    private function check_is_soldout() {
        return isset($this->item->isSoldOut);
    }

     /**
     * Check if item is for schools
     * TODO: implement, needs documentation
     *
     * @since    0.0.5
     * @access   private
     */
    private function check_is_school_only() {
        return false;
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
     * Try to get post based on its slug/name value using the generated UUID
     *
     * @since    0.0.4
     * @access   private
     */
    private function get_post()
    {
        $args = array(
            'post_type' => $this->post_type,
            'name' => $this->uuid,
            'posts_per_page' => 1
        );

        $posts = get_posts($args);

        return isset($posts[0]) ? $posts[0]->ID : null;
    }

    private function force_update() {
        $fields = [
            'ID' => $this->post_ID,
            'post_title' => $this->uuid
        ];

        return wp_update_post($fields);
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
            'post_status' => 'publish',
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
