<?php

/*
Plugin Name: Widget for Zen
Plugin URI: https://wordpress.org/plugins/widget-for-yandex-zen/
Description: This plugin provides Widget for Zen Blogging Platform.
Version: 1.2.6
Author: fromgate
Author URI: https://prozen.ru
License: GPL2
*/

require_once ('option.php');
require_once ('wz-zen-util.php');

const CACHE_TIME = 1800; // 30 минут

class ZenWidget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'zen_widget',
			esc_html__( 'Zen' , 'widget-for-yandex-zen'),
			array( 'description' => esc_html__( 'Widget for Zen', 'widget-for-yandex-zen'), )
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		echo $this->zen_get($instance['newscount']);
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$newscount =! empty( $instance['newscount'] ) ? $instance['newscount'] : 1;
		if ($newscount < 1) {
			$newscount = 1;
		}
		?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'widget-for-yandex-zen' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'newscount' ) ); ?>"><?php esc_attr_e( 'News Amount:', 'widget-for-yandex-zen' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'newscount' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'newscount' ) ); ?>" type="number" value="<?php echo esc_attr( $newscount ); ?>">
        </p>

		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['newscount'] = ( ! empty( $new_instance['newscount'] ) ) ? strip_tags( $new_instance['newscount'] ) : 1;
		return $instance;
	}

	function zen_get($newscount) {
		$opts = get_option( 'zw_option_name');
		$media = $opts['zw_zen_media'];

		if (empty( $media )) {
			return esc_html__( 'Channel undefined!', 'widget-for-yandex-zen');
		}

		$cache = $this->getcache(true);
		if (! empty ($cache)) {
			return $cache;
		}

		$json_text = WzZenUtil::get_zen_json($media);

		if (empty( $json_text )) {
			return esc_html__( 'Failed to connect to Zen!', 'widget-for-yandex-zen');
		}

		$showlogo = $newscount;
		if ($opts['zw_show_logo'] == 'never') {
			$showlogo = 0;
		} elseif ($opts['zw_show_logo'] == 'firstonly') {
			$showlogo = 1;
		}

		$logo = $opts ['zw_logo_type'];
		if (empty($logo)) {
			$showlogo = 0;
		}

		$json = json_decode($json_text);

		$count = count ($json->items);

		$div = '';
		// TODO move in options
		$skip_rss = true;
		$added = 0;
		for ($i = 0; $i < $count; $i++) {
			$last = $json->items[$i];
			$title = $last->title;
			$link = $last->link;

			if (parse_url($link, PHP_URL_HOST) != 'dzen.ru' && $skip_rss) {
				continue;
			}
			$url = preg_replace('/\?utm_referrer=.*$/','', $link);

			//Remove turbo links
			if (strpos($url, 'https://yandex.ru/turbo?text=') === 0) {
				$url = str_replace('https://yandex.ru/turbo?text=','', $url);
				$url = urldecode ($url);
				$url = preg_replace('/\&utm_referrer=.*$/','', $url);
				$url = preg_replace('/\&promo=.*$/','', $url);
			}
			$imgUrl = $last->image;

			$id = $last->id;

			if (empty($id)) {
				return $this->getcache(false);
			}

			$div = $div . '<div class="zenimage">' .
			       '<a href="' .$url. '" target="_blank">';
			if (empty($imgUrl)) {
				$imgUrl = plugins_url( '/images/zen_empty.png', __FILE__ );
			}
			$div = $div .'<img src="' . $imgUrl. '" width="330" height="186" />';
			$div = $div .'<div class="zenimage_text">';

			if ($i < $showlogo) {
				$div = $div.'<div height=100%">';
				$div = $div.'<img src="' . ZwSettings::getIcon($logo).'" align="left" hspace="7px" vspace="8px" />';
				$div = $div.'</div>';
			}
			$div = $div.'<div><span>' .$title . '</span></div></div></a></div>';
			$added++;
			if ($added >= $count || $added >= $newscount) {
				break;
			}
		}
		$this->savecache($div);
		return $div;
	}

	function getcache($check_time) {
		$cachefile = dirname( __FILE__ ) . '/tmp/data-' . $this->id . '.txt';
		if (($check_time == false)||(file_exists($cachefile)&&(filesize($cachefile)>0)&&(time()-filemtime($cachefile)) < CACHE_TIME)) {
			$fp = @fopen($cachefile, 'r');
			$data = fread($fp, filesize($cachefile));
			return $data;
		}
		return "";
	}

	function savecache($data) {
		if (empty ($data)) return;
		$cachedir = dirname( __FILE__ ) . '/tmp';
		if (!file_exists($cachedir)) {
			mkdir($cachedir, 0777, true);
		}
		$cachefile = $cachedir . '/data-' . $this->id . '.txt';
		file_put_contents($cachefile, $data);
	}


}

function register_zen_widget() {
	register_widget( 'ZenWidget'  );
}

function register_zen_widget_styles() {
	wp_register_style( 'zenwidgetcss', plugins_url('css/widget-for-yandex-zen.css', __FILE__));
	wp_enqueue_style( 'zenwidgetcss' );
}

add_action( 'widgets_init', 'register_zen_widget' );
add_action( 'wp_enqueue_scripts', 'register_zen_widget_styles' );