<?php
/*
Plugin Name: SBTS Banner Everywhere
Plugin URI: http://github.com/sbtsdev/banner-everywhere
Description: Add a message to every site in this installation as a banner running across the top of the page, every page.
Author: Joshua Cottrell
Author URI: https://github.com/jcottrell
Version: 0.2.0
*/

class SBTS_Banner_Everywhere {
	private $begin_time;
	private $end_time;
	private $now;
	private $active;
	private $message_temple; // template where {{SITE}}

	public function __construct() {
		$this->active = false;
		$options = get_option( 'sbts_banner_everywhere_opts' );
		if ( isset( $options['date_start'] ) && isset( $options['date_end'] ) && isset( $options['time_start'] ) && isset( $options['time_end'] ) ) {
			$this->begin_time		= strtotime( $options['date_start'] . ' ' . $options['time_start'] );
			$this->end_time			= strtotime( $options['date_end'] . ' ' . $options['time_end'] );
			$this->now				= time();
										// make sure that strtotime returned correctly and that the date range is valid and the banner should be active
			$this->active			= ( $this->begin_time && $this->end_time && ( $this->begin_time > 0 ) && ( $this->end_time > 0) && ( $this->now > $this->begin_time ) && ( $this->now < $this->end_time ) );
			$this->message_template	= ( isset( $options['banner_text'] ) && ( strlen( $options['banner_text'] ) > 0 ) ) ? $options['banner_text'] : '{{SITE}} will be unavailable for scheduled maintenance ({{END_DATE}} {{END_TIME}}).';
		}

		add_action( 'admin_init', array( &$this, 'options_init' ) );
		add_action( 'admin_menu', array( &$this, 'options_page_init' ) );

		if ( $this->active ) {
			add_action( 'wp_head', array( &$this, 'header' ) );
			add_action( 'wp_footer', array( &$this, 'banner' ) );
		}
	}

	public function header() {
		wp_enqueue_script( 'json2' );
		wp_enqueue_script( 'sbts-banner-everywhere', plugins_url( 'js/banner-everywhere.v0.2.0.min.js', __FILE__ ), array( 'json2' ), null, true );
	}

	public function banner() {
		// since localStorage is root url specific, let's be that way too
		$my_site = home_url();
		$my_site_start = strpos( $my_site, '/' ) + 2;
		$my_site_end = strpos( $my_site, '/', $my_site_start );
		$my_site = substr( $my_site, $my_site_start, ( $my_site_end > $my_site_start ? $my_site_end - $my_site_start : strlen( $my_site ) ) );
?>
		<div id="sbts_banner_everywhere" data-banner_begin="<?php echo $this->begin_time; ?>" data-banner_end="<?php echo $this->end_time; ?>" data-banner_now="<?php echo $this->now; ?>" style="position:fixed;top:0;left:0;z-index:4000;display:none;clear:both;margin:0;padding:10px 5px;background-color:goldenrod;width:100%;font:normal 14px/24px GaramondPremrPro, serif;color:4a4a4a;text-align:center;border-bottom:2px solid #2a2a2a;">
			<div style="float:right;margin:-12px 20px 2px 4px;"><a id="sbts_banner_everywhere_hider" style="font-size:12px;line-height:13px;color:#f1f1f1;text-decoration:none;" href="#hide">dismiss</a></div>
			<p style="width:90%;margin:0 auto;"><?php
				echo str_replace(	array( '{{SITE}}','{{BEGIN_DATE}}','{{BEGIN_TIME}}','{{END_DATE}}','{{END_TIME}}'),
									array( $my_site, date( 'F j, Y', $this->begin_time ), date( 'h:ia', $this->begin_time ), date( 'F j, Y', $this->end_time ), date( 'h:ia', $this->end_time ) ),
									$this->message_template );
			?></p>
		</div>
	<?php
	}

	public function options_init() {
		register_setting( 'sbts_banner_everywhere_opts', 'sbts_banner_everywhere_opts' );
	}

	public function options_page_init() {
		$sbe_page = add_options_page( 'Banner Everywhere', 'Banner Everywhere', 'manage_network_options', 'sbts-banner-everywhere-options', array( &$this, 'options_page' ) );
		add_action( "admin_print_scripts-$sbe_page", array( &$this, 'admin_head' ) );
	}

	public function options_page() {
		echo	'<div class="wrap">';
		echo	'	<form method="post" action="options.php">';
		settings_fields( 'sbts_banner_everywhere_opts' );
		$options = get_option( 'sbts_banner_everywhere_opts' );
		echo	'		<h2>Banner Everywhere Settings</h2>';
		echo	'		<div class="banner-wrap">';
		echo	'			<label for="sbts_banner_everywhere_text">Banner text</label>';
		echo	'			<input class="banner-text widefat" type="text" name="sbts_banner_everywhere_opts[banner_text]" value="' . ( isset( $options['banner_text'] ) ? $options['banner_text'] : '' ) . '" />';
		echo	'			<p>Use {{SITE}} to reference the domain the banner appears on. Use {{BEGIN_DATE}}, {{BEGIN_TIME}}, {{END_DATE}}, {{END_TIME}} to reference the dates and times you set below.</p>';
		echo	'		</div>';
		echo	'		<div class="date-time-wrap">';
		echo	'			<p>Set the date and time range for the banner to appear.</p>';
		echo	'			<div class="date-time-box">';
		echo	'				<p>Start Date</p>';
		echo	'				<div class="date-box" id="dt_start"></div>';
		echo	'				<div class="time-box">';
		echo	'					<input type="text" id="date_start" name="sbts_banner_everywhere_opts[date_start]" value="' . ( isset( $options['date_start'] ) ? $options['date_start'] : '' ) . '" />';
		echo	'					<input placeholder="Start time (24hr)" type="text" id="time_start" name="sbts_banner_everywhere_opts[time_start]" value="' . ( isset( $options['time_start'] ) ? $options['time_start'] : '' ) . '" />';
		echo	'				</div>';
		echo	'			</div>';
		echo	'			<div class="date-time-box">';
		echo	'				<p>End Date</p>';
		echo	'				<div class="date-box" id="dt_end"></div>';
		echo	'				<div class="time-box">';
		echo	'					<input type="text" id="date_end" name="sbts_banner_everywhere_opts[date_end]" value="' . ( isset( $options['date_end'] ) ? $options['date_end'] : '' ) . '" />';
		echo	'					<input placeholder="End time (24hr)" type="text" id="time_end" name="sbts_banner_everywhere_opts[time_end]" value="' . ( isset( $options['time_end'] ) ? $options['time_end'] : '' ) . '" />';
		echo	'				</div>';
		echo	'			</div>';
		echo	'		</div>';
		echo	'		<div class="instruction-wrap"><p class="okay" id="instruction"></p></div>';
		echo	'		<p class="submit">';
		echo	'			<input type="submit" class="button-primary" value="'; _e( 'Save Changes' ); echo '" />';
		echo	'		</p>';
		echo	'	</form>';
		echo	'</div>';
	}

	public function admin_head() {
		$my_url = plugins_url( '/', __FILE__ );
		wp_enqueue_style( 'banner-everywhere-smoothness-jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/smoothness/jquery-ui.min.css', array(), null );
		wp_enqueue_style( 'banner-everywhere-css', $my_url . 'css/banner-everywhere-admin.css', 'banner-everywhere-smoothness-jquery-ui', null );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'banner-everywhere-js', $my_url . 'js/banner-everywhere-admin.v0.2.0.min.js', array( 'jquery-ui-datepicker' ), null, true );
	}

}

global $sbts_everywhere_banner;
if ( class_exists( 'SBTS_Banner_Everywhere' ) ) {
	if (! isset( $sbts_everywhere_banner ) ) {
		$sbts_everywhere_banner = new SBTS_Banner_Everywhere();
	}
}
?>
