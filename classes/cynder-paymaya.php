<?php
/**
 * PHP version 7
 * 
 * Paymaya Payment Plugin
 * 
 * @category Plugin
 * @package  Paymaya
 * @author   Cyndertech <devops@cynder.io>
 * @license  n/a (http://127.0.0.0)
 * @link     n/a
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Paymaya Class
 * 
 * @category Class
 * @package  Paymaya
 * @author   Cyndertech <devops@cynder.io>
 * @license  n/a (http://127.0.0.0)
 * @link     n/a
 */
class Cynder_Paymaya_Gateway extends WC_Payment_Gateway
{
    /**
     * Singleton instance
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
        if (null === self::$_instance ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Starting point of the payment gateway
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->id = 'paymaya';
        $this->has_fields = true;
        $this->method_title = 'Payments via Paymaya';
        $this->method_description = 'Secure online payments via Paymaya';

        $this->supports = array(
            'products'
        );

        $this->initFormFields();

        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->secret_key = $this->testmode ?
            $this->get_option('test_secret_key')
            : $this->get_option('secret_key');
        $this->public_key = $this->testmode ?
            $this->get_option('test_public_key')
            : $this->get_option('public_key');

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );
    }

    /**
     * Payment Gateway Settings Page Fields
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    public function initFormFields()
    {
        $webhookUrl = add_query_arg(
            'wc-api',
            'cynder_paymaya',
            trailingslashit(get_home_url())
        );

        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Paymaya Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'type'        => 'text',
                'title'       => 'Title',
                'description' => 'This controls the title that ' .
                                 'the user sees during checkout.',
                'default'     => 'Payments via Paymaya',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description that ' .
                                 'the user sees during checkout.',
                'default'     => 'Secure online payments via Paymaya',
            ),
            'live_env' => array(
                'title' => 'API Keys',
                'type' => 'title',
                'description' => 'Some useful description of API keys here'
            ),
            'public_key' => array(
                'title'       => 'Public Key',
                'type'        => 'text'
            ),
            'secret_key' => array(
                'title'       => 'Secret Key',
                'type'        => 'text'
            ),
        );
    }
}
