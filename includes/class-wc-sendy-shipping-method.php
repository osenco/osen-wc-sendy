<?php
if (!class_exists("WC_Sendy_Shipping_Method")) {
    class WC_Sendy_Shipping_Method extends WC_Shipping_Method
    {
        /**
         * The ID of the shipping method.
         *
         * @var string
         */
        public $id = 'sendyit';
    
        /**
         * The title of the method.
         *
         * @var string
         */
        public $method_title = 'Sendy';
    
        /**
         * The description of the method.
         *
         * @var string
         */
        public $method_description = 'Deliver with Sendy';
    
        /**
         * The supported features.
         *
         * @var array
         */
        public $supports = [
            'settings',
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
        ];
        
        public function __construct()
        {
            $this->id = "sendyit";
            $this->method_title = __("Sendy Delivery", "sendyit");
            $this->method_description = __("The Sendy Woocommerce Plugin for Sendy Public API.", "sendyit");

            $this->enabled = isset($this->settings["enabled"]) ? $this->settings["enabled"] : "yes";
            $this->title = isset($this->settings["title"]) ? $this->settings["title"] : __("Sendy Delivery", "sendyit");

            $this->init_form_fields();
            $this->init_settings();

            // Save settings in admin
            add_action("woocommerce_update_options_shipping_{$this->id}", [$this, 'process_admin_options']);
        }
        
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable Sendy'),
                    'type' => 'checkbox',
                    'description' => __('Enable this shipping method.'),
                    'default' => 'yes',
                ),
                'live' => array(
                    'title' => __('Live Environment'),
                    'type' => 'checkbox',
                    'description' => __('Leave unchecked if in Sandbox.'),
                    'default' => 'yes',
                ),
                'title' => array(
                    'title' => __('Method Title'),
                    'type' => 'text',
                    'description' => __('Title to be display on site.'),
                    'default' => __('Deliver with Sendy'),
                ),
                "sendy_api_key" => array(
                    "title" => __("Sendy API Key", "sendyit"),
                    "type" => "text",
                    "id" => "key",
                    "default" => __("mysendykey", "sendyit")
                ),

                "sendy_api_username" => array(
                    "title" => __("Sendy Api Username", "sendyit"),
                    "type" => "text",
                    "default" => __("mysendyusername", "sendyit")
                ),

                "shop_location" => array(
                    "title" => __("Shop Location", "sendyit"),
                    "type" => "text",
                    "placeholder" => "Enter a location",
                    "description" => __("Please Pick From Google Map Suggestions.", "sendyit")
                ),

                "building" => array(
                    "title" => __("Building", "sendyit"),
                    "type" => "text"
                ),

                "floor" => array(
                    "title" => __("Floor", "sendyit"),
                    "type" => "text"

                ),

                "phone" => array(
                    "title" => __("Contact Phone Number"),
                    "type" => "text"
                ),

                "open_hours" => array(
                    "title" => __("Shop Opening Hours", "sendyit"),
                    "type" => "select",
                    "options" => array(
                        "blank" => __("Select opening hour", "sendyit"),
                        "6" => __("6:00 AM", "sendyit"),
                        "7" => __("7:00 AM", "sendyit"),
                        "8" => __("8:00 AM", "sendyit"),
                        "9" => __("9:00 AM", "sendyit"),
                        "10" => __("10:00 AM", "sendyit"),
                        "11" => __("11:00 AM", "sendyit"),
                        "12" => __("12:00 PM", "sendyit"),
                        "13" => __("1:00 PM", "sendyit"),
                        "14" => __("2:00 PM", "sendyit"),
                        "15" => __("3:00 PM", "sendyit"),
                        "16" => __("4:00 PM", "sendyit"),
                        "17" => __("5:00 PM", "sendyit"),
                        "18" => __("6:00 PM", "sendyit"),
                        "19" => __("7:00 PM", "sendyit"),
                        "20" => __("8:00 PM", "sendyit")
                    )
                ),

                "close_hours" => array(
                    "title" => __("Shop Closing Hours", "sendyit"),
                    "type" => "select",
                    "options" => array(
                        "blank" => __("Select closing hour", "sendyit"),
                        "6" => __("6:00 AM", "sendyit"),
                        "7" => __("7:00 AM", "sendyit"),
                        "8" => __("8:00 AM", "sendyit"),
                        "9" => __("9:00 AM", "sendyit"),
                        "10" => __("10:00 AM", "sendyit"),
                        "11" => __("11:00 AM", "sendyit"),
                        "12" => __("12:00 PM", "sendyit"),
                        "13" => __("1:00 PM", "sendyit"),
                        "14" => __("2:00 PM", "sendyit"),
                        "15" => __("3:00 PM", "sendyit"),
                        "16" => __("4:00 PM", "sendyit"),
                        "17" => __("5:00 PM", "sendyit"),
                        "18" => __("6:00 PM", "sendyit"),
                        "19" => __("7:00 PM", "sendyit"),
                        "20" => __("8:00 PM", "sendyit")
                    )
                ),

                "other_details" => array(
                    "title" => __("Other Details", "sendyit"),
                    "type" => "textarea"
                ),

                "from_lat" => array(
                    "title" => __("From Latitude", "sendyit"),
                    "type" => "text"
                ),

                "from_long" => array(
                    "title" => __("From Longitude", "sendyit"),
                    "type" => "text"
                ),

            );
        }

        public function calculate_shipping($package = array())
        {
            $weight = 0;
            $cost = 0;
            $country = $package["destination"]["country"];

            //wp_send_json($package);

            foreach ($package["contents"] as $item_id => $values) {
                $_product = $values["data"];

                if ($_product->has_weight()) {
                    $weight += (float) $_product->get_weight() * $values['quantity'];
                }
            }

            $weight = wc_get_weight($weight, "kg");

            if ($weight <= 10) {
                $cost = 0;
            } elseif ($weight <= 30) {
                $cost = 5;
            } elseif ($weight <= 50) {
                $cost = 10;
            } else {
                $cost = 20;
            }

            $countryZones = array(
                "KE" => 0,
                "UG" => 3,
                "TZ" => 2
            );

            $zonePrices = array(
                0 => 20,
                1 => 30,
                2 => 50,
                3 => 70
            );

            $zoneFromCountry = $countryZones[$country];
            $priceFromZone = $zonePrices[$zoneFromCountry];

            $cost += $priceFromZone;

            $rate = array(
                "id" => $this->id,
                "label" => "Sendy",
                "cost" => $cost
            );

            $this->add_rate($rate);
        }

        /**
         * Evaluate a cost from a sum/string.
         *
         * @param  string $sum Sum of shipping.
         * @param  array  $args Args, must contain `cost` and `qty` keys. Having `array()` as default is for back compat reasons.
         * @return string
         */
        protected function evaluate_cost($sum, $args = array())
        {
            // Add warning for subclasses.
            if (!is_array($args) || !array_key_exists('qty', $args) || !array_key_exists('cost', $args)) {
                wc_doing_it_wrong(__FUNCTION__, '$args must contain `cost` and `qty` keys.', '4.0.1');
            }

            include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';

            // Allow 3rd parties to process shipping cost arguments.
            $args           = apply_filters('woocommerce_evaluate_shipping_cost_args', $args, $sum, $this);
            $locale         = localeconv();
            $decimals       = array(wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',');
            $this->fee_cost = $args['cost'];

            // Expand shortcodes.
            add_shortcode('fee', array($this, 'fee'));

            $sum = do_shortcode(
                str_replace(
                    array(
                        '[qty]',
                        '[cost]',
                    ),
                    array(
                        $args['qty'],
                        $args['cost'],
                    ),
                    $sum
                )
            );

            remove_shortcode('fee', array($this, 'fee'));

            // Remove whitespace from string.
            $sum = preg_replace('/\s+/', '', $sum);

            // Remove locale from string.
            $sum = str_replace($decimals, '.', $sum);

            // Trim invalid start/end characters.
            $sum = rtrim(ltrim($sum, "\t\n\r\0\x0B+*/"), "\t\n\r\0\x0B+-*/");

            // Do the math.
            return $sum ? WC_Eval_Math::evaluate($sum) : 0;
        }

        /**
         * Work out fee (shortcode).
         *
         * @param  array $atts Attributes.
         * @return string
         */
        public function fee($atts)
        {
            $atts = shortcode_atts(
                array(
                    'percent' => '',
                    'min_fee' => '',
                    'max_fee' => '',
                ),
                $atts,
                'fee'
            );

            $calculated_fee = 0;

            if ($atts['percent']) {
                $calculated_fee = $this->fee_cost * (floatval($atts['percent']) / 100);
            }

            if ($atts['min_fee'] && $calculated_fee < $atts['min_fee']) {
                $calculated_fee = $atts['min_fee'];
            }

            if ($atts['max_fee'] && $calculated_fee > $atts['max_fee']) {
                $calculated_fee = $atts['max_fee'];
            }

            return $calculated_fee;
        }

        /**
         * Calculate the shipping costs.
         *
         * @param array $package Package of items from cart.
         */
        public function calculated_shipping($package = array())
        {
            $rate = array(
                'id'      => $this->get_rate_id(),
                'label'   => $this->title,
                'cost'    => 0,
                'package' => $package,
            );

            // Calculate the costs.
            $has_costs = false; // True when a cost is set. False if all costs are blank strings.
            $cost      = $this->get_option('cost');

            if ('' !== $cost) {
                $has_costs    = true;
                $rate['cost'] = $this->evaluate_cost(
                    $cost,
                    array(
                        'qty'  => $this->get_package_item_qty($package),
                        'cost' => $package['contents_cost'],
                    )
                );
            }

            // Add shipping class costs.
            $shipping_classes = WC()->shipping()->get_shipping_classes();

            if (!empty($shipping_classes)) {
                $found_shipping_classes = $this->find_shipping_classes($package);
                $highest_class_cost     = 0;

                foreach ($found_shipping_classes as $shipping_class => $products) {
                    // Also handles BW compatibility when slugs were used instead of ids.
                    $shipping_class_term = get_term_by('slug', $shipping_class, 'product_shipping_class');
                    $class_cost_string   = $shipping_class_term && $shipping_class_term->term_id ? $this->get_option('class_cost_' . $shipping_class_term->term_id, $this->get_option('class_cost_' . $shipping_class, '')) : $this->get_option('no_class_cost', '');

                    if ('' === $class_cost_string) {
                        continue;
                    }

                    $has_costs  = true;
                    $class_cost = $this->evaluate_cost(
                        $class_cost_string,
                        array(
                            'qty'  => array_sum(wp_list_pluck($products, 'quantity')),
                            'cost' => array_sum(wp_list_pluck($products, 'line_total')),
                        )
                    );

                    if ('class' === $this->type) {
                        $rate['cost'] += $class_cost;
                    } else {
                        $highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
                    }
                }

                if ('order' === $this->type && $highest_class_cost) {
                    $rate['cost'] += $highest_class_cost;
                }
            }

            if ($has_costs) {
                $this->add_rate($rate);
            }

            /**
             * Developers can add additional flat rates based on this one via this action since @version 2.4.
             *
             * Previously there were (overly complex) options to add additional rates however this was not user.
             * friendly and goes against what Flat Rate Shipping was originally intended for.
             */
            do_action('woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate);
        }

        /**
         * Get items in package.
         *
         * @param  array $package Package of items from cart.
         * @return int
         */
        public function get_package_item_qty($package)
        {
            $total_quantity = 0;
            foreach ($package['contents'] as $item_id => $values) {
                if ($values['quantity'] > 0 && $values['data']->needs_shipping()) {
                    $total_quantity += $values['quantity'];
                }
            }
            
            return $total_quantity;
        }

        /**
         * Finds and returns shipping classes and the products with said class.
         *
         * @param mixed $package Package of items from cart.
         * @return array
         */
        public function find_shipping_classes($package)
        {
            $found_shipping_classes = array();

            foreach ($package['contents'] as $item_id => $values) {
                if ($values['data']->needs_shipping()) {
                    $found_class = $values['data']->get_shipping_class();

                    if (!isset($found_shipping_classes[$found_class])) {
                        $found_shipping_classes[$found_class] = array();
                    }

                    $found_shipping_classes[$found_class][$item_id] = $values;
                }
            }

            return $found_shipping_classes;
        }

        /**
         * Sanitize the cost field.
         *
         * @since 3.4.0
         * @param string $value Unsanitized value.
         * @throws Exception Last error triggered.
         * @return string
         */
        public function sanitize_cost($value)
        {
            $value = is_null($value) ? '' : $value;
            $value = wp_kses_post(trim(wp_unslash($value)));
            $value = str_replace(array(get_woocommerce_currency_symbol(), html_entity_decode(get_woocommerce_currency_symbol())), '', $value);
            // Thrown an error on the front end if the evaluate_cost will fail.
            $dummy_cost = $this->evaluate_cost(
                $value,
                array(
                    'cost' => 1,
                    'qty'  => 1,
                )
            );
            if (false === $dummy_cost) {
                throw new Exception(WC_Eval_Math::$last_error);
            }
            return $value;
        }
    }
}