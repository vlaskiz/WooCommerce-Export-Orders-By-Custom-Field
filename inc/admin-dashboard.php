<?php

namespace WooExportOrdersBcf;

defined('ABSPATH') || exit;

class WEOBC_Admin_Dashboard {

    public function __construct() {

        // Add settings page to menu
        add_action('admin_menu', array($this, 'add_menu_item'), 99);

    }

    public function add_menu_item() {
        add_submenu_page(
            'woocommerce',
            __('Order Exports', 'woo-export-order-bcf'),
            __('Order Exports', 'woo-export-order-bcf'),
            'manage_options',
            'woo_export_order_bcf',
            array( $this, 'export_settings_callback' )
        );
    }

    /**
     * Add Setting Page Html
     */
    public function export_settings_callback() {

        $date = new \DateTime();

        $statuses = array(
            'wc-pending' => __('Pending', 'woocommerce' ),
            'wc-processing' => __('Processing', 'woocommerce' ),
            'wc-on-hold' => __('On-hold', 'woocommerce' ),
            'wc-completed' => __('Completed', 'woocommerce' ),
            'wc-cancelled' => __('Canceled', 'woocommerce' ),
            'wc-refunded' => __('Refunded', 'woocommerce' ),
            'wc-failed' => __('Failed', 'woocommerce' ),
        );

        $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'export_orders';
        $export_orders_tab_url = '?page=woo_export_order_bcf&tab=export_orders';
        $settings_tab_url = '?page=woo_export_order_bcf&tab=export_settings';

        if ( isset($_POST['email_address']) ) {
            update_option( "woebcf_admin_email_address", $_POST["email_address"] );
        }

        $email_address = get_option( "woebcf_admin_email_address" );

        ?>

        <div class="wrap">
                
            <h2><?php _e('WooCommerce Order Exports', 'woo-export-order-bcf'); ?></h2>

            <?php

            if ( isset($_POST['woo_export_order_bcf_save'] ) ) {
                _e( 'Settings have been saved.', 'woo-export-order-bcf' );
            }

            ?>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr($export_orders_tab_url); ?>" class="nav-tab <?php echo $active_tab == 'export_orders' ? 'nav-tab-active' : ''; ?>"><?php _e('Export Orders', 'woo-export-order-bcf'); ?></a>
                <a href="<?php echo esc_attr($settings_tab_url); ?>" class="nav-tab <?php echo $active_tab == 'export_settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'woo-export-order-bcf'); ?></a>
            </h2>

            <?php if ( $active_tab == 'export_orders' ) : ?>
            <form method="post" id="export_order" action="">
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">
                            <?php _e("Order status:", "woo-export-order-bcf"); ?>
                        </th>
                        <td>
                            <select id="woebcf_order_status" class="wc-enhanced-select-nostd" data-placeholder="<?php esc_attr_e('Select Order Status', 'woo-export-order-bcf'); ?>" name="woebcf_order_status" multiple="multiple" style="width:500px;max-width:100%;">
                                <option value=""><?php esc_attr_e('Select Order Status', "woo-export-order-bcf"); ?></option>
                                <?php
                                foreach( $statuses as $status => $label ){
                                    echo '<option value="' . esc_attr($status) . '">' . $label . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('If left empty, completed orders will be exported.', 'woo-export-order-bcf'); ?></p>
                        </td>
                    </tr>
                                                
                    <tr valign="top">
                        <th scope="row">
                            <?php _e("Delivery date:", "woo-export-order-bcf"); ?>
                        </th>
                        <td>
                            <input type="date" name="woebcf_order_date" placeholder="<?php esc_attr_e("Select date", "woo-export-order-bcf"); ?>" value="<?php echo esc_attr($date->format("Y-m-d")) ?>" required style="width:500px;max-width:100%;" />
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php _e("Send to emails:", "woo-export-order-bcf"); ?>
                        </th>
                        <td>
                            <input type="text" name="woebcf_email_address" placeholder="<?php esc_attr_e('Enter email address(-es)', 'woo-export-order-bcf'); ?>" value="" style="width:500px;max-width:100%;"<?php echo $email_address ? '' : ' readonly'; ?> />
                            <p class="description"><?php _e("Single or comma separated list of emails.", "woo-export-order-bcf"); ?></p>
                            <?php if ( !$email_address ) : ?>
                            <span class="description"><?php printf(
                                __('Admin (From) email is not set. Please set it in the %1$ssettings tab%2$s tab if you want to use this feature.', "woo-export-order-bcf"),
                                '<a href="'.esc_attr($settings_tab_url).'">',
                                '</a>'
                            ); //_e("Admin (From) email is not set. Please set it in the settings tab if you want to use this feature.", "woo-export-order-bcf"); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>

                </table>

                <?php wp_nonce_field( 'woo_export_pdf_perform', 'woo_export_pdf_nonce' ); ?>

                <?php submit_button( __("Export", "woo-export-order-bcf"), 'primary', 'woo_export_order_bcf', false ); ?>

                <div class="spinner" style="float:none"></div>

            </form>
            <?php endif; ?>

            <?php if ( $active_tab == 'export_settings' ) : ?>
            <form method="post" action="">
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">
                            <?php _e("Admin email (From email):", "woo-export-order-bcf"); ?>
                        </th>
                        <td>
                            <input type="email" name="email_address" placeholder="<?php esc_attr_e("Enter email address", "woo-export-order-bcf"); ?>" value="<?php echo $email_address ? $email_address : ''; ?>" style="width:500px;max-width:100%;"/>
                        </td>
                    </tr>

                </table>

                <?php submit_button( __("Save", "woo-export-order-bcf"), 'primary', 'woo_export_order_bcf_save', false ); ?>

            </form>
            <?php endif; ?>

        </div><!-- /.wrap -->
        <?php
    }

}

$WEOBC_Admin_Dashboard = new WEOBC_Admin_Dashboard();
