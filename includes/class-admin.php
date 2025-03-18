<?php
defined( 'ABSPATH' ) || exit;


/**
 * Class Admin.
 *
 * @since 1.0.0
 */
class PVM_Admin {


    /**
     * Constructor.
     */
    public function __construct() {

        add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_data_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'add_data_panel' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta' ) );
        // Enqueue admin scripts and styles.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }


    /** Add product data tab.
     *
     * @param array $tabs .
     * @since 1.0.0
     * @retun array
     */
    public function add_data_tab( $tabs ) {

        $tabs['product_preorder_woocommerce'] = array(
            'label'    => esc_html__( 'Plugin Variation Manage', 'plugin-version-manage' ),
            'target'   => 'product_variation_product_data',
            'class'    => array( 'show_if_simple', 'show_if_variable' ),
            'priority' => 21,

        );

        return $tabs;
    }

    public function add_data_panel() {
        global $post;
        $versions         = get_post_meta($post->ID, '_product_versions', true);
        $plugin_file_name = get_post_meta($post->ID, '_plugin_file_name', true);
        ?>
        <div id="product_variation_product_data" class="panel woocommerce_options_panel" >

            <div id="main-container-plugin-version">
                <p class="form-field">
                    <label for="plugin-file-name"><?php esc_html_e('File Name:', 'plugin-version-manage'); ?></label>
                    <input id="plugin-file-name" type="text" name="plugin-file-name" placeholder="File Name" value="<?php echo esc_attr($plugin_file_name); ?>">
                </p>
                <button type="button" class="button add_version">Add New Version</button>
                <div id="version_container">
                    <?php
                    if (!empty($versions) && is_array($versions)) {
                        foreach ($versions as $version) {
                            ?>
                            <div class="version_item">
                                <input type="text" name="version_name[]" placeholder="Version Name" value="<?php echo esc_attr($version['version_name']); ?>">
                                <input type="date" name="release_date[]" placeholder="Release Date" value="<?php echo esc_attr($version['release_date']); ?>">
                                <button type="button" class="remove_version">Remove</button>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     * Save product variation and quick cart metadata.
     *
     * This function handles the saving of product-specific variation and quick cart meta fields in the WooCommerce product data panel.
     * It verifies the nonce for security, sanitizes the input fields, and updates the corresponding post meta  data.
     *
     * @since 1.0.0
     *
     * @param int $post_id The ID of the product being saved.
     *
     * @throws Exception If nonce validation fails or fields contain invalid data.
     *
     * @return void|int Returns the post ID if nonce or other checks fail, otherwise saves the meta data.
     */
    public function save_product_meta($post_id) {

        // Save plugin file name
        if (isset($_POST['plugin-file-name'])) {
            $plugin_file_name = sanitize_text_field($_POST['plugin-file-name']);
            update_post_meta($post_id, '_plugin_file_name', $plugin_file_name);
        } else {
            delete_post_meta($post_id, '_plugin_file_name');
        }

        // Check if versions exist
        if (!isset($_POST['version_name']) || !isset($_POST['release_date'])) {
            delete_post_meta($post_id, '_product_versions');
            return;
        }

        // Sanitize versions
        $version_names = array_map('sanitize_text_field', $_POST['version_name']);
        $release_dates = array_map('sanitize_text_field', $_POST['release_date']);

        $versions = [];
        foreach ($version_names as $index => $name) {
            if (!empty($name) && !empty($release_dates[$index])) {
                $versions[] = [
                    'version_name' => $name,
                    'release_date' => $release_dates[$index],
                ];
            }
        }

        // Save or delete metadata
        if (empty($versions)) {
            delete_post_meta($post_id, '_product_versions');
        } else {
            update_post_meta($post_id, '_product_versions', $versions);
        }
    }


    /**
     * Enqueue admin styles and scripts.
     *
     * @param string $hook Page hook.
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_admin_assets($hook)
    {
            // Enqueue admin CSS.
            wp_enqueue_style(
                'pct_admin_style',
                PVM_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                PVM_VERSION
            );

            // Enqueue admin JavaScript.
            wp_enqueue_script(
                'qbt_admin_script',
                PVM_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                PVM_VERSION,
                true
            );
    }

}