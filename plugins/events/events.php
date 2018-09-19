<?php

/*
Plugin Name: Events Plugin
Plugin URI: https://google.com
Description: Events Plugin
Version: 0.1.0
Author: Miguel Villasenor
 */

class Events
{
    public function __construct()
    {
        add_filter('the_content', array($this, 'addEventInfo'));
        add_shortcode('twitter_link', array($this, 'insertTwitterLink'));
        add_shortcode('events_list', array($this, 'events_shortcode'));
        add_action('init', array($this, 'create_post_type'));
        add_action('admin_menu', array($this, 'create_menu'));
        add_action( 'admin_init', array($this, 'register_my_settings') );
        wp_register_style('events-style', plugin_dir_url(__FILE__) . 'css/styles.css');
    }

    public function addEventInfo($content)
    {
        if (is_single() && get_post_type() == 'events') {
            $content .= $this->getEvents(get_option('number_of_events'));
        }
        return $content;
    }

    public function events_shortcode($attr) {
        $attr = shortcode_atts(['number' => "10"], $attr);
        return $this->getEvents($attr["number"]);
    }

    public function getEvents($event_number) {
        
        wp_enqueue_style('events-style');
        $content = '<h1>Events</h1>';
            $events = $this->getDataFromAPI($event_number);

            foreach ($events['items'] as $event) {
                $content .= '<div class="event-container">';
                $content .= '<div class="event-header-container">';
                $content .= '<img class="event-image" src="' . $event['image_url'] . '"/>';
                $content .= '<div>';
                $content .= '<h3 class="event-name">' . $event['name'] . '</h3>';
                $content .= '<div class="entry-meta">' . date(get_option( 'date_format' ), strtotime($event['start'])) . 
                ' to ' . date(get_option( 'date_format' ), strtotime($event['start'])) . '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="event-description entry-content">' . wp_trim_words($event['description']) . '</div>';

                $content .= '<div class="venue-name">' . $event['venue']['name'] . '</div>';
                $content .= '<div class="venue-address">' . $event['venue']['address'] . '</div>';
                
                $content .= '</div>';
            }

            return $content;
    }

    private function getDataFromAPI($event_number)
    {
        $api_request = get_option('events_url') . '?page=1&size=' . $event_number;
        $api_response = wp_remote_get($api_request);
        $api_data = json_decode(wp_remote_retrieve_body($api_response), true);

        return $api_data;
    }

    public function insertTwitterLink($attr)
    {
        $attr = shortcode_atts(['username' => 'm1k3777', 'text' => 'Twitter'], $attr);
        return '<div><a href="http://twitter.com/' . $attr["username"] . '" target="_blank">' . $attr["text"] . '</a></div>';
    }

    public function create_post_type()
    {
        register_post_type(
            'events',
            array(
                'labels' => array(
                    'name' => __('Event lists'),
                    'singular_name' => __('Event list')
                ),
                'public' => true,
                'has_archive' => false,
            )
        );
    }

    function register_my_settings() 
    {
        register_setting('event_options', 'number_of_events', array('type' => 'integer', 'default' => 10));
        register_setting('event_options', 'events_url', array('type' => 'string', 'default' => 'http://159.89.138.233/events'));
    }

    function create_menu() 
    {
        add_options_page( 'Events Options', 'Events', 'manage_options', 'event-options', array($this, 'options_form'));
    }

    function options_form() 
    {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        echo '<div class="wrap">';
        echo '<h1>Events Options</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'event_options' );
        do_settings_sections( 'event_options' );
        echo '<div><label>Number of events <label><input type="text" name="number_of_events" value="' . esc_attr( get_option('number_of_events') ) . '"/></div>';
        echo '<div><label>Events api url <label><input type="text" name="events_url" value="' . esc_attr( get_option('events_url') ) . '"/></div>';
        submit_button();
        echo '</form>';
        echo '</div>';
    } 

}

$events = new Events();

