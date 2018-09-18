<?php

namespace EventsPlugin;

class EventsOptions {
	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_events_options' ] );
		add_action( 'admin_menu', [ $this, 'add_events_options_page' ] );
	}

	public function register_events_options() {
		register_setting( 'events-settings', 'api_url', [
			'type' => 'string'
		] );
		register_setting( 'events-settings', 'events_page', [
			'type' => 'number',
			'default' => 1
		]);
		register_setting( 'events-settings', 'events_size', [
			'type' => 'number',
			'default' => 10
		]);
	}

	public function add_events_options_page() {
		add_options_page(
			'Events Settings',
			'Events Settings',
			'manage_options',
			'events-settings',
			[ $this, 'create_admin_page' ]
		);
	}

	public function create_admin_page() {
		?>
		<div class="wrap">
			<h1>Events Settings</h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'events-settings' );
				do_settings_sections( 'events-settings' );
				?>
				<table class="form-table">
					<tbody>
					<tr>
						<th class="row"><label for="api_url">Events API Url</label></th>
						<td><input id="api_url" type="text" placeholder="Events API Url" name="api_url"
						           value="<?php echo esc_attr( get_option( 'api_url' ) ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th class="row"><label for="events_page">Events Page</label></th>
						<td><input id="events_page" type="number" placeholder="Page" name="events_page" min="1"
						           value="<?php echo esc_attr( get_option( 'events_page' ) ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th class="row"><label for="events_size">Events Page Size</label></th>
						<td><input id="events_size" type="number" placeholder="Page Size" name="events_size" min="1"
						           value="<?php echo esc_attr( get_option( 'events_size' ) ); ?>" class="regular-text"></td>
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
}