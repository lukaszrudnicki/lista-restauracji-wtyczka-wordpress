<?php
class Settings_Page {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_settings_page() {
        add_options_page(
            'Ustawienia Listy Restauracji',
            'Lista Restauracji',
            'manage_options',
            'lista-restauracji-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('lr_settings_group', 'lr_google_maps_api_key');
        register_setting('lr_settings_group', 'lr_marker_icon');
        register_setting('lr_settings_group', 'lr_cluster_icon');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Ustawienia Listy Restauracji</h1>
            <form method="post" action="options.php">
                <?php settings_fields('lr_settings_group'); ?>
                <?php do_settings_sections('lr_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Klucz API Google Maps</th>
                        <td><input type="text" name="lr_google_maps_api_key" value="<?php echo esc_attr(get_option('lr_google_maps_api_key')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">URL ikony markera</th>
                        <td><input type="text" name="lr_marker_icon" value="<?php echo esc_attr(get_option('lr_marker_icon')); ?>" /></td>
                        </tr>
                    <tr valign="top">
                        <th scope="row">URL ikony klastra</th>
                        <td><input type="text" name="lr_cluster_icon" value="<?php echo esc_attr(get_option('lr_cluster_icon')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
                <?php
echo '<pre>';
echo 'Saved cluster icon: ' . esc_html(get_option('lr_cluster_icon'));
echo '</pre>';
?>
            </form>
        </div>
        <?php
    }
}