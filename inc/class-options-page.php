<?php
/**
 * Class OptionsPage
 *
 * Creates an options page that allows the user to enter their sharpsprings secret and other info
 */

namespace RelativeMarketing\EndpointsForSharpspring;

class Options_Page {
	/**
	 * Static property to hold our singleton instance
	 *
	 * @var bool
	 */
	static protected $instance = false;

	/**
	 * constructor.
	 */
	private function __construct() {
		$this->start();
	}

	protected function start() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu_item' ] );
		add_action( 'admin_init', [ $this, 'display_theme_panel_fields' ] );
	}

	/**
	 * Adds a sub menu page 
	 */
	public function add_admin_menu_item() {
		add_submenu_page( 'options-general.php', 'Endpoints for Sharpspring', 'Endpoints for Sharpspring', 'manage_options', 'endpoints-for-sharpspring', [ $this, 'option_page_callback' ] );
	}

	/**
	 * The main option page content
	 */
	public function option_page_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page' ) );
		}
		?>
		<div class="wrap">
			<form action='options.php' method='post'>
				<?php
				settings_fields( 'endpoints_for_sharpspring_section' );
				?>
				<h1>Endpoints for Sharpspring</h1>
				<p>Please add the required sharpspring info</p>
				<?php
				do_settings_sections( 'endpoints-for-sharpspring' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * The field that accepts the GTM ID
	 */
	public function display_theme_panel_fields() {
		add_settings_section( 'endpoints_for_sharpspring_section', 'Endpoints for Sharpspring settings', null, 'endpoints-for-sharpspring' );

		$fields = [
			['key' => 'endpoints_for_sharpspring_api_key', 'heading' => 'Your sharpspring api key', 'callback' => 'display_api_input'],
			['key' => 'endpoints_for_sharpspring_secret_key', 'heading' => 'Your sharpspring secret key', 'callback' => 'display_secret_input'],
		];
		
		foreach ($fields as $field) {
			add_settings_field( $field['key'], $field['heading'], [ $this, $field['callback'] ], 'endpoints-for-sharpspring', 'endpoints_for_sharpspring_section' );
			register_setting( 'endpoints_for_sharpspring_section', $field['key'] );
		}

	}

	public function add_input( $option ) {
		echo sprintf( '<input name="%s" id="%1$s" value="%2$s" />', $option, sanitize_text_field( get_option( $option ) ) );
	}

	/**
	 * The form input for the secret key
	 */
	public function display_secret_input() {
		$this->add_input( 'endpoints_for_sharpspring_secret_key' );
	}

	/**
	 * The form input
	 */
	public function display_api_input() {
		$this->add_input( 'endpoints_for_sharpspring_api_key' );
	}

	/**
	 * If an instance exists , this returns it. If not it creates one and then returns it.
	 *
	 * @return Options_Page
	 */

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}