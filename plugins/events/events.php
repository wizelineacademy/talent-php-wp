<?php
/*
Plugin Name: Events from Eventbrite
Plugin URI: 
Description: Get the events from Eventbrite
Version: 0.0.1
Author: Luis venegas
Author URI: https://www.neuronapixel.com
License: GPLv2 or later
Text Domain: events
*/
class Events{
    protected $currentPage;
    public function __construct(){
        $this->currentPage=1;
        add_action('init',array($this,'create_post_type'));
        add_action( 'admin_menu', array($this,'eventsPluginMenu') );
        
        add_option( 'api_url', 'http://159.89.138.233/events' , '', 'yes' );
        add_option( 'location', 'Guadalajara' , '', 'yes' );
        add_option( 'perPage', '10' , '', 'yes' );
        
        add_filter( 'the_content', array($this,'addEventContent') );

        add_shortcode('twitterlink', array($this,'insertTwitterLink'));
        add_shortcode('eventsWidget', array($this,'insertEventsWidget'));

        wp_register_style(
            'events-stylesheet', // handle name
            plugin_dir_url(__FILE__) . '/css/styles.css' // the URL of the stylesheet
        );
        wp_enqueue_style( 'events-stylesheet' );

    }

    static public function eventsPluginMenu() {
        add_options_page( 'Events Options', 'Events Plugin Options', 'manage_options', 'events-unique-identifier', array($this,'events_plugin_options') );
    }

    static public function events_plugin_options() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        $opt_name1 = 'location';
        $hidden_field_name = 'event_submit_hidden';
        $data_field_name_opt1 = 'location';

        $opt_name2 = 'perPage';
        $data_field_name_opt2 = 'perPage';

        $opt_name3 = 'api_url';
        $data_field_name_opt3 = 'apiUrl';

        $opt_val1 = get_option( $opt_name1 );
        $opt_val2 = get_option( $opt_name2 );
        $opt_val3 = get_option( $opt_name3 );

        if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
            // Read their posted value
            $opt_val1 = $_POST[ $data_field_name_opt1 ];
            $opt_val2 = $_POST[ $data_field_name_opt2 ];
            $opt_val3 = $_POST[ $data_field_name_opt3 ];

            // Save the posted value in the database
            update_option( $opt_name1, $opt_val1 );
            update_option( $opt_name2, $opt_val2 );
            update_option( $opt_name3, $opt_val3 );

            echo '<div class="wrap">';
            echo '<div class="updated"><p><strong>'. _e('settings saved.', 'menu-test' ) .'</strong></p></div>';
            echo '</div>';
        }else{
            echo '<div class="wrap">';
            echo '<p>Here is where you can modify events plugin options</p>';
        
            echo '<form name="form1" method="post" action="">';
            echo        '<input type="hidden" name="'.  $hidden_field_name .'" value="Y">';

            echo        '<p>'. _e("Event API URL:", 'menu-test' ) ;
            echo        '<input type="text" name="'.  $data_field_name_opt3 .'" value="'.  $opt_val3 .'" size="80">';
            echo        '</p>';

            echo        '<p>'. _e("Event Location:", 'menu-test' ) ;
            echo        '<input type="text" name="'.  $data_field_name_opt1 .'" value="'.  $opt_val1 .'" size="20">';
            echo        '</p>';

            echo        '<p>'. _e("Event per Page:", 'menu-test' ) ;
            echo        '<input type="number" name="'.  $data_field_name_opt2 .'" value="'.  $opt_val2 .'" >';
            echo        '</p><hr />';

            echo    '<p class="submit">';
            echo    '<input type="submit" name="Submit" class="button-primary" value="'. (string)esc_attr_e('Save Changes') .'" />';
            echo    '</p>';

            echo    '</form>';
            echo    '</div>';
        }
    }

    static public function addEventContent($args){
        $defaults = array (
            'content' => 'Wrong',
            'page' => 1,
            'perPage' => 10
       );
       $args = wp_parse_args( $args, $defaults );
     
        if(is_single()){
            if ('events' === get_post_type()) {
                $content = $this-> getEventsContent($args);
            }else{
                $content = get_the_content();
            }
        }
        return $content;
    }

    private function getEventsContent($args){
        $defaults = array (
            'content' => 'Something went wrong.',
            'page' => 1,
            'perPage' => 10
       );
       $args = wp_parse_args( $args, $defaults );
        
        $addendum = " <h2>Events</h2>";
        $content .= $addendum;
        
        
        $events = $this->getDataFromAPI($args);
        $content.= '<div>';
        foreach ($events['items'] as $event) {
            
            $content.= '<div class="event">';

            $content.= ' <div class="event-image">
                            <img src="'. $event['image_url'].'" alt="">
                        </div>';

            $content.=  '<div class="event-title">
                            <h2>
                                '. nl2br($event['name']).'
                            </h2>
                        </div>';
            $startDate = new DateTime($event['start']);
            $endDate = new DateTime($event['end']);
            $content.= '<div class="time-info">
                        '.__('Begins: ').'<span class="ligther">' .$startDate->format('Y/m/d @ H:i ').'</span>'.
                        ' ' .__('Ends: ').'<span class="ligther">'.$endDate->format('Y/m/d @ H:i ').'</span>' .' 
                        </div>';

            $content.= '<div class="event-description">
                            <p>
                            '. nl2br($event['description']).'
                            </p>
                        </div>';
            
            $content.= '<div class="event-venue">
                         <h3>'.__('Venue Information:').'</h3>
                            <div>
                                <h4>
                                '. nl2br($event['venue']['name']) .'
                                </h4>
                                <p>
                                '. nl2br($event['venue']['address']) .'
                                </p>
                            </div>  
                        </div>';

            $content.= '</div>';
        }
    $content.= '</div>';
    return $content;
    }

    private function getDataFromAPI($args){
        $defaults = array (
            'content' => 'Wrong',
            'page' => 1,
            'perPage' => 10
        );
        $args = wp_parse_args( $args, $defaults );

        $api_request = (string)get_option('api_url')."?page=".(string)$args['page']."&size=".(string)$args['perPage'];
        $api_response = wp_remote_get( $api_request);
        $api_data = json_decode(wp_remote_retrieve_body( $api_response), true );
        return $api_data;
    }

    public function insertTwitterLink($attr){
        $attr = shortcode_atts(array('username'=>'cachitweet','text'=>'visit my twitter'),$attr);
        return '<div><a href="https://twitter.com/'.$attr['username'].'" target="_blank">'.'Follow '.$attr['username']." ".$attr['text'].'</a></div>';
    }

    public function insertEventsWidget($attr){
       $attr = shortcode_atts(array('content'=>'Something','page'=>1,'per_page'=>10),$attr);
       
        $msg = ' Per Page: '.$attr['perPage']. '...';
        $attrs=array(
            'content' => $msg ,
            'page' => 1,
            'perPage' => intval($attr['per_page'])
        );
       
         return $this->getEventsContent($attrs);
    }

    function create_post_type() {
        register_post_type( 'events',
          array(
            'labels' => array(
              'name' => __( 'Event lists' ),
              'singular_name' => __( 'Events list' ),
            ),
            'public' => true,
            'has_archive' => false,
          )
        );
    }

}

$events = new Events();