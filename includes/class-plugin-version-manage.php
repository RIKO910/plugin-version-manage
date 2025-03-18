<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class Product_Countdown_Timer.
 *
 * @since 1.0.0
 */
class PVM_plugin_Version_Manage {

    /**
     * File.
     *
     * @var string $file File
     *
     * @since 1.0.0
     */
    public string $file;

    /**
     * Version.
     *
     * @var mixed|string $version Version
     *
     * @since 1.0.0
     */
    public string $version = '1.0.0';

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct( $file, $version = '1.0.0' ) {
        $this->file    = $file;
        $this->version = $version;
        $this->define_constant();
        $this->activation();
        $this->deactivation();
        $this->init_hooks();
        $this->register_api_route();
    }

    /**
     * Register REST API route for version management.
     *
     * @since 1.0.0
     */
    public function register_api_route() {
        add_action( 'rest_api_init', function() {
            register_rest_route( 'wooxperto-plugin', '/latest-version/(?P<product_id>\d+)', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_latest_plugin_version' ),
                'args'     => array(
                    'product_id' => array(
                        'required' => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        }
                    ),
                ),
            ) );
        });
    }

    /**
     * Callback function for the /latest-version API route.
     *
     * @since 1.0.0
     */
    public function get_latest_plugin_version( WP_REST_Request $request ) {
        $product_id       = $request->get_param( 'product_id' );
        $versions         = get_post_meta( $product_id, '_product_versions', true );
        $latest_version   = 'N/A';

        if ( ! empty( $versions ) && is_array( $versions ) ) {
            usort( $versions, function( $a, $b ) {
                return version_compare( $b['version_name'], $a['version_name'] );
            });

            $latest_version = $versions[0]['version_name'];
        }

        return new WP_REST_Response( array(
            'latest_version' => $latest_version
        ), 200 );
    }


    /**
     * Define Constant.
     *
     * @return void
     * @since 1.0.0
     */
    public function define_constant() {
        define( 'PVM_VERSION', $this->version );
        define( 'PVM_PLUGIN_DIR', plugin_dir_path( $this->file ) );
        define( 'PVM_PLUGIN_URL', plugin_dir_url( $this->file ) );
        define( 'PVM_PLUGIN_BASENAME', plugin_basename( $this->file ) );
    }

    /**
     * Activation.
     *
     * @return void
     * @since 1.0.0
     */
    public function activation() {
        register_activation_hook( $this->file, array( $this, 'activation_hook' ) );
    }

    /**
     * Activation hook.
     *
     * @return void
     * @since 1.0.0
     */
    public function activation_hook() {
        update_option( 'PVM_VERSION', $this->version );
    }

    /**
     * Deactivation.
     *
     * @return void
     * @since 1.0.0
     */
    public function deactivation() {
        register_deactivation_hook( $this->file, array( $this, 'deactivation_hook' ) );
    }

    /**
     * Deactivation hook
     *
     * @return void
     * @since 1.0.0
     */
    public function deactivation_hook() {
        delete_option( 'PVM_VERSION' );
    }

    /**
     * Init hook.
     *
     * @return void
     * @since 1.0.0
     */
    public function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'woocommerce_init', array( $this, 'init' ) );
    }

    /**
     * Load textdomain.
     *
     * @return void
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'plugin-version-manage', false, dirname( plugin_basename( $this->file ) ) . '/languages/' );
    }

    /**
     * Init.
     *
     * @since 1.0.0
     * @return void
     */
    public function init() {

        if ( is_admin() ) {
            new PVM_Admin();
        }

        add_action('woocommerce_after_my_account', array($this, 'show_completed_order_items'), 10, 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_post_download_product_version', array($this, 'handle_product_version_download'));
        add_action('admin_post_nopriv_download_product_version', array($this, 'handle_product_version_download'));
    }

    function show_completed_order_items($downloads) {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $args = array(
            'customer_id' => $user_id,
            'status'      => 'completed',
            'limit'       => -1
        );

        $orders = wc_get_orders($args);

        ?>
        <h2><?php esc_html_e('My Products', 'plugin-version-manage'); ?></h2>

        <?php
        if (!$orders) {
            ?>
            <p><?php esc_html_e('No completed orders found.', 'plugin-version-manage'); ?></p>
            <?php
            return;
        }
        ?>

        <style>
            table.shop_table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            table.shop_table th, table.shop_table td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
                font-size: 14px;
            }
            table.shop_table th {
                background-color: #f4f4f4;
                font-weight: bold;
            }
            table.shop_table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            table.shop_table td {
                vertical-align: middle;
            }
            table.shop_table td a {
                text-decoration: none;
                color: #333;
            }
            table.shop_table td a:hover {
                text-decoration: underline;
            }
            .download-container {
                display: flex;
                align-items: center;
                gap: 10px; 
            }
            .version-dropdown {
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 5px;
                font-size: 14px;
                width: fit-content;
            }
            .download-button {
                padding: 8px 15px;
                background-color: #9078B6;
                color: white;
                font-size: 14px;
                border-radius: 5px;
                cursor: pointer;
                border:none;
                transition: background 0.3s ease;
            }
            .download-button:hover {
                background-color: #7a5d9e;
            }
            .no-versions {
                font-style: italic;
                color: #666;
            }
            .my-all-plugin-info-container {
                width: 100%;
                display: block;
                margin: auto;
                overflow-x: auto; /* Enable horizontal scrolling */
                white-space: nowrap; /* Prevent wrapping of table content */
            }
            .my-all-plugin-info-container table {
                width: 100%;
                min-width: 800px; /* Set a minimum width for the table */
            }
        </style>
        <div class="my-all-plugin-info-container">
        <table class="shop_table shop_table_responsive my_account_orders woocommerce-orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product Name</th>
                    <th>License Key</th>
                    <th>Number of Active Sites</th>
                    <th>Expiry Date</th>
                    <th>Added Sites</th>
                    <th>Download</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($orders as $order) {
                    $order_id     = $order->get_id();
                    $license_info = get_license_key_info_by_order_id($order_id);

                    // Plugin version info.
                    $plugin_versions = [];

                    foreach ($order->get_items() as $item) {
                        $product_id = $item->get_product_id();
                        $product    = wc_get_product($product_id);

                        if ($product) {
                            $parent_id = $product->get_parent_id();
                            $meta_id = $parent_id ? $parent_id : $product_id;

                            $plugin_versions[$product_id] = get_post_meta($meta_id, '_product_versions', true) ?: [];
                        }
                    }

                    // Get subscription details if available
                    $subscriptions           = wcs_get_subscriptions_for_order($order_id);
                    $subscription_start_date = '';
                    $subscription_end_date   = '';

                    if (!empty($subscriptions)) {
                        $subscription            = reset($subscriptions);
                        $subscription_start_date = $subscription->get_date('start');
                        $subscription_end_date   = $subscription->get_date('next_payment');
                    }

                    // Display the results
                    if ($license_info) {
                        foreach ($license_info as $index => $info) {
                            $product_id   = $info['product_id'] ?? null;
                            $product      = wc_get_product($product_id);
                            $product_name = $product->get_name();
                            $versions     = $product_id ? ($plugin_versions[$product_id] ?? []) : [];

                            echo '<tr>';
                            echo '<td>' . $info['order_id'] . '</td>';
                            echo '<td>' . $product_name . '</td>';
                            echo '<td>' . $info['license_key'] . '</td>';
                            echo '<td>' . ($info['no_of_active_site'] == '-1' ? 'Unlimited' : $info['no_of_active_site']) . '</td>';
                            echo '<td>' . $info['expiry_date'] . '</td>';
                            echo '<td>' . ($info['added_sites'] > 0 ? $info['added_sites'] : 0) . '</td>';
                            echo '<td>';
                            if (!empty($versions)) {
                                echo '<form method="POST" action="' . esc_url(admin_url('admin-post.php')) . '">';
                                echo '<div class="download-container">';
                                echo '<select class="version-dropdown" name="selected_version">';
                                foreach ($versions as $version) {
                                    $version_release_date = $version['release_date'];
                                    if ($subscription_end_date > $version_release_date) {
                                        echo '<option value="' . esc_attr($version['version_name']) . '">' . esc_html($version['version_name']) . '</option>';
                                    }
                                }
                                echo '</select>';

                                echo '<input type="hidden" name="product_id" value="' . esc_attr($product_id) . '">';
                                echo '<input type="hidden" name="action" value="download_product_version">';
                                echo '<button type="submit" class="download-button">Download</button>';
                                echo '</div>';
                                echo '</form>';
                            } else {
                                echo '<span class="no-versions">No versions available</span>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7">' . __('No license key information available for this order.', 'woocommerce') . '</td></tr>';
                    }
                }
                ?>
            </tbody>
        </table>
        </div>

        <?php
    }


    function handle_product_version_download() {
        if (!isset($_POST['selected_version'], $_POST['product_id'])) {
        wp_die(__('Invalid request!', 'woocommerce'));
        }
    
        $selected_version = sanitize_text_field($_POST['selected_version']);
        $product_id       = intval($_POST['product_id']);
        $plugin_file_name = get_post_meta($product_id, '_plugin_file_name', true);

        // Construct the file URL based on the selected version
        $file_url = "https://www.wooxperto.com/our-plugins-zip/{$plugin_file_name}/xyzk-{$selected_version}.zip";

        if (!$plugin_file_name) {
            wp_send_json_error(__('Plugin file not found!', 'woocommerce'));
        }
    
        // Check if the file exists
        $headers = get_headers($file_url, 1);
        if (strpos($headers[0], '200') === false) {
            wp_die(__('File not available!', 'woocommerce'));
        }
    
        // Force file download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($plugin_file_name) . '.zip"');
        readfile($file_url);
        exit;
    }

    public function enqueue_frontend_assets(){
        wp_enqueue_style(
            'pct_admin_style',
            PVM_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            PVM_VERSION
        );
    }

}