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
    public $post_type = WP_TUXEDO_POST_TYPE;

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
     * WP post status
     *
     * @since    0.2.6
     * @access   protected
     * @var      string  $post_status
     */
    protected $post_status = 'publish';


    /**
     * related show post
     *
     * @since    0.2.0
     * @access   protected
     * @var      object    $related_show
     */
    protected $related_show = null;

    /**
     * tuxedo event date parsed to php date object
     *
     * @since    0.2.0
     * @access   protected
     * @var      object    $parsed_date
     */
    protected $parsed_date = null;

    /**
     * Construct method
     *
     * @since    0.0.4
     * @access   public
     */
    public function __construct($item = null)
    {
        $this->item = $item;
        $this->uuid = $this->generate_uuid();
        $this->now = Carbon::now();
        $this->parsed_date = $this->parse_date();
        $this->related_show = $this->get_related_show();
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
            do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', "No related show or excluded from the web. Tuxedo ID : " . $this->item->id);
            return;
        }

        // do not import show date set in the past
        if( $this->parsed_date->isBefore( $this->now ) ) {
            do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', "Show date is in the past, skip : " . $this->parsed_date . " Tuxedo ID:" . $this->item->id);
            return;
        };

        // generate post title
        $this->post_title = $this->generate_post_title();

        // try to get post
        $this->post_ID = $this->get_post();

        // set post status
        $this->post_status = $this->set_post_status();

        // no post found, create new
        if ( ! $this->post_ID) {
            $this->create_post();
            return;
        }

        // post exist, update fields
        $this->save_or_update_fields();

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
            'post_status' => $this->post_status
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
        if ( ! class_exists('ACF') ) {
            do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', 'Advanced custom fields is not installed', 'error');
            return;
        };

        update_field('uuid', $this->uuid, $this->post_ID);
        update_field('date', $this->parsed_date->toDateTimeString(), $this->post_ID);
        update_field('tuxedo_url', $this->item->tuxedoUrl, $this->post_ID);
        update_field('tuxedo_venue_id', $this->item->venueId, $this->post_ID);
        update_field('tuxedo_is_published', $this->item->isPublished, $this->post_ID);
        update_field('tuxedo_soldout', $this->check_is_soldout(), $this->post_ID);
        update_field('school_only', $this->check_is_school_only(), $this->post_ID);
        update_field('show', $this->related_show->ID, $this->post_ID);

        // force update post, will populate Algolia with all fields data
        $this->force_update();

        // send update info to loger
        do_action(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', 'Created or updated show date : ' . $this->post_title . '. Tuxedo ID:' . $this->item->id, 'info');
    }

     /**
     * Check if item is sold out
     *
     * @since    0.0.5
     * @access   private
     */
    private function check_is_soldout() {
        return (isset($this->item->isSoldOut) && $this->item->isSoldOut === true)  || (isset($this->item->otherStatus) && $this->item->otherStatus === "soldOut");
    }

     /**
     * Check if item is for schools
     *
     * @since    0.0.5
     * @access   private
     */
    private function check_is_school_only() {
        return isset($this->item->otherStatus) && $this->item->otherStatus === "Scolaire";
    }

     /**
     * Set post status based on Tuxedo API 'isClosed' flag
     * if exist and set to true, we set the post-status to 'draft' otherwise default to 'publish'
     *
     * @since    0.2.6
     * @access   private
     */
    private function set_post_status() {
        $is_closed = isset($this->item->isClosed) && $this->item->isClosed === true;
        return $is_closed ? "draft" : "publish";
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
        return implode([$this->related_show->post_title, $this->parsed_date->locale('fr')->isoFormat('LLLL')], " @ ");
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
            'posts_per_page' => 1,
            'post_status' => array('publish', 'draft')
        );

        $posts = get_posts($args);

        return isset($posts[0]) ? $posts[0]->ID : null;
    }

    /**
     * Force a post update with title
     *
     * @since    0.0.4
     * @access   private
     */
    private function force_update() {

        $fields = [
            'ID' => $this->post_ID,
            'post_title' => $this->post_title,
            'post_status' => $this->post_status
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

        return isset($query->posts[0]) ? $query->posts[0] : null;
    }

}
