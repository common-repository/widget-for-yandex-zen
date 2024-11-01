<?php
require_once ('wz-zen-util.php');

class ZwSettings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            __('Widget for Zen Settings', 'widget-for-yandex-zen'),
            __('Widget for Zen', 'widget-for-yandex-zen'),
            'manage_options',
            'zw-settings',
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option('zw_option_name');
        ?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('zw_option_group');
                do_settings_sections('zw-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'zw_option_group', // Option group
            'zw_option_name', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'zw_section_1', // ID
            __('Widget for Zen Settings', 'widget-for-yandex-zen'), // Title
            null, //aarray( $this, 'print_section_info' ), // Callback
            'zw-settings' // Page
        );

        add_settings_field(
            'zw_zen_media', // ID
            __('Zen URL', 'widget-for-yandex-zen'), // Title
            array($this, 'zw_zen_media_callback'), // Callback
            'zw-settings', // Page
            'zw_section_1' // Section
        );

        add_settings_field(
            'zw_show_logo', // ID
            __('Show Logo', 'widget-for-yandex-zen'), // Title
            array($this, 'zw_show_logo_callback'), // Callback
            'zw-settings', // Page
            'zw_section_1' // Section
        );

        add_settings_field(
            'zw_logo_type', // ID
            __('Logo Style', 'widget-for-yandex-zen'), // Title
            array($this, 'zw_logo_type_callback'), // Callback
            'zw-settings', // Page
            'zw_section_1' // Section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array Returns updated fields
     */
    public function sanitize($input)
    {
        $new_input = array();
        if (isset($input['zw_zen_media'])) {
            $media = sanitize_text_field($input['zw_zen_media']);
            $media = str_replace(array('https://', 'http://', 'zen.yandex.ru/media/', 'zen.yandex.com/media/', 'zen.yandex.ru/', 'zen.yandex.com/','zen.yandex.ru/profile/editor/',
	            'dzen.ru/media/', 'dzen.ru/profile/editor/'), '', $media);
            $new_input['zw_zen_media'] = $media;
        }

        if (isset($input['zw_show_logo'])) {
            $new_input['zw_show_logo'] = $input['zw_show_logo'];
        }

        if (isset($input['zw_logo_type'])) {
            $new_input['zw_logo_type'] = $input['zw_logo_type'];
        }

        $files = glob(dirname(__FILE__) . '/tmp/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print __('Enter your settings below:', 'widget-for-yandex-zen');
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function zw_zen_media_callback()
    {
        $media = isset($this->options['zw_zen_media']) ? esc_attr($this->options['zw_zen_media']) : '';
        printf('<input type="text" id="zw_zen_media" name="zw_option_name[zw_zen_media]" value="%s" />', $media);
        printf('<br><small>' . __('Enter URL of your Zen Blog.', 'widget-for-yandex-zen') . '<br />');
        if (empty($media)) {
            printf(__('Example: ', 'widget-for-yandex-zen') . ' <code>%s</code>' . '</small>', 'https://dzen.ru/id/5a3def60e86a9e50b401ab4a');
        } else {
            $media_url = 'https://dzen.ru/' . $media;
            printf(__('Current value:', 'widget-for-yandex-zen') . ' <code>%s</code>' . '</small>', $media_url);
        }
    }

    public function zw_show_logo_callback() {
        $checked = isset( $this->options['zw_show_logo'] ) ? esc_attr( $this->options['zw_show_logo']) : 'always';
        printf('<select name="zw_option_name[zw_show_logo]">');
        printf('<option value="always" %s>'.__('Always', 'widget-for-yandex-zen').'</option>',selected ($checked, "always"));
        printf('<option value="firstonly" %s>'.__('On First Only', 'widget-for-yandex-zen').'</option>',selected ($checked, "firstonly"));
        printf('<option value="never" %s>'.__('Never', 'widget-for-yandex-zen').'</option>',selected ($checked, "never"));
        printf('</select>');
        printf('<br><small>'.__('Choose the way to show Zen logo on the post preview.', 'widget-for-yandex-zen').'</small>');
    }

    /**
     *
     */
    public function zw_logo_type_callback()
    {
        $selected = isset( $this->options['zw_logo_type'] ) ? esc_attr( $this->options['zw_logo_type']) : 'black_16';
        echo ('<table><tbody valign="middle">');
	    printf ('<tr><td>%s</td><td>%s</td></tr>',
		    $this->zw_radio_button ('star_32', $selected),
		    $this->zw_radio_button ('star_16', $selected));
        printf ('<tr><td>%s</td><td>%s</td></tr>',
            $this->zw_radio_button ('blue_32', $selected),
            $this->zw_radio_button ('blue_16', $selected));
        printf ('<tr><td>%s</td><td>%s</td></tr>',
            $this->zw_radio_button ('red_32', $selected),
            $this->zw_radio_button ('red_16', $selected));
        printf ('<tr><td>%s</td><td>%s</td></tr>',
            $this->zw_radio_button ('white_32', $selected),
            $this->zw_radio_button ('white_16', $selected));
        printf ('<tr><td>%s</td><td>%s</td></tr>',
            $this->zw_radio_button ('black_32', $selected),
            $this->zw_radio_button ('black_16', $selected));
	    printf ('<tr><td>%s</td><td>%s</td></tr>',
		    $this->zw_radio_button ('broken_32', $selected),
		    $this->zw_radio_button ('broken_16', $selected));
        echo ('</tbody></table>');
        printf('<br><small>%s</small>',__('Choose the style of the Zen logo.', 'widget-for-yandex-zen'));
    }

    private function zw_radio_button ($logo, $selected) {
        $checked = $logo == $selected ? ' checked="checked"' : '';
        return '<input type="radio" name="zw_option_name[zw_logo_type]" id="'
            .$logo
            .'" value="'
            .$logo
            .'" '
            .$checked
            .'><label for="'
            .$logo
            .'"><img src="'
            .$this->getIcon($logo)
            . '" /></label><br>';
    }

    public static function getIcon($logoType) {
        return plugins_url( '/images/zen_logo_'.$logoType.'.png', __FILE__ );
    }
}

if( is_admin() ) {
    $my_settings_page = new ZwSettings();
}