<?php
/*
Plugin Name: Are you open?
Plugin URI: http://slimndap.com
Description: 
Author: Jeroen Schmit, Slim & Dapper
Author URI: http://slimndap.com/
Version: 1.0
*/

class Areyouopen {
	function __construct() {
		if (is_admin()) {
			add_action( 'admin_init', array($this,'admin_init'));
			add_action( 'admin_menu', array($this, 'admin_menu' ));
		}
		
		$this->options = get_option('are-you-open');
	}
	
	function open() {
		$weekday = date('N');
		$now = time();
		
		if (
			empty($this->options['open_hour_'.$weekday]) || 
			empty($this->options['open_minute_'.$weekday]) || 
			empty($this->options['close_hour_'.$weekday]) || 
			empty($this->options['close_hour_'.$weekday])
		) {
			// opening or closing hours are not properly set.
			return false;
		}
		
		return true;
	}

	function admin_init() {
		register_setting(
            'are-you-open', // Option group
            'are-you-open' // Option name
        );

		add_settings_section(
			'opening-hours', // ID
			__('Opening hours','are-you-open'), // Title
			'', // Callback
			'are-you-open' // Page
		);
		
		add_settings_field(
			'regular-hours', // ID
			__('Regular hours','are-you-open'), // Title
			array( $this, 'settings_field_regular_hours' ), // Callback
			'are-you-open', // Page
			'opening-hours' // Section
		);

		add_settings_field(
			'exceptions', // ID
			__('Exceptions','are-you-open'), // Title
			array( $this, 'settings_field_exceptions' ), // Callback
			'are-you-open', // Page
			'opening-hours' // Section
		);
	}

	function admin_menu() {
		add_options_page( __('Are you open?','are-you-open'), 'Are you open?', 'manage_options', 'are-you-open-admin', array( $this, 'admin_page' ));
	}	

	public function admin_page() {
        ?>
		<div class="wrap">
			<h2><?php echo __('Are you open?','are-you-open').' '.__('Settings');?></h2>
			<form method="post" action="options.php">
			<?php
	            // This prints out all hidden setting fields
	            settings_fields('are-you-open');
	            do_settings_sections('are-you-open');
	            submit_button();
	        ?>
			</form>
		</div>
	<?php
   }
   
   
	function settings_field_regular_hours() {
		echo $this->open();
	
		echo '<table>';
		
		$weekdays = $this->weekdays();
		for ($i=0;$i<count($weekdays);$i++) {
			echo '<tr>';
			
			echo '<td>'.$weekdays[$i].'</td>';

			echo '<td>'.__('from','are-you-open');			
			echo ' <select name="are-you-open[open_hour_'.$i.']">';
			echo '<option></option>';
			foreach (range(0,24) as $hour) {
				echo '<option';
				if (!empty($this->options['open_hour_'.$i]) && $this->options['open_hour_'.$i]==$hour) {
					echo ' selected="selected"';
				}
				echo '>'.sprintf("%02d",$hour).'</option>';
			}
			echo '</select>';
		
			echo '<select name="are-you-open[open_minute_'.$i.']">';
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

			echo '<td>'.__('to','are-you-open');			
			echo ' <select name="are-you-open[close_hour_'.$i.']">';
			echo '<option></option>';
			foreach (range(0,24) as $hour) {
				echo '<option value="'.$hour.'"';
				if (!empty($this->options['close_hour_'.$i]) && $this->options['close_hour_'.$i]==$hour) {
					echo ' selected="selected"';
				}
				echo '>'.sprintf("%02d",$hour).'</option>';
			}
			echo '</select>';
		
			echo '<select name="are-you-open[close_minute_'.$i.']">';
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
}
	
	

$Areyouopen = new Areyouopen();