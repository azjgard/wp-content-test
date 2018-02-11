<?php

class WC_Payment_Gateway_Dummy extends WC_Payment_Gateway
{

    private $user_role = '';

    public function __construct()
    {
        $this->id = 'wc_dummy_gateway';
        $this->has_fields = false;
        $this->method_title = __('Dummy Gateway', 'woocommerce');
        $this->method_description = __('Dummy Gateway allows you to create orders without charging you real money. This is usefully when you want to test your order process.', 'woocommerce');
        $this->title = 'Dummy Gateway';
        $this->description = __('This payment gateway will not charge you real money.', 'woocommerce');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable dummy payment gateway', 'woocommerce'),
                'default' => 'no'
            ),
            'user_role' => array(
                'title' => __('User role', 'woocommerce'),
                'type' => 'select',
                'description' => __('A role of user for which dummy gateway should be accessible.', 'woocommerce'),
                'desc_tip' => true,
                'options' => array(
                    'administrator' => __('Administrator', 'woocommerce'),
                    'everyone' => __('Everyone', 'woocommerce')
                ),
                'default' => 'administrator'
            )
        );
    }

    public function init_settings()
    {
        parent::init_settings();
        $this->user_role = !empty($this->settings['user_role']) && 'everyone' === $this->settings['user_role'] ? 'everyone' : 'administrator';
    }

    public function is_available()
    {
        $is_available = parent::is_available();
        if ($is_available && $this->user_role === 'administrator') {
            $is_available = current_user_can('administrator');
        }
        return $is_available;
    }

    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);
        $order->payment_complete();

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

}