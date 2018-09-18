<?php

namespace EventsPlugin;

class Events {
	public function __construct() {
		add_action( 'init', [ $this, 'create_event_cpt' ] );
		add_filter( 'the_content', [ $this, 'add_event_info' ] );
		wp_register_style(
			'events-styles',
			plugins_url( '/css/styles.css', __FILE__ )
		);
		add_shortcode( 'events', [ $this, 'short_code_events' ] );
	}

	public function create_event_cpt() {
		register_post_type( 'events', [
			'labels'      => [
				'name'          => __( 'Event Lists' ),
				'singular_name' => __( 'Events List' )
			],
			'public'      => true,
			'has_archive' => false,
			'menu_icon'   => 'dashicons-tickets-alt'
		] );
	}

	public function add_event_info( string $content ) {
		if ( is_singular( 'events' ) ) {
			wp_enqueue_style( 'events-styles' );
			$events_page    = get_option( 'events_page' );
			$events_size    = get_option( 'events_size' );
			$result_content = $this->generate_events_list_html( $events_page, $events_size );
			$content        .= $result_content;
		}

		return $content;
	}

	public function short_code_events( $attr ) {
		$attr   = shortcode_atts( [ 'number' => get_option( 'events_size' ) ], $attr );
		$number = $attr['number'];
		wp_enqueue_style( 'events-styles' );
		return $this->generate_events_list_html( 1, $number );
	}

	private function generate_events_list_html( int $page, int $page_size ): string {
		$events  = $this->get_data_from_api( $page, $page_size );
		$content = '<h1>Events</h1><section class="events__list">';
		foreach ( $events['items'] as $event ) {
			$content .= '	
			<article class="event__card">
				<a href="'. esc_url( $event['url'] ) .'">
					<img class="event__thumbnail" src="' . esc_url( $event['image_url'] ) . '" alt="' . esc_attr( $event['name'] ) . '">
				</a>
				<div class="event__details">
					<h4>' . $event['name'] . '</h4>
					<p class="event__duration">
						<strong>Inicia:</strong> ' . $this->transform_time( $event['start'] ) . '
					</p>
					<p class="event__duration">
						<strong>Termina:</strong> ' . $this->transform_time( $event['end'] ) . '
					</p>
					<p>' . $event['description'] . '</p>
					<hr>
					<div class="event__venue">
						<p>
							<strong>Lugar: </strong> ' . $event['venue']['name'] . '
						</p>
						<p>
							<strong>Direccion: </strong> ' . $event['venue']['address'] . '
						</p>
					</div>
				</div>
			</article>';
		}

		$content .= '</section>';

		return $content;
	}

	private function get_data_from_api( int $page, int $page_size ) {
		$api_url      = get_option( 'api_url' );
		$api_request  = "$api_url?page=$page&size=$page_size";
		$api_response = wp_remote_get( $api_request );
		$api_data     = json_decode( wp_remote_retrieve_body( $api_response ), true );

		return $api_data;
	}

	private function transform_time ( string $date_time ): string {
		$date_time_format = get_option( 'date_format' ).' @ '.get_option( 'time_format' );
		return (new \DateTime( $date_time,  new \DateTimeZone( get_option( 'timezone_string' ) ) ) )->format($date_time_format);

	}
}