<?php
/**
 * @wordpress-plugin
 * Plugin Name:         WooCommerce Export Orders By Custom Field
 * Plugin URI:          https://avlasovas.me/
 * Description:         Export WooCommerce orders by custom fields to a PDF file.
 * Version:             1.0.9
 * Author:              Andrius Vlasovas
 * Author URI:          https://avlasovas.me/
 * Requires at least:   4.4
 * Tested up to:        5.2
 * WC requires at least: 3.0
 * WC tested up to:     3.8
 * License:             GNU General Public License v3.0
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path:         /languages/
 * GitHub Plugin URI:   https://github.com/vlaskiz/WooCommerce-Export-Orders-By-Custom-Field
 * text domain:         woo-export-order-bcf
 */

namespace WooExportOrdersBcf;

if ( ! defined( 'ABSPATH' ) ) {
    return; // Exit if accessed directly.
}

define('WOO_EOBCF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOO_EOBCF_PLUGIN_PATH', plugin_dir_path(__FILE__));

class Woo_Export_Orders_Bcf {

    private $upload_dir,
            $upload_url;

    public function __construct() {

        $this->upload_dir = $this->upload_folder('basedir');
        $this->upload_url = $this->upload_folder('baseurl');

        $this->includes();

        add_filter( 'woocommerce_screen_ids', [ $this, 'set_wc_screen_ids' ] );

        add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ], 15 );

        add_action( 'wp_ajax_woo_export_orders_bcfpdf', [ $this, 'prepare_export_file' ] );

        add_action( 'init', [ $this, 'wpdocs_load_textdomain' ] );

    }
  
    /**
     * Load plugin textdomain.
     */
    public function wpdocs_load_textdomain() {
        load_plugin_textdomain( 'woo-export-order-bcf', false, basename(dirname(__FILE__)) . '/languages' ); 
    }

    /*
    * Include plugin files
    */
    private function includes() {
        require_once( 'inc/admin-dashboard.php' );
    }

    /**
     * Load WC scripts on our settings page
     *
     * @param array $screen
     * @return array
     */
    public function set_wc_screen_ids( $screen ){
        $screen[] = 'woocommerce_page_woo_export_order_bcf';
        
        return $screen;
    }

    /**
     * Loads backend scripts.
     */
    public function admin_scripts( $hook_suffix ) {

        if ( isset($_GET['page']) && $_GET['page'] === 'woo_export_order_bcf' ) {
            wp_register_script( 'woo-export-orders-bcf-admin', WOO_EOBCF_PLUGIN_URL . '/assets/js/admin.js', 'jquery' );
            wp_enqueue_script( 'woo-export-orders-bcf-admin' );

            $dataToBePassed = array(
                'ajaxurl'   => admin_url( 'admin-ajax.php' ),
            );

            wp_localize_script( 'woo-export-orders-bcf-admin', 'weobcf_data', $dataToBePassed); 
        }
    }

    /**
     * Output json data and response via AJAX to frontend
     *
     * @param string $status
     * @param string $message
     * @param array $data
     * @return json
     */
    public function ajax_submit($status, $message, $data = NULL) {
    
        $result = array (
            'status' => $status,
            'message' => $message,
            'data' => $data
        );

        $output = json_encode($result);

        exit($output);
    }

    /**
     * Ajax action to generate PDF file
     *
     * @return json
     */
    public function prepare_export_file() {

        $posted_data = $_POST;
        $dataArray = [];

        if ( ! isset( $posted_data['woo_export_pdf_nonce'] ) 
            || ! wp_verify_nonce( $posted_data['woo_export_pdf_nonce'], 'woo_export_pdf_perform' ) 
        ) {
            $this->ajax_submit( 'error', __('Sorry, this action cannot be performed for security reasons.', 'woo-export-order-bcf'), $dataArray );
            die();
        }

        if ( empty($posted_data['woebcf_order_date']) ) {
            $this->ajax_submit( 'error', __('Select delivery date.', 'woo-export-order-bcf'), $dataArray );
            die();
        }

        $statuses = array( "wc-completed" );
        if ( !empty($posted_data['woebcf_order_status']) && $posted_data['woebcf_order_status'] ) {
            $statuses = $posted_data['woebcf_order_status'];
        }

        $date = date_create($posted_data['woebcf_order_date']);
        $date = date_format($date, 'Y-m-d');

        $right_now = current_time('Hi');

        //get orders that not sent before
        $orders = $this->get_orders( $date, $statuses);

        if ( $orders ) {

            require_once __DIR__ . '/vendor/autoload.php';

            if ( ! is_dir( $this->upload_dir ) ) {
                wp_mkdir_p($this->upload_dir);
            }

            $mpdf = new \Mpdf\Mpdf( [
                'tempDir' => $this->upload_dir,
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
            ] );

            $mpdf->setFooter('{PAGENO} / {nb}');

            $file_name = 'exported-order-' . $date . '.pdf';

            $all_items = array();

            ob_start();

            //loop over orders
            foreach ( $orders as $orderID ) :

                $order = new \WC_Order( $orderID );
                $order_items = $order->get_items();
                $customer_note = $order->get_customer_note();

                ?>
                <div class="single-order-row" style="margin-bottom:15pt;padding-bottom:15pt;border-bottom:2px solid #dddddd">

                    <div class="order-info" style="margin-bottom:5pt">

                        <img src="<?php echo WOO_EOBCF_PLUGIN_URL; ?>/assets/img/rectangle-ico.png" style="width:12pt;height:12pt;vertical-align:middle"/>

                        &nbsp;<span class="order-id order-detail-single"><?php
                        echo $order->get_meta('_order_number_formatted') ?
                            '#'. $order->get_meta('_order_number_formatted') :
                            '#'. $order->get_id();
                        ?></span>&nbsp;&nbsp;&nbsp;&nbsp;

                        <span class="delivery-date order-detail-single"><b><?php echo $order->get_meta('_billing_date'); ?></b></span>&nbsp;&nbsp;&nbsp;&nbsp;

                        <span class="delivery-time order-detail-single"><b><u><?php echo $order->get_meta('_billing_time'); ?></u></b></span>&nbsp;&nbsp;&nbsp;&nbsp;

                        <span class="customer-name order-detail-single"><b><?php echo __('Client:', 'woo-export-order-bcf'); ?></b>&nbsp;<?php echo $order->get_billing_first_name() . '&nbsp;' . $order->get_billing_last_name(); ?></span>&nbsp;&nbsp;&nbsp;&nbsp;

                        <span class="customer-phone"><b><?php echo $order->get_billing_phone(); ?></b></span>

                    </div>

                    <?php if ( $customer_note ) : ?>
                    <div class="order-note" style="margin-bottom:5pt">
                        <span><b><?php _e('Customer note:', 'woo-export-order-bcf'); ?>&nbsp;</b></span><?php echo $customer_note; ?>
                    </div>
                    <?php endif; ?>

                    <div class="order-items" style="line-height:1.6">
                        <?php
                        foreach( $order_items as $item_id => $item ) {

                            //Get the variation ID
                            $product_id = $item->get_variation_id();
                            $variation_id = $item->get_variation_id();

                            if ( !$product_id ) {
                                //Get the product ID
                                $product_id = $item->get_product_id();
                            }

                            $item_meta_data = $item->get_meta_data();

                            //Get the WC_Product object
                            $product = $item->get_product();

                            // The quantity
                            $product_qty = $item->get_quantity();

                            // The product name
                            $product_name = $item->get_name();

                            $unit = get_post_meta( $product_id, 'unit', true );

                            $temp_item = [
                                'name'  => $product_name,
                            ];

                            ?>
                            <div class="order-item-single" style="margin-top:4pt">
                                <span>-&nbsp;<?php echo $product_name; ?></span>
                                <span><?php echo $unit ? '&nbsp;('. __('unit', 'woo-export-order-bcf') .'&nbsp;' . $unit . ')': ''; ?><b>&nbsp;(x<?php echo $product_qty; ?>)</b></span>
                                <span>&nbsp;-&nbsp;<b><?php echo wc_price($item->get_total()); ?></b></span>
                                <?php
                                if ( $item_meta_data ) {
                                    foreach( $item_meta_data as $meta_data ) {
                                        $meta_data_as_array = $meta_data->get_data();

                                        if ( $meta_data_as_array ) {
                                            unset($meta_data_as_array['id']);

                                            $attribute_name = get_taxonomy( $meta_data_as_array['key'] )->labels->singular_name;
                                            $attribute_value = get_term_by( 'slug', $meta_data_as_array['value'], $meta_data_as_array['key'] )->name;
    
                                            if ( $attribute_name && $attribute_value ) {
                                                $temp_item_data = [
                                                    'key'   => $meta_data_as_array['key'],
                                                    'value' => $meta_data_as_array['value'],
                                                    'formatted_name'  => $attribute_name,
                                                    'formatted_value' => $attribute_value,
                                                ];
        
                                                $temp_item['variations'][] = $temp_item_data;
        
                                                ?>
                                                <div class="order-item-single__variation" style="padding-left:12pt;font-size:10pt;line-height:1.3;">
                                                    <span><b><?php echo $attribute_name; ?></b>:&nbsp;<?php echo $attribute_value; ?></span>
                                                </div>
                                                <?php
                                            }
                                        }
                                    }
                                }
                                ?>
                            </div>
                            <?php

                            $temp_items['products'][] = $temp_item;

                            if ( isset( $all_items['products'] ) ) {
                                foreach ( $all_items['products'] as $key => $products_array ) {
                                    if ( $products_array == $temp_item ) {
                                        $all_items['quantities'][$key] = $all_items['quantities'][$key] + $product_qty;
                                    } else {
                                        if ( !in_array($temp_item, $all_items['products']) ) {
                                            $all_items['products'][] = $temp_item;
                                            $all_items['quantities'][] = $product_qty;
                                        }
                                    }
                                }
                            } else {
                                $all_items['products'][] = $temp_item;
                                $all_items['quantities'][] = $product_qty;
                            }

                        } //endforeach
                        ?>
                    </div>

                    <div class="order-totals" style="margin-top:6pt;text-align:right">
                        <?php printf( __('%sOrder total:%s %s', 'woo-export-order-bcf'), '<b>', '</b>', $order->get_formatted_order_total() ); ?>
                    </div>

                </div>
                <?php

            endforeach;

            $file_html = ob_get_clean();
            $mpdf->WriteHTML($file_html);

            if ( $all_items ) : 

                ob_start();
            ?>
            <div class="order-items" style="line-height:1.6">
                <?php
                foreach( $all_items['products'] as $key => $item ) {

                    ?>
                    <div class="order-item-single" style="margin-bottom:8pt">
                        <span><?php echo $item['name']; ?>&nbsp;-&nbsp;</span>
                        <span><b>x<?php echo $all_items['quantities'][$key] ?></b></span>
                        <?php
                        if ( isset( $item['variations'] ) ) {
                            foreach( $item['variations'] as $variation ) { ?>
                            <div class="order-item-single__variation" style="padding-left:12pt;font-size:10pt;line-height:1.3;">
                                <span><b><?php echo $variation['formatted_name']; ?></b>:&nbsp;<?php echo $variation['formatted_value']; ?></span>
                            </div>
                            <?php
                            }
                        }
                        ?>
                    </div>
                <?php
                }
                ?>
            </div>
            <?php

                $items_html = ob_get_clean();

                // pagebreak
                $mpdf->AddPage();

                $mpdf->WriteHTML('<h2>'. __('Products list for the day:', 'woo-export-order-bcf') .'</h2>');

                $mpdf->WriteHTML($items_html);

            endif;



            // assign PDF content to variable
            $content = $mpdf->Output('', 'S');

            $file = array(
                'base'    => $this->upload_dir,
                'file'    => '/' . $file_name,
                'content' => $content,
            );

            if ( $file_result = self::create_exports_dir( $file ) ) {

                $file_url = $this->upload_url . '/' . $file_name;
                $file_path = $this->upload_dir . '/' . $file_name;

                $dataArray['download_url'] = $file_url;
                $dataArray['file_name'] = $file_name;

                // Email the report
                if ( isset( $posted_data['woebcf_email_address'] ) && $posted_data['woebcf_email_address'] ) {
                    self::send_file_via_email( $file_path , 'Order export ', $posted_data['woebcf_email_address']  );
                }

                // Remove created file
                // $this->remove_file( $file_path  );
            }
            
            $this->ajax_submit( 'success', __('Success.', 'woo-export-order-bcf'), $dataArray );
        } else {
            $this->ajax_submit( 'error', __('No orders found. Try different parameters.', 'woo-export-order-bcf'), $dataArray );
        }

        die();
    }

    /**
     * Get Orders IDs
     *
     * @param string $date
     * @param array $statuses
     * @return void
     */
    private function get_orders( $date, $statuses ) {

        $orders = get_posts(array(
            'fields'    => 'ids',
            "post_type" => "shop_order",
            "post_status" => $statuses,
            "posts_per_page" => -1,
            'meta_query' => array(
                'relation' => 'AND',
                'date_clause' => array(
                    'key' => '_billing_date',
                    'value' => $date,
                    'compare' => '=',
                    'type' => 'DATE',
                ),
                'time_clause' => array(
                    'key' => '_billing_time',
                    'compare' => 'EXISTS',
                    'type' => 'NUMERIC'
                ),
            ),
            'orderby' => array(
                'date_clause' => 'DESC',
                'time_clause' => 'ASC',
            ),
        ));

        return $orders;
    }

    /**
     * Create exports/temporary directory and files
     *
     * @param array $file
     * @return bool
     */
    private function create_exports_dir( $file = [] ) {
        $created = true;

        // Install files and folders for uploading files and prevent hotlinking.
        $files = array(
            array(
                'base'    => $this->upload_dir,
                'file'    => '/index.html',
                'content' => '',
            ),
            /*array(
                'base'    => $this->upload_dir,
                'file'    => '/.htaccess',
                'content' => 'deny from all',
            ),*/
            $file
        );

        foreach ( $files as $file ) {

            if ( $file && wp_mkdir_p( $file['base'] ) ) { // ! file_exists( trailingslashit( $file['base'] ) . $file['file'] )
                $created = false;

                $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' );
                if ( $file_handle ) {
                    fwrite( $file_handle, $file['content'] );
                    fclose( $file_handle );
                    $created = true;
                }
            }
        }
        return $created;
    }

    /**
     * Return Uploads directory path or url
     *
     * @param string $base
     * @return string
     */
    public function upload_folder( $base ) {
        $upload = wp_upload_dir();

        if ( isset( $upload[$base] ) ) {
            $upload_dir = $upload[$base];
            $upload_dir = $upload_dir . '/woo_export_orders_bcf_uploads';

            return $upload_dir;
        }

        return $upload_dir['basedir'];
    }

    /**
     * Send file via email
     *
     * @param string $filepath
     * @param string $subject
     * @param string/array $recipients
     * @return void
     */
    public static function send_file_via_email( $filepath, $subject = 'Export for ', $recipients ) {

        if ( !$filepath )
            return;

        $admin_address = get_option( "woebcf_admin_email_address" );

        if ( $recipients && is_email( $admin_address ) ) {

            $headers = 'from: '. $admin_address . "\r\n" . 'reply-to: ' . $admin_address . "\r\n";

            $email_subject = $subject . date("Y-m-d");
            $email_body = __('Bellow please find an attached order export file.', 'woo-export-order-bcf');

            $sent = wp_mail( trim($recipients), $email_subject, $email_body, $headers, [ $filepath ] );

            if ( !$sent ) {
                error_log('Export failed to send email.' . print_r($sent, 1));
            }
        }

        return true;
    }

    /**
     * Remove file from directory
     *
     * @param string $file_path
     * @return void
     */
    public static function remove_file($file_path = '') {
        if (!$file_path)
            return;
        unlink($file_path);
    }

}

/**
 * Loading plugin
 * @return void 
 */
function woo_export_orders_bcf_load() {
    $plugin = new Woo_Export_Orders_Bcf();
}
add_action( 'plugins_loaded', 'WooExportOrdersBcf\woo_export_orders_bcf_load' );
