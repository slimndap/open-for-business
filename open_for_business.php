<?php
/*
Plugin Name: Open for business
Plugin URI: http://slimndap.com
Description: 
Author: Jeroen Schmit, Slim & Dapper
Author URI: http://slimndap.com/
Version: 1.0
*/

class Open_for_business {
	function __construct() {
		if (is_admin()) {
			add_action( 'admin_init', array($this,'admin_init'));
			add_action( 'admin_menu', array($this, 'admin_menu' ));
		}

		add_action( 'plugins_loaded', array($this,'plugins_loaded'));
		
		add_shortcode('open_for_business', array($this,'shortcode'));
		
		$this->options = get_option('open_for_business');
	}
	
	function now() {
		if (empty($this->now)) {
			$offset    = get_option( 'gmt_offset' );
			$offset    = $offset * 60 * 60;
			$this->now = time() + $offset;		
		}
		return $this->now;	
	}

	function well_are_you() {
		if (
			!($opening_time = $this->opening_time()) ||
			!($closing_time = $this->closing_time())
		) {
			// opening or closing hours are not properly set.
			return false;
		}
		
		return ($opening_time<$this->now()) &&  ($closing_time>$this->now());
	}

	function opening_time ($timestamp=false) {
		if ($timestamp) {
			$weekday = date('w',$timestamp);
		} else {
			$weekday = date('w',$this->now());
		}
		if (
			empty($this->options['open_hour_'.$weekday]) || 
			empty($this->options['open_minute_'.$weekday])
		) {
			return false;
		}
		return strtotime($this->options['open_hour_'.$weekday].':'.$this->options['open_minute_'.$weekday]);	
	}

	function closing_time ($timestamp=false) {
		if ($timestamp) {
			$weekday = date('w',$timestamp);
		} else {
			$weekday = date('w',$this->now());
		}
		if (
			empty($this->options['close_hour_'.$weekday]) || 
			empty($this->options['close_minute_'.$weekday])
		) {
			return false;
		}
		
		return strtotime($this->options['close_hour_'.$weekday].':'.$this->options['close_minute_'.$weekday]);	
	}

	function please_elaborate() {
		if ($this->well_are_you()) {
			// We're open. But when do you close?
			$weekday = date('w',$this->now());
			$closing_time = strtotime($this->options['close_hour_'.$weekday].':'.$this->options['close_minute_'.$weekday]);
			return sprintf( __( 'open today until %s', 'open_for_business' ), date_i18n(get_option('time_format'),$this->closing_time()) );
		} else {
			// We're closed. But when do you open again?
			if ($this->now()<$this->opening_time()) {
				// Later today
				return sprintf( 
					__( 'open today from %s', 'open_for_business' ), 
					date_i18n(get_option('time_format'),$this->opening_time()) 
				);
			}
			
			// Tomorrow?
			$next_day = strtotime('tomorrow', $this->now());
			$in_how_many_days = 1;
			while (!($opening_time = $this->opening_time($next_day)) || !$this->closing_time($next_day)) {
				// Or another day?
				$next_day = strtotime('+1 day', $next_day);
				$in_how_many_days++;
			}

			if ($in_how_many_days==1) {
				// Tomorrow
				return sprintf( 
					__( 'open tomorrow from %s', 'open_for_business' ), 
					date_i18n(get_option('time_format'),$opening_time) 
				);
			}
			
			if ($in_how_many_days<6) {
				return sprintf( 
					__( 'open %s from %s', 'open_for_business' ), 
					date_i18n('l',$next_day),
					date_i18n(get_option('time_format'),$opening_time) 
				);
				
			}
			
			else {				
				return sprintf( 
					__( 'open %s from %s', 'open_for_business' ), 
					date_i18n(get_option('date_format'),$next_day),
					date_i18n(get_option('time_format'),$opening_time) 
				);
			}
		}
		
	}

	function admin_init() {
		register_setting(
            'open_for_business', // Option group
            'open_for_business' // Option name
        );

		add_settings_section(
			'opening-hours', // ID
			__('Opening hours','open_for_business'), // Title
			'', // Callback
			'open_for_business' // Page
		);
		
		add_settings_field(
			'regular-hours', // ID
			__('Regular hours','open_for_business'), // Title
			array( $this, 'settings_field_regular_hours' ), // Callback
			'open_for_business', // Page
			'opening-hours' // Section
		);

		/* Maybe later...

		add_settings_field(
			'exceptions', // ID
			__('Exceptions','open_for_business'), // Title
			array( $this, 'settings_field_exceptions' ), // Callback
			'open_for_business', // Page
			'opening-hours' // Section
		);
		
		*/
	}

	function admin_menu() {
		add_options_page( __('Open for business','open_for_business'), 'Open for business', 'manage_options', 'open_for_business-admin', array( $this, 'admin_page' ));
	}	

	public function admin_page() {
	    ?>
		<div class="wrap">
			<h2><?php echo __('Open for business','open_for_business').' '.__('Settings');?></h2>
			<form method="post" action="options.php">
			<?php
	            // This prints out all hidden setting fields
	            settings_fields('open_for_business');
	            do_settings_sections('open_for_business');
	            submit_button();
	        ?>
			</form>
		</div>
	<?php
	}
   
   
	function settings_field_regular_hours() {
		echo $this->please_elaborate();

		echo '<table>';
		
		$weekdays = $this->weekdays();
		for ($i=0;$i<count($weekdays);$i++) {
			echo '<tr>';
			
			echo '<td>'.$weekdays[$i].'</td>';

			echo '<td>'.__('from','open_for_business');			
			echo ' <select name="open_for_business[open_hour_'.$i.']">';
			echo '<option></option>';
			foreach (range(0,24) as $hour) {
				echo '<option';
				if (!empty($this->options['open_hour_'.$i]) && $this->options['open_hour_'.$i]==$hour) {
					echo ' selected="selected"';
				}
				echo '>'.sprintf("%02d",$hour).'</option>';
			}
			echo '</select>';
		
			echo '<select name="open_for_business[open_minute_'.$i.']">';
			echo '<option></option>';
			foreach (range(0,60) as $hour) {
				echo '<option';
				if (isset($this->options['open_minute_'.$i]) && $this->options['open_minute_'.$i]==$hour) {
					echo ' selected="selected"';
				}
				echo '>'.sprintf("%02d",$hour).'</option>';
			}
			echo '</select>';
			echo '</td>';

			echo '<td>'.__('to','open_for_business');			
			echo ' <select name="open_for_business[close_hour_'.$i.']">';
			echo '<option></option>';
			foreach (range(0,24) as $hour) {
				echo '<option value="'.$hour.'"';
				if (!empty($this->options['close_hour_'.$i]) && $this->options['close_hour_'.$i]==$hour) {
					echo ' selected="selected"';
				}
				echo '>'.sprintf("%02d",$hour).'</option>';
			}
			echo '</select>';
		
			echo '<select name="open_for_business[close_minute_'.$i.']">';
			echo '<option></option>';
			foreach (range(0,60) as $hour) {
				echo '<option';
				if (!empty($this->options['close_minute_'.$i]) && $this->options['close_minute_'.$i]==$hour) {
					echo ' selected="selected"';
				}
				echo '>'.sprintf("%02d",$hour).'</option>';
			}
			echo '</select>';
			echo '</td>';

			echo '</tr>';
		}
		echo '</table>';
	}
	
	private function weekdays() {
		$timestamp = strtotime('next Sunday');
		$days = array();

		for ($i = 0; $i < 7; $i++) {
			$days[] = date_i18n('l', $timestamp);
			$timestamp = strtotime('+1 day', $timestamp);
		}
		
		return $days;		
	}

	function plugins_loaded(){
		load_plugin_textdomain('open_for_business', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}
	
	function shortcode($atts,$content) {
		extract( shortcode_atts( array(
			'elaborate' => false
		), $atts ) );
		
		
		if ($this->well_are_you()) {
			echo __('open','open_for_business');
		} else {
			echo __('closed','open_for_business');
		}
		
		if ($elaborate) {
			echo ' ('.$this->please_elaborate().')';
		}
	}
}
	
	

$Open_for_business = new Open_for_business();