<?php
/**
 * PHP version 7
 * Plugin Name: Payments via Paymaya for WooCommerce
 * Description: Take credit and debit card payments via Paymaya.
 * Author: PayMaya
 * Author URI: https://www.paymaya.com
 * Version: 1.0.0
 * Requires at least: 5.3.2
 * Tested up to: 5.5.1
 * WC requires at least: 3.9.3
 * WC tested up to: 4.5.2
 *
 * @category Plugin
 * @package  CynderTech
 * @author   CynderTech <hello@cynder.io>
 * @license  GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @link     n/a
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce fallback notice.
 *
 * @return string
 */
function Paymaya_Woocommerce_Missing_Cynder_notice()
{
    /* translators: 1. URL link. */
    echo '<div class="error"><p><strong>' . sprintf(
        esc_html__(
            'Paymaya requires WooCommerce to be '
            . 'installed and active. You can download %s here.',
            'woocommerce-gateway-paymaya'
        ),
        '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
    ) . '</strong></p></div>';
}

/**
 * Initialize Paymaya Gateway Class
 *
 * @return string
 */
function Paymaya_Init_Gateway_class()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'Paymaya_Woocommerce_Missing_Cynder_notice');
        return;
    }

    define('CYNDER_PAYMAYA_MAIN_FILE', __FILE__);
    define('CYNDER_PAYMAYA_VERSION', '1.0.0');
    define('CYNDER_PAYMAYA_BASE_SANDBOX_URL',  'https://pg-sandbox.paymaya.com');
    define('CYNDER_PAYMAYA_BASE_PRODUCTION_URL',  'https://pg.paymaya.com');
    define(
        'CYNDER_PAYMAYA_PLUGIN_URL',
        untrailingslashit(
            plugins_url(
                basename(plugin_dir_path(__FILE__)),
                basename(__FILE__)
            )
        )
    );
    

    if (!class_exists('Cynder_Paymaya')) :
        /**
         * Paymaya Class
         * 
         * @category Class
         * @package  Paymaya
         * @author   Cyndertech <devops@cynder.io>
         * @license  n/a (http://127.0.0.0)
         * @link     n/a
         * @phpcs:disable Standard.Cat.SniffName
         */
        class Cynder_Paymaya
        {
            /**
             * *Singleton* instance of this class
             * 
             * @var Singleton The reference the *Singleton* instance of this class
             */
            private static $_instance;

            /**
             * Returns the *Singleton* instance of this class.
             *
             * @return Singleton The *Singleton* instance.
             */
            public static function getInstance()
            {
                if (null === self::$_instance) {
                    self::$_instance = new self();
                }

                return self::$_instance;
            }

            /**
             * Private clone method to prevent cloning of the instance of the
             * *Singleton* instance.
             *
             * @return void
             */
            private function __clone()
            {
                // empty
            }

            /**
             * Private unserialize method to prevent unserializing of the *Singleton*
             * instance.
             *
             * @return void
             */
            private function __wakeup()
            {
                // empty
            }

            /**
             * Protected constructor to prevent creating a new instance of the
             * *Singleton* via the `new` operator from outside of this class.
             */
            private function __construct()
            {
                add_action('admin_init', array($this, 'install'));
                $this->init();
            }

            /**
             * Initialize Paymaya plugin
             * 
             * @return void
             * 
             * @since 1.0.0
             */
            public function init()
            {
                $fileDir = dirname(__FILE__);
                include_once $fileDir.'/classes/cynder-paymaya.php';
                include_once 'paymaya-top-level-hooks.php';

                add_filter(
                    'woocommerce_payment_gateways',
                    array($this, 'addGateways')
                );

                if (version_compare(WC_VERSION, '3.4', '<')) {
                    add_filter(
                        'woocommerce_get_sections_checkout',
                        array($this, 'filterGatewayOrderAdmin')
                    );
                }
            }

            /**
             * Registers Payment Gateways
             * 
             * @param $methods array of methods
             * 
             * @return array
             * 
             * @since 1.0.0
             */
            public function addGateways($methods)
            {
                $methods[] = 'Cynder_Paymaya_Gateway';
                
                return $methods;
            }

            /**
             * Registers Payment Gateways
             * 
             * @param array $sections array of sections
             * 
             * @return array
             * 
             * @since 1.0.0
             */
            public function filterGatewayOrderAdmin($sections) 
            {
                unset($sections['paymaya']);

                $gatewayName = 'woocommerce-gateway-paymaya';
                $sections['paymaya'] = __(
                    'Payments via Paymaya',
                    $gatewayName
                );

                $sections = [];

                return $sections;
            }

            /**
             * Install/Update function
             * 
             * @return void
             * 
             * @since 1.0.0
             */
            public function install()
            {
                if (!is_plugin_active(plugin_basename(__FILE__))) {
                    return;
                }

                if (!defined('IFRAME_REQUEST')
                    && (CYNDER_PAYMAYA_VERSION !== get_option(
                        'cynder_paymaya_version'
                    ))
                ) {
                    do_action('woocommerce_paymaya_updated');

                    if (!defined('CYNDER_PAYMAYA_INSTALLING')) {
                        define('CYNDER_PAYMAYA_INSTALLING', true);
                    }

                    $this->updatePluginVersion();
                }
            }

            /**
             * Updates Plugin Version
             * 
             * @return void
             * 
             * @since 1.0.0
             */
            public function updatePluginVersion()
            {
                delete_option('cynder_paymaya_version');
                update_option('cynder_paymaya_version', CYNDER_PAYMAYA_VERSION);
            }

        }
    
        Cynder_Paymaya::getInstance();
    endif;
}

add_action('plugins_loaded', 'paymaya_init_gateway_class');
