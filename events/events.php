<?php

/*
Plugin Name: Events Plugin
Plugin URI: https://www.eventbrite.com/
Description: Events Plugin
Version: 1.0.0
Author: Mike Sagnelli
*/

class Events {

    public function __construct() {
		add_filter('the_content', [$this,'addEventInformation']);
		add_shortcode('events', [$this, 'resumeEvents']);
		add_action('init', [$this, 'createEvent']);
		add_action('admin_menu', [ $this, 'addEventsOptions']);
        add_action('admin_init', [ $this, 'registerEventOptions']);
		wp_register_style(
			'events-styles',
			plugins_url( '/css/styles.css', __FILE__ )
		);
    }

    private function getData(int $page, int $pageSize) {
		$apiUrl = get_option('apiUrl');
		$apiRequest = "$apiUrl?page=$page&size=$pageSize";
		$apiResponse = wp_remote_get($apiRequest);
		$apiData = json_decode(wp_remote_retrieve_body($apiResponse), true);
 		return $apiData;
    }
    
 	private function getTime (string $date) : string {
		$dateFormat = get_option('date_format').' @ '.get_option('time_format');
		return (new \DateTime($date, new \DateTimeZone(get_option('timezone_string'))))->format($dateFormat);
    }
    
    public function registerEventOptions() {
		register_setting('eventSettings', 'apiUrl', [
			'type' => 'string'
		]);
		register_setting('eventSettings', 'eventsPage', [
			'type' => 'number',
			'default' => 1
		]);
		register_setting('eventSettings', 'eventsSize', [
			'type' => 'number',
			'default' => 10
		]);
    }
    
 	public function addEventsOptions() {
		add_options_page(
			'Events Settings',
			'Events Settings',
			'manage_options',
			'eventSettings',
			[$this, 'adminPage']
		);
    }
    
 	public function adminPage() {
		?>
		<div class="wrap">
			<h1>Events Settings</h1>
			<form action="options.php" method="post">
				<?php
				settings_fields('eventSettings');
				do_settings_sections('eventSettings');
				?>
				<table class="form-table">
					<tbody>
					<tr>
						<th class="row"><label for="apiUrl">API Url</label></th>
						<td><input id="apiUrl" type="text" placeholder="Events API Url" name="apiUrl"
						           value="<?php echo esc_attr(get_option('apiUrl')); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th class="row"><label for="eventsPage">Page</label></th>
						<td><input id="eventsPage" type="number" placeholder="Page" name="eventsPage" min="1"
						           value="<?php echo esc_attr(get_option('eventsPage')); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th class="row"><label for="eventsSize">Page Size</label></th>
						<td><input id="eventsSize" type="number" placeholder="Page Size" name="eventsSize" min="1"
						           value="<?php echo esc_attr(get_option('eventsSize')); ?>" class="regular-text"></td>
					</tr>
					</tbody>
					<tr>
						<td><?php submit_button(); ?></td>
					</tr>
				</table>
			</form>
		</div>
		<?
	}
    
 	public function createEvent() {
		register_post_type('events', [
			'labels' => [
				'name' => __('Event Lists'),
				'singular_name' => __('Events List')
			],
			'public' => true,
			'has_archive' => false,
			'menu_icon' => 'dashicons-tickets-alt'
		]);
    }
    
 	public function addEventInformation(string $content) {
		if (is_singular('events')) {
			wp_enqueue_style('events-styles');
			$eventsPage = get_option('eventsPage');
			$eventsSize = get_option('eventsSize');
			$result = $this->eventList($eventsPage, $eventsSize);
			$content .= $result;
		}
 		return $content;
    }
    
 	public function resumeEvents($attribute) {
		$attribute = shortcode_atts(['number' => get_option('eventsSize')], $attribute);
		$number = $attribute['number'];
		wp_enqueue_style('events-styles');
		return $this->eventList(1, $number);
    }
    
 	private function eventList(int $page, int $pageSize) : string {
		$events = $this->getData($page, $pageSize);
		$content = '<h1>Events</h1><section class="events">';
		if (is_array($events) || is_object($events)) {
			foreach ($events['items'] as $event) {
				$content .= '	
					<article class="event">
						<a href="'.esc_url($event['url']).'">
							<img class="event-image" src="'.esc_url($event['image_url']).'" alt="'.esc_attr($event['name']).'">
						</a>
						<div class="event-details">
							<h4>'.$event['name'].'</h4>
							<p class="event-date">
								<b>Inicio:</b> '.$this->getTime($event['start']).'
							</p>
							<p class="event-date">
								<b>Fin:</b> '.$this->getTime($event['end']).'
							</p>
							<p>'.$event['description'].'</p>
							<hr>
							<div class="event-venue">
								<p>
									<b>Lugar: </b> '.$event['venue']['name'].'
								</p>
								<p>
									<b>Direccion: </b> '.$event['venue']['address'].'
								</p>
							</div>
						</div>
					</article>
				';
			}
		}
 		$content .= '</section>';
 		return $content;
    }
}

$events = new Events();