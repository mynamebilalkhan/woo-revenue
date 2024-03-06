<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.presstigers.com
 * @since      1.0.0
 *
 * @package    Pt_Revenue
 * @subpackage Pt_Revenue/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="pt-revenue-wrapper">

     <div class="pt-revenue-filters">
          <form method="get" id="filter-form">
               <input type="hidden" name="page" value="custom-orders">

               <input type="date" name="from_date" placeholder="From Date" value="<?php echo isset($_GET['from_date']) ? esc_attr($_GET['from_date']) : ''; ?>" />
               <input type="date" name="to_date" placeholder="To Date" value="<?php echo isset($_GET['to_date']) ? esc_attr($_GET['to_date']) : ''; ?>" />

               <input type="hidden" id="selected-countries" name="selected_countries" value="<?php echo isset($_GET['selected_countries']) ? esc_attr($_GET['selected_countries']) : ''; ?>">
               <input type="hidden" id="selected-states" name="selected_states" value="<?php echo isset($_GET['selected_states']) ? esc_attr($_GET['selected_states']) : ''; ?>">
               <?php
               // Dropdown for countries
               $countries = WC()->countries->get_countries();
               $selected_countries = isset($_GET['country']) ? (array)$_GET['country'] : array();
               ?>
               <select name="country[]" id="country" class="select_country_multiple js-example-basic-multiple" multiple="multiple">
                    <option value="">Select Country</option>
                    <option value="US" <?php echo in_array('US', $selected_countries) ? 'selected' : ''; ?>>United States</option>
                    <?php
                    foreach ($countries as $code => $name) {
                         if ($code !== 'US') {
                    ?>
                              <option value="<?php echo esc_attr($code); ?>" <?php echo in_array($code, $selected_countries) ? 'selected' : ''; ?>><?php echo esc_html($name); ?></option>
                    <?php
                         }
                    }
                    ?>
               </select>

               <?php
               // Dropdown for states based on the selected country
               $selected_states = isset($_GET['state']) ? (array)$_GET['state'] : array();
               $states = array();
               if (!empty($selected_countries)) {
                    foreach ($selected_countries as $country) {
                         $states += WC()->countries->get_states($country);
                    }
               }
               ?>
               <select name="state[]" id="state" class="select_state_multiple js-example-basic-multiple" multiple="multiple">
                    <option value="">Select State</option>
                    <?php
                    foreach ($states as $code => $name) {
                    ?>
                         <option value="<?php echo esc_attr($code); ?>" <?php echo in_array($code, $selected_states) ? 'selected' : ''; ?>><?php echo esc_html($name); ?></option>
                    <?php
                    }
                    ?>
               </select>
               <div><p class="pt-states-error"></p></div>

               <input type="submit" value="Filter" name="pr-revenue" />
          </form>
     </div>

     <?php if (isset($_GET['pr-revenue'])) { ?>
          <div class="pt-download-csv">
               <div id="csvfile"><a href="#!" class="pt-download-csv-btn">Generate CSV</a></div>
               <div id="csvLink"></div>
          </div>
     <?php } ?>


     <table class="wp-list-table widefat fixed striped table-view-list orders">
          <caption class="screen-reader-text">Table ordered by Date. Descending.</caption>
          <thead>
               <tr>
                    <th scope="col" id="no" class="no-column manage-column column-no">ID</th>
                    <th scope="col" id="name" class="manage-column column-name">Name</th>
                    <th scope="col" id="orders" class="manage-column column-orders">Orders</th>
                    <th scope="col" id="email" class="manage-column column-email">Email</th>
                    <th scope="col" id="country" class="manage-column column-country">Country</th>
                    <th scope="col" id="state" class="manage-column column-state">State</th>
                    <th scope="col" id="amount" class="manage-column column-amount">Amount</th>
                    <th scope="col" id="status" class="manage-column column-status">Date</th>
               </tr>
          </thead>

          <tbody id="the-list">
               <?php
               if (isset($_GET['pr-revenue'])) {

                    $current_page = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);

                    $args = array(
                         'date_after' => isset($_GET['from_date']) ? date('Y-m-d', strtotime($_GET['from_date'])) : '', // Filter by From Date
                         'date_before' => isset($_GET['to_date']) ? date('Y-m-d', strtotime($_GET['to_date'])) : '', // Filter by To Date
                         'status' => array('wc-completed'),
                         'limit' => 20,
                         'paged' => $current_page,
                         'paginate' => true,
                    );

                    $orders = wc_get_orders($args);

                    // Array to store the consolidated orders for each customer
                    $consolidated_orders = array();

                    // Consolidate orders by customer
                    foreach ($orders->orders as $order) {
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

                    // Display orders
                    if ($orders) {
                         foreach ($consolidated_orders as $customer_id => $customer_data) {
               ?>
                              <tr>
                                   <td><?php echo $customer_data['order_id']; ?></td>
                                   <td><?php echo $customer_data['customer_name']; ?></td>
                                   <td><?php echo $customer_data['orders_count']; ?></td>
                                   <td><?php echo $customer_data['customer_email']; ?></td>
                                   <td><?php echo $customer_data['customer_country']; ?></td>
                                   <td><?php echo $customer_data['customer_state']; ?></td>
                                   <td><?php echo wc_price($customer_data['total_amount']); ?></td>
                                   <!-- <td><?php //echo implode(', ', $customer_data['order_status']); 
                                             ?></td> -->
                                   <td><?php echo $customer_data['order_date']; ?></td>
                              </tr>
                         <?php
                         }
                    } else {
                         ?>
                         <tr class="no-items">
                              <td class="colspanchange" colspan="8">No record found.</td>
                         </tr>
                    <?php
                    }
               } else {
                    ?>
                    <tr class="no-items">
                         <td class="colspanchange" colspan="8">Please select filter to get data.</td>
                    </tr>
               <?php
               }
               ?>
          </tbody>

          <tfoot>
               <tr>
                    <th scope="col" id="no" class="manage-column column-no">ID</th>
                    <th scope="col" id="name" class="manage-column column-name">Name</th>
                    <th scope="col" id="orders" class="manage-column column-orders">Orders</th>
                    <th scope="col" id="email" class="manage-column column-email">Email</th>
                    <th scope="col" id="country" class="manage-column column-country">Country</th>
                    <th scope="col" id="state" class="manage-column column-state">State</th>
                    <th scope="col" id="amount" class="manage-column column-amount">Amount</th>
                    <th scope="col" id="status" class="manage-column column-status">Date</th>
               </tr>
          </tfoot>

     </table>
     <div class="pagination-area">
          <!-- Pagination -->
          <?php

          if (isset($orders->total)) {
               echo "<div class='pagination-area-results'>" . $orders->total . " orders found Page " . $current_page . " of " . $orders->max_num_pages . "</div>";
               $total_orders = $orders->total;

               // Calculate the total number of pages
               $total_pages = $orders->max_num_pages;

               echo '<div class="pagination-links">';
               // Output pagination
               echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page,
               ));
               echo '</div>';
          }
          ?>
     </div>

     <div class="pt-revenue-summary">

          <!-- ###################USA ONE STATE VS REST OF USA###################### -->
          <?php
          $total_customer_in_state = array();
          $total_orders_in_state = 0;
          $total_spend_in_state = 0;
          $total_customer_in_rest_of_state = array();
          $total_orders_in_rest_of_state = 0;
          $total_spend_in_rest_of_state = 0;
          ?>

          <?php if (isset($_GET['pr-revenue']) && isset($_GET['country']) && !empty($_GET['country']) && isset($_GET['state']) && !empty($_GET['state'])) {
               // Define summary arguments for orders
               $summary_args_usa = array(
                    'post_type' => 'shop_order',
                    'post_status' => 'wc-completed',
                    'date_query' => array(
                         'after' => isset($_GET['from_date']) ? date('Y-m-d', strtotime($_GET['from_date'])) : '', // Filter by From Date
                         'before' => isset($_GET['to_date']) ? date('Y-m-d', strtotime($_GET['to_date'])) : '', // Filter by To Date
                         'inclusive' => true,
                    ),
                    'posts_per_page' => -1, // Adjust the number of orders per page as needed
                    'fields' => 'ids', // Only fetch post IDs to improve performance
               );

               if (isset($_GET['country']) && !empty($_GET['country'])) {
                    $summary_args_usa['meta_query'] = array('relation' => 'OR');
                    foreach ($_GET['country'] as $selected_country) {
                         $summary_args_usa['meta_query'][] = array(
                              'key' => '_billing_country',
                              'value' => wc_clean($selected_country),
                              // 'compare' => '=',
                         );
                    }
               }

               // Get all orders based on summary arguments
               $summary_query_usa = new WP_Query($summary_args_usa);

               $selected_country = $_REQUEST['country'];
               $selected_states = $_REQUEST['state'];
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
          ?>
               <!-- Second table for US vs Non-US -->
               <!-- Add your code for the second table here -->

               <h3>Summary</h3>
               <table class="wp-list-table widefat fixed striped table-view-list orders summary-table">
                    <thead>
                         <tr>
                              <th></th>
                              <th><strong>Total Customers</strong></th>
                              <th><strong>Total Orders</strong></th>
                              <th><strong>Total Spend</strong></th>
                         </tr>
                    </thead>
                    <tbody>
                         <?php

                         $grand_total = 0.0;

                         foreach ($selected_states_summary as $country => $state) {

                              foreach ($state as $k => $v) {
                                   echo '<tr>
                                        <td>' . WC()->countries->states[$country][$k] . '</td>
                                        <td>' . count(array_unique($v['customers'])) . '</td>
                                        <td>' . $v['total_order'] . '</td>
                                        <td>' . wc_price($v['total_spend']) . '</td>
                                   </tr>';
                                   $grand_total += $v['total_spend'];
                              }
                         }

                         echo '<tr>
                              <td>Other States of selected Countries</td>
                              <td>' . count(array_unique($other_states_summary['customers'])) . '</td>
                              <td>' . $other_states_summary['total_order'] . '</td>
                              <td>' . wc_price($other_states_summary['total_spend']) . '</td>
                         </tr>';
                         $grand_total += $other_states_summary['total_spend'];
                         ?>
                    </tbody>
                    <tfoot>
                         <tr>
                              <td class="colspanchange" colspan="3"><strong>Total US Orders</strong></td>
                              <td><strong><?php echo wc_price($grand_total); ?></strong></td>
                         </tr>
                    </tfoot>
               </table>
          <?php } ?>
          <div class="gap-space"></div>

          <!-- ###################USA VS NON-USA###################### -->

          <?php if (isset($_GET['pr-revenue'])) {
               // Define summary arguments for orders
               $summary_args = array(
                    'post_type' => 'shop_order',
                    'post_status' => 'wc-completed',
                    'date_query' => array(
                         'after' => isset($_GET['from_date']) ? date('Y-m-d', strtotime($_GET['from_date'])) : '', // Filter by From Date
                         'before' => isset($_GET['to_date']) ? date('Y-m-d', strtotime($_GET['to_date'])) : '', // Filter by To Date
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

          ?>
               <table class="wp-list-table widefat fixed striped table-view-list orders">
                    <thead>
                         <tr>
                              <th></th>
                              <th><strong>Total Customers</strong></th>
                              <th><strong>Total Orders</strong></th>
                              <th><strong>Total Spend</strong></th>
                         </tr>
                    </thead>
                    <tbody>
                         <tr>
                              <td>US</td>
                              <td><?php echo count($us_customers); ?></td>
                              <td><?php echo $total_us_orders; ?></td>
                              <td><?php echo wc_price($total_us_spend); ?></td>
                         </tr>
                         <tr>
                              <td>Non-US</td>
                              <td><?php echo count($non_us_customers); ?></td>
                              <td><?php echo $total_non_us_orders; ?></td>
                              <td><?php echo wc_price($total_non_us_spend); ?></td>
                         </tr>
                    </tbody>
                    <tfoot>
                         <tr>
                              <td class="colspanchange" colspan="3"><strong>Total Revenue</strong></td>
                              <td><strong><?php echo wc_price($total_us_spend + $total_non_us_spend); ?></strong></td>
                         </tr>
                    </tfoot>
               </table>

          <?php } ?>
     </div>

</div>