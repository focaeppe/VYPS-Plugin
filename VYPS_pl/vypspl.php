<?php
/*
  Plugin Name: VYPS Public Log Shortcode Addon
  Description: Adds user a public log to the VYPS Plugin
  Version: 0.0.01
  Author: VidYen, LLC
  Author URI: https://vidyen.com/
  License: GPLv2 or later
 */

 /* 
* Originally copy and paste of the balance shortcode plugin
* I realized, when I decided to do a weighted raffle system
* I needed a way so people could see activity of everything
 */
 
register_activation_hook(__FILE__, 'vyps_pl_install');

/* Removed all the database and table creation call as this addon does not need its own table
*  Or even an uninstall file as it just adds code fuctionality. Yes you can get on me for not
*  adding this to the base, but it was a Chris Roberts call. You can make your own version if
*  it bothers you that much.
*/



add_action('admin_menu', 'vyps_pl_submenu', 17);

/* Creates the Coin Hive submenu on the main VYPS plugin */

function vyps_pl_submenu() 
{
	$parent_menu_slug = 'vyps_points';
	$page_title = "Public Log Shortcode";
    $menu_title = 'Public Log Shortcode';
	$capability = 'manage_options';
    $menu_slug = 'vyps_pl_page';
    $function = 'vyps_pl_sub_menu_page';

    add_submenu_page($parent_menu_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
}


/* Below is the functions for the shortcode */

function vyps_pl_sub_menu_page() 
{ 
	/* Actually I don't think I need to do calls on this page */
    
	echo
	"<h1>Welcome to Public Log Shortcode Addon Plugin</h1>
	<p>This plugin addon to the VYPS allow you add shortcodes so your users can see their own and other users balances.</p>
	<h2>Shortcodes Syntax</h2>
	<p><b>[vyps-pl]</b></p>
	<p> Shows a list of all points with the current balance along with name for logged in user. They must be logged into see this.</p>
	<p><b>[vyps-balance-list pid=&quot;#&quot; uid=&quot;#&quot;]</b></p>
	<p>Replace the # with desired numerical value of the pid and uid.</p>
	<p>The pid is the pointID number seen on the points list page along with the uid which is the user id in WordPress. Leaving the uid option out (ie. [vyps-balance-list pid=&quote;&quote;]  will default to the logged on user.</p>
	<p>Note: Leaving the uid blank will tell the user they need to log in if you intend to show this to users who are not log in.</p>
	<p>Also Note: pid will default to 1 which is the first point you have unless you delete it. I would recommend specifing pid at all times.</p>
	<h2>Here is a list of our other addons that go along with this system:</h2>
	<p>Coin Hive addon plugin</p>
	<p>AdScend Plugin</p>
	<p>WooWallet Bridge Plugin</p>
	<p>CoinFlip Game Plugin</p>
	<p>Balance Shortcode Plugin</p>
	<p>Plublic Log Plugin</p>";
} 

/* I'm shouting in caps as I need to tell which shortcode is which */

/* LIST FUCNTION SHORTCODE
*  Because an admin might just want a full list without messing around with
*  variables etc. Why not just make a single shortcode with differing variables?
*  Because this is how I would like it if I was admin with no coding experience.
*  I could in theory make a pid or uid for this, but honesty just recontruct the
*  shortcode [vyps-balance] to do that. It's easier for an admin to do that with
*  WP than me to mess around on the code end. Do not mistake my generosity for
*  generosity.
*/

function pl_func() {
	
	/* Technically users don't have to be logged in
	* Should litterally be the log the admin sees 
	* I don't care. Tell users to not put personal identificable 
	* information in their user name (referred to PID in the health care industry)
	*/
	
	global $wpdb;
    $query2 = "select * from {$wpdb->prefix}vyps_points_log order by time desc";
    $point_logs = $wpdb->get_results($query2);
    ?>
    <div class="wrap">        
        <h1 class="wp-heading-inline">Public Point Log</h1>        
        <h2>Point Log</h2>
        <table class="wp-list-table widefat fixed striped users">
            <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Point type</th>
                <th>Amount +/-</th>
                <th>Adjustment Reason</th>
            </tr>
            <?php if (!empty($point_logs)): ?>
                <?php $i = 0; ?>
                <?php foreach ($point_logs as $logs): ?>
                    <tr>
                        <td><?= $logs->time; ?></td>
                        <td><?php
                            $userdata = get_userdata($logs->user_id);
                            echo $userdata->data->user_nicename; //I'm guessing this works with the user_nicename. Who knows.
                            ?>
                        <td><?php
                            $points_name = $wpdb->get_row("select * from {$wpdb->prefix}vyps_points where id= '{$logs->points}'");
                            echo $points_name->name;
                        ?></td>
						<td><?= $logs->points_amount; ?></td>
                        <td><?= $logs->reason; ?></td>
                        
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No data found yet.</td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Point type</th>
                <th>Amount +/-</th>
                <th>Adjustment Reason</th>
            </tr>
        </table>
    </div>
	<?php
	
}
	
/* 
* Shortcode for the log.
*/

add_shortcode( 'vyps-pl', 'pl_func');
