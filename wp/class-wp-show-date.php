<?php

namespace WP_Tuxedo\Wp;

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


    protected $related_show = null;

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
        $this->parsed_date = $this->parse_date();
        $this->related_show = $this->get_related_show();
        $this->post_title = $this->generate_post_title();
    }

    /**
     * Run method
     *
     * @since    0.0.4
     * @access   public
     */
    public function run() {

        // stops here if no show found OR $item has a bool excludedFromTheWeb set to true
        if (!$this->related_show || (isset($this->item->excludedFromTheWeb) && $this->item->excludedFromTheWeb == true)) {
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
        $this->logger->info('Created or updated show ID : ' . $this->post_ID);

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

        $fields = [
            'post_title' => $this->post_title,
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
            return;
        };

        // set ACF fields meta
        $this->save_or_update_fields();

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

        $this->logger->info($this->parsed_date);

        update_field('uuid', $this->uuid, $this->post_ID);
        update_field('date', $this->parsed_date, $this->post_ID);
        update_field('tuxedo_url', $this->item->tuxedoUrl, $this->post_ID);
        update_field('tuxedo_venue_id', $this->item->venueId, $this->post_ID);
        update_field('tuxedo_is_published', $this->item->isPublished, $this->post_ID);
        update_field('tuxedo_soldout', $this->check_is_soldout(), $this->post_ID);
        // TODO: implement
        // update_field('school_only', $this->check_is_school_only(), $this->post_ID);
        update_field('show', $this->related_show->ID, $this->post_ID);

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
     * Generate a post title
     *
     * @since    0.2.0
     * @access   private
     */
    private function generate_post_title()
    {
        // join
        return implode([$this->related_show->post_title, $this->parsed_date], " @ ");
    }

    /**
     * Parse API date to PHP date object in current timezone
     *
     * @since    0.2.0
     * @access   private
     */
    private function parse_date()
    {
        if(!$this->item->date) return;

        // join and hash it
        // parse and set timezone to Tuxedo item date
        return Carbon::parse($this->item->date)->setTimezone('America/Toronto');
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
            'post_title' => $this->post_title
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
                    'key'     => 'tuxedo',
                    'compare' => '==',
                    'value'   => $this->item->showId,
                )
            )
        );

        $query = new \WP_Query($args);

        return isset($query->posts[0]) ? $query->posts[0] : null;
    }

}
