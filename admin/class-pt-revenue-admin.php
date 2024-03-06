<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.presstigers.com
 * @since      1.0.0
 *
 * @package    Pt_Revenue
 * @subpackage Pt_Revenue/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Pt_Revenue
 * @subpackage Pt_Revenue/admin
 * @author     PressTigers <majid@presstigers.dev>
 */
class Pt_Revenue_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		wp_enqueue_style($this->plugin_name . "select2", 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), $this->version, 'all');
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/pt-revenue-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script($this->plugin_name . "select2", 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), $this->version, true);
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/pt-revenue-admin.js', array('jquery', $this->plugin_name . "select2"), $this->version, true);
		wp_enqueue_script('pt_revenue_script', plugin_dir_url(__FILE__) . 'js/pt_revenue_script.js', array('jquery'), $this->version, true);
		wp_localize_script('pt_revenue_script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
	}

	/**
	 * Functio To add a new page under woocommerce
	 */
	public function add_custom_orders_page()
	{
		add_submenu_page(
			'woocommerce',
			'Custom Orders Page',
			'Custom Orders',
			'manage_options',
			'custom-orders',
			array($this, 'custom_orders_page_callback')
		);
	}

	public function custom_orders_page_callback()
	{
		include(plugin_dir_path(__FILE__) . 'partials/pt-revenue-admin-display.php');
	}

	// AJAX handler to fetch states based on selected country
	public function get_states_by_country_callback()
	{
		// Ensure the request is coming from a valid source
		// check_ajax_referer('get_states_nonce', 'security');

		// Get the selected country code from the AJAX request
		$country_code = isset($_REQUEST['country_code']) ? $_REQUEST['country_code'] : '';

		if( $country_code ) {
			$country_code = $country_code[0];
		}

		
		// Initialize an array to store states data
		$states = WC()->countries->get_states($country_code);
		// wp_send_json_success($states);

		// Fetch states based on the selected country code
		// if (!empty($country_code)) {
		// 	if (is_array($country_code)) {
		// 		foreach ($country_code as $country) {
		// 			$states[$country] = WC()->countries->get_states($country);
		// 		}
		// 	} else {
		// 		$states[$country_code] = WC()->countries->get_states($country_code);
		// 	}

		// 	// Replace this with your method to retrieve states based on the country code
		// 	// Example: $states = get_states_by_country_code($country_code);
		// 	// You may use the WC()->countries->get_states() method if you're using WooCommerce
		// }

		$output = array(); 
		$options = "";

		if (isset($states) && !empty($states)) {
			// var_dump($states);
			// die('die ajax');
			$options .= '<option value="">Select State</option>';
			foreach ($states as $key => $value) {
				$options .= '<option value="' . $key . '">' . $value . '</option>';

				// $country = WC()->countries->countries[$key];

				// $options .= '<optgroup label="' . $country . '">';
				// if (is_array($value) && count($value) !== 0) {
					// foreach ($value as $k => $v) {
					// 	// $val = explode(":", $v);
					// 	$options .= '<option value="' . $k . '">' . $v . '</option>';
					// }
				// }
				// $options .= '</optgroup>';
			}
			$output['status'] = 'success';
			$output['data'] = $options;
			// wp_send_json_success($output);
			
		} else {
			$options .= 'No State exist for '. WC()->countries->countries[$country_code];
			$output['status'] = 'error';
			$output['data'] = $options;		
			// wp_send_json_error($output);
		}
		
		wp_send_json_success($output);
		// Send the states data as JSON response

		// return $country_code;
	}

	// AJAX handler to fetch states based on selected country
	public function create_and_download_csv_callback()
	{
		$from_date = $_REQUEST['from_date'];
		$to_date = $_REQUEST['to_date'];
		$country = $_REQUEST['country']; 
		$state = $_REQUEST['state'];

	   	// Define arguments
		$args = array(
			'post_type'      => 'shop_order', // Assuming orders are stored as a custom post type 'shop_order'
			'posts_per_page' => -1, // Retrieve all orders
			'post_status'    => 'wc-completed', // Assuming completed orders only
			'meta_query'     => array(
				'relation'    => 'AND', // Combine meta queries with AND logic
				array(
					'relation' => 'AND', // Combine date queries with AND logic
					array(
						'key'     => '_completed_date', // Meta key for completion date
						'value'   => date('Y-m-d H:i:s', strtotime($from_date)), // Convert from_date to MySQL datetime format
						'compare' => '>=', // Orders completed on or after from_date
						'type'    => 'DATETIME',
					),
					array(
						'key'     => '_completed_date', // Meta key for completion date
						'value'   => date('Y-m-d H:i:s', strtotime($to_date)), // Convert to_date to MySQL datetime format
						'compare' => '<=', // Orders completed on or before to_date
						'type'    => 'DATETIME',
					),
				),
			),
		);

		// Add country condition if it's set
		if (isset($country)) {
			$args['meta_query'][] = array(
				'key'     => '_billing_country', // Meta key for billing country
				'value'   => $country, // Value from $_REQUEST['country']
				'compare' => 'IN', // Exact match
			);
		}

		// Add state condition if it's set
		if (isset($state)) {
			$args['meta_query'][] = array(
				'key'     => '_billing_state', // Meta key for billing state
				'value'   => $state, // Value from $_REQUEST['state']
				'compare' => 'IN', // Exact match
			);
		}

		// Instantiate new WP_Query
		$query = new WP_Query($args);

		// Array to store the consolidated orders for each customer
		$consolidated_orders = array();

		// Consolidate orders by customer
		foreach ($query->posts as $post) {
			$order = wc_get_order($post->ID);
			$customer_id = $order->get_customer_id();
			if (!isset($consolidated_orders[$customer_id])) {
				$consolidated_orders[$customer_id] = array(
					'customer_name' => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
					'customer_email' => $order->get_billing_email(),
					'customer_country' => WC()->countries->countries[$order->get_billing_country()],
					'customer_state' => WC()->countries->states[$order->billing_country][$order->billing_state],
					'orders_count' => 0,
					'total_amount' => 0,
					'order_status' => array()
				);
			}
			$consolidated_orders[$customer_id]['orders_count']++;
			$consolidated_orders[$customer_id]['total_amount'] += $order->get_total();
			$consolidated_orders[$customer_id]['order_date'] = date("Y-m-d", strtotime($order->get_date_paid()));
			$consolidated_orders[$customer_id]['order_id'] = $order->get_id();
			$consolidated_orders[$customer_id]['order_status'][] = $order->get_status();
		}

	   if ($query) {

			$csvfilePath = __DIR__ . '/csvfile/';
			$csv_filename = "PressTigers Order Report For $from_date to $to_date.csv";

			$csv_file = $csvfilePath . $csv_filename;

			$csvfile_url = plugins_url() . '/pt-revenue/admin/csvfile/' . $csv_filename;

			// Step 2: Write fetched data into a CSV file
			// $csv_file = fopen($csv_filename, "w");

			// Save CSV file
			$file = fopen($csv_file, 'w');
			
			

			// Set headers for CSV file download
			// header('Content-Type: application/csv');
			// header('Content-Disposition: attachment; filename="exported_data.csv"');
			
			// $csv_data = ""; // Your CSV data as a string
			// ob_start();
			// Write CSV headers
			// fputcsv($csv_file, array('ID', 'Name', 'Orders', 'Email', 'Country', 'State', 'Amount', 'Date'));

			fwrite($file, "ID,Name,Orders,Email,Country,State,Amount,Date\n");

			foreach ($consolidated_orders as $customer_id => $c_data) {
				fwrite($file, $c_data['order_id'].','.$c_data['customer_name'].','.$c_data['orders_count'].','.$c_data['customer_email'].','.$c_data['customer_country'].','.$c_data['customer_state'].',$  '.$c_data['total_amount'].','.$c_data['order_date']. "\n" );

			}

			fwrite($file, "\n\n\n"); // done with the order table.

			if (isset($country) && !empty($country) && isset($state) && !empty($state)) {
					
				$total_customer_in_state = array();
				$total_orders_in_state = 0;
				$total_spend_in_state = 0;
				$total_customer_in_rest_of_state = array();
				$total_orders_in_rest_of_state = 0;
				$total_spend_in_rest_of_state = 0;

				$summary_args_usa = array(
					'post_type' => 'shop_order',
					'post_status' => 'wc-completed',
					'date_query' => array(
						'after' => isset($from_date) ? date('Y-m-d', strtotime($from_date)) : '', // Filter by From Date
						'before' => isset($to_date) ? date('Y-m-d', strtotime($to_date)) : '', // Filter by To Date
						'inclusive' => true,
					),
					'posts_per_page' => -1, // Adjust the number of orders per page as needed
					'fields' => 'ids', // Only fetch post IDs to improve performance
				);

				if (isset($country) && !empty($country)) {
						$summary_args_usa['meta_query'] = array('relation' => 'OR');
						foreach ($country as $selected_country) {
							$summary_args_usa['meta_query'][] = array(
								'key' => '_billing_country',
								'value' => wc_clean($selected_country),
								// 'compare' => '=',
							);
						}
				}

				// Get all orders based on summary arguments
				$summary_query_usa = new WP_Query($summary_args_usa);

				$selected_country = $country;
				$selected_states = $state;
				$selected_states_summary = array();

				foreach ($selected_country as $country) {
						$curent_states = WC()->countries->get_states($country);
						// print_r($curent_states);
						// Extract state codes
						$state_codes = array_keys($curent_states);
						foreach ($selected_states as $state) {
							if (in_array($state, $state_codes)) {
								$selected_states_summary[$country][$state] = array(
									'customers' => array(),
									'total_order' => 0,
									'total_spend' => 0,
								);
							}
						}
				}


				$other_states_summary = array(
						'customers' => array(),
						'total_order' => 0,
						'total_spend' => 0,
				);


				foreach ($summary_query_usa->posts as $order_id) {

						$order = wc_get_order($order_id);
						$customer_id = $order->get_customer_id();
						$billing_country = $order->get_billing_country();
						$billing_state = $order->get_billing_state();
						$order_total = get_post_meta($order_id, '_order_total', true);;

						if (in_array($billing_country, $selected_country) && in_array($billing_state, $selected_states)) {
							array_push($selected_states_summary[$billing_country][$billing_state]['customers'], $customer_id);
							$selected_states_summary[$billing_country][$billing_state]['total_order']++;
							$selected_states_summary[$billing_country][$billing_state]['total_spend'] += $order_total;
						} else {
							array_push($other_states_summary['customers'], $customer_id);
							$other_states_summary['total_order']++;
							$other_states_summary['total_spend'] += $order_total;
						}
				}

				fwrite($file, "Summary\n");
				fwrite($file, ",Total Customers,Total Orders,Total Spend\n");

                 

				$grand_total = 0.0;

				foreach ($selected_states_summary as $country => $state) {

					foreach ($state as $k => $v) {
						fwrite($file, WC()->countries->states[$country][$k].','.count(array_unique($v['customers'])).','.$v['total_order'].',$'.$v['total_spend']. "\n" );
						$grand_total += $v['total_spend'];
					}
				}

				fwrite($file, 'Other States of ,'.count(array_unique($other_states_summary['customers'])).','.$other_states_summary['total_order'].',$'.$other_states_summary['total_spend']. "\n" );
				$grand_total += $other_states_summary['total_spend'];

				fwrite($file, 'Total US Orders, , ,$'.$grand_total. "\n" ); // 

			} // Done with states summary

			$summary_args = array(
				'post_type' => 'shop_order',
				'post_status' => 'wc-completed',
				'date_query' => array(
					 'after' => isset($from_date) ? date('Y-m-d', strtotime($from_date)) : '', // Filter by From Date
					 'before' => isset($to_date) ? date('Y-m-d', strtotime($to_date)) : '', // Filter by To Date
					 'inclusive' => true,
				),
				'posts_per_page' => -1,
				'fields' => 'ids', // Only fetch post IDs to improve performance
		   	);

			// Initialize variables for summary
			$total_us_orders = 0;
			$total_us_spend = 0;
			$total_non_us_orders = 0;
			$total_non_us_spend = 0;

			$us_customers = array();
			$non_us_customers = array();

			// Get all orders based on summary arguments
			$summary_query = new WP_Query($summary_args);

			// Iterate through orders to calculate summary
			foreach ($summary_query->posts as $order_id) {
				$order = wc_get_order($order_id);

				$customer_id = $order->get_customer_id();

				// Get customer location details
				$country = get_post_meta($order_id, '_billing_country', true);
				$state = get_post_meta($order_id, '_billing_state', true);

				// Update summary based on customer location
				if ($country === 'US') {

					 if (!in_array($customer_id, $us_customers)) {
						  array_push($us_customers, $customer_id);
					 }

					 // Increment US orders count
					 $total_us_orders++;

					 // Add order total to US spend
					 $total_us_spend += get_post_meta($order_id, '_order_total', true);
				} else {

					 if (!in_array($customer_id, $non_us_customers)) {
						  array_push($non_us_customers, $customer_id);
					 }

					 // Increment non-US orders count
					 $total_non_us_orders++;

					 // Add order total to non-US spend
					 $total_non_us_spend += get_post_meta($order_id, '_order_total', true);
				}
		   	}

			fwrite($file, "\n\n\n");
			fwrite($file, ",Total Customers,Total Orders,Total Spend\n");
			fwrite($file, 'US,'.count($us_customers).','.$total_us_orders.',$'.$total_us_spend. "\n" ); 
			fwrite($file, 'Non-US,'.count($non_us_customers).','.$total_non_us_orders.',$'.$total_non_us_spend. "\n" ); 
			fwrite($file, "Total Revenue,,,$".$total_us_spend + $total_non_us_spend."\n");



			// Start the states summary if needed.

			fclose($file);

			// Output CSV data
			// Get captured output and store in $csv_data
			// $csv_data = ob_get_clean();

			// // Output CSV data
			// echo $csv_data;
		
			// End script execution
			// wp_die();

			// Close the CSV file
			// fclose($csv_file);

			// Return JSON response with file URL or data
			// $response = array(
			// 	'success' => true,
			// 	'fileUrl' => home_url().'/exported_data.csv' // Replace with the actual file URL
			// );
			echo $csvfile_url;
		// }
		// Send the output as JSON response

		wp_die();
		}
	}
}

function handle_custom_query_var($query, $query_vars)
{

	if (isset($_GET['country']) && !empty($_GET['country'])) {
		$query['meta_query'] = array('relation' => 'OR');
		foreach ($_GET['country'] as $selected_country) {
			$query['meta_query'][] = array(
				'key' => '_billing_country',
				'value' => wc_clean($selected_country),
				// 'compare' => '=',
			);
		}
	}

	if (isset($_GET['state']) && !empty($_GET['state'])) {
		$query['meta_query'] = array('relation' => 'OR');
		foreach ($_GET['state'] as $selected_state) {
			$query['meta_query'][] = array(
				'key' => '_billing_state',
				'value' => wc_clean($selected_state),
				// 'compare' => '=',
			);
		}
	}

	return $query;
}
add_filter('woocommerce_order_data_store_cpt_get_orders_query', 'handle_custom_query_var', 10, 2);
