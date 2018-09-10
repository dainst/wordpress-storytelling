<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	widget for esa map
 * @link 		
 * @author 		Philipp Franck
 *
 * Searches in the Eagle Database directly.
 *
 */
class esa_map_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array( 
			'classname' => 'ESA Map',
			'description' => 'Displays a map of all posts with embedded epigraphic content which have geographic coordinates.',
		);
		parent::__construct('esa_map_widget', 'ESA Map', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget($args, $instance) {
		$h = (isset($instance['height']) and $instance['height'] > 0) ? " style='height:{$instance['height']}px'" : '';
		echo "<div id='esa_items_overview_map' $h>&nbsp;</div>";
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form($instance) {
		// outputs the options form on admin
		$height = (isset($instance['height'])) ? $instance['height'] : '';
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php _e( 'Height:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="number" value="<?php echo esc_attr($height); ?>">
			</p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['height'] = ( ! empty( $new_instance['height'] ) ) ? strip_tags( $new_instance['height'] ) : '';

		return $instance;
	}
}
?>