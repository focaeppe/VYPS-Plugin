<?php

//Shortcode itself.

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//NOTE: This is the shortcode we need to use going forward
//NOTE: Also, going forward there will be no simple miner you can display without consent button. Sorry. Not. Sorry.

function vyps_vy256_solver_func($atts) {

  //Ok. Some shortcode defaults. Thread and throttle are optional
  //but I'm not going to let people start at 100% unless they mean it.
  //So by default the miner starts with 1 thread at 10% and the users
  //Can crank it up if they want.
  //ALso I'm putting in VidYen's test server API keys as defaults
  //But I will put a warning that you did not set the keys but you are
  //earning VidYen hashes directly. I mean you can do that, but...
  //I try not to question the "Why would?" scenarios these days.
  //-Felty


  //I felt it easier to just check if user is logged in and just do nothing at that point.
  //Admins can use the VYPS login check to warn people they need to be logged in.
  if ( ! is_user_logged_in() ){

    return;

  }

  $atts = shortcode_atts(
  array(
      'skey' => '5y8ys1vO4guiyggOblimkt46sAOWDc8z',
      'pkey' => 'A6YSYjxSpS0NY6sZiBbtV6qdx4006Ypw',
      'pid' => 0,
      'suid' => 'default',
      'threads' => '1',
      'throttle' => '90',
  ), $atts, 'vyps-256' );

  //Error out if the PID wasn't set as it doesn't work otherwise.
  //In theory they still need to consent, but no Coinhive code will be displayed
  //until the site admin fixes it. I suppose in theory one could set a negative number -Felty
  if ($atts['pid'] == 0){

      return "ADMIN ERROR: Point ID not set!";

  }

  //NOTE: Where we are going we don't need $wpdb

  $sm_site_key = $atts['skey'];
  $hiveKey = $atts['pkey'];
  $sm_siteUID = $atts['suid'];
  $sm_threads = $atts['threads'];
  $sm_throttle = $atts['throttle'];
  $pointID = $atts['pid'];
  $current_user_id = get_current_user_id();
  $sm_user = $sm_siteUID . $current_user_id;
  $hiveUser = $sm_siteUID . $current_user_id;

  if ($sm_site_key == '5y8ys1vO4guiyggOblimkt46sAOWDc8z') {

    $site_warning = "<tr><td><b>You appear to have not set a site key. Hashes are going to VidYen, not your site!</b></td></tr>";

  } else {

    $site_warning = '';

  }

	if (isset($_POST["consent"]) AND is_user_logged_in() ){ // Just checking if they clicked conset and are logged in case something dumb happened.

    //Ok. Something needs to be in the $redeem_ouput to satisfy my OCD
    $redeem_output = "<tr><td>Click the button above to convert the total hashes into points.</td></tr>"; //putting this in a table

    //Get the url for the solver
    $vy256_solver_folder_url = plugins_url( 'js/solver/', __FILE__ );
    //$vy256_solver_url = plugins_url( 'js/solver/miner.js', __FILE__ ); //Ah it was the worker.

    //Need to take the shortcode out. I could be wrong. Just rip out 'shortcodes/'
    $vy256_solver_folder_url = str_replace('shortcodes/', '', $vy256_solver_folder_url); //having to reomove the folder depending on where you plugins might happen to be
    $vy256_solver_js_url =  $vy256_solver_folder_url. 'solver.js';
    $vy256_solver_worker_url = $vy256_solver_folder_url. 'worker.js';

    //Ok some issues we need to know the path to the js file so will have to ess with that.
		$simple_miner_output = "
    <table>
      $site_warning
      <tr><td>
        <div>
          <textarea rows=\"4\" cols=\"50\" id=\"texta\"></textarea>
        </div>
        <div>
          <button id=\"startb\" onclick=\"start()\">Start mining!</button>
        </div>
        <script>var newWorker = new Worker(\"$vy256_solver_worker_url\");</script>
        <script src=\"$vy256_solver_js_url\"></script>
        <script>

          function start() {

            document.getElementById(\"startb\").disabled = true; // disable button



            /* start mining, use a local server */
            server = \"wss://www.vy256.com:8181\";

            startMining(\"moneroocean.stream\",
              \"48Vi6kadiTtTyemhzigSDrZDKcH6trUTA7zXzwamziSmAKWYyBpacMjWbwaVe4vUMveKAzAiA4j8xgUi29TpKXpm3wL5K5a\");

            /* keep us updated */

            addText(\"Connecting...\");

            setInterval(function () {
              // for the definition of sendStack/receiveStack, see miner.js
              while (sendStack.length > 0) addText((sendStack.pop()));
              while (receiveStack.length > 0) addText((receiveStack.pop()));
              addText(\"calculated \" + totalhashes + \" hashes.\");
            }, 2000);

          }

          /* helper function to put text into the text field.  */

          function addText(obj) {

            var elem = document.getElementById(\"texta\");
            elem.value += \"[\" + new Date().toLocaleString() + \"] \";

            if (obj.identifier === \"job\")
              elem.value += \"new job: \" + obj.job_id;
            else if (obj.identifier === \"solved\")
              elem.value += \"solved job: \" + obj.job_id;
            else if (obj.identifier === \"hashsolved\")
              elem.value += \"pool accepted hash!\";
            else if (obj.identifier === \"error\")
              elem.value += \"error: \" + obj.param;
            else elem.value += obj;

            elem.value += \"" . '\n' . "\";
            elem.scrollTop = elem.scrollHeight;

          }

        </script>
      </td>
      <tr><td>
        <form method=\"post\">
          <input type=\"hidden\" value=\"\" name=\"consent\"/>
        <input type=\"submit\" class=\"button-secondary\" value=\"Redeem Hashes\" onclick=\"return confirm('Did you want to sync your mined hashes with this site?');\" />
        </form>
      </td></tr>
      $redeem_output
    </table>";

    //btw I set this to only allow consent for testing -Felty

	} elseif (isset($_POST["redeem"]) AND is_user_logged_in()) { //see if post button is redeem and logged in.

      //Ok. Actually not setting PID to something doesn't matter for mining.
      //However, when you try to redeem, its a big issue if you don't know which point you are redeeming to.

			//Copied and pasted from the old VidYen.com code
			// fetch from DB
			//$hiveUser = $user->id;
			//$hiveKey = 'baMweSSSVy93nOaQXOuQ0rKFRQlX0PY1';
			// --------------------

			$url = "https://api.coinhive.com/user/balance?name={$hiveUser}&secret={$hiveKey}";

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			curl_close($ch);

			$jsonData = json_decode($result, true);
			$balance = $jsonData['balance'];

			/* echo $balance;

			$hostBalance = $unbalance + ($unbalance - $balance);

			echo $hostBalance; */

			//
			// A very simple PHP example that sends a HTTP POST to a remote site
			//

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL,"https://api.coinhive.com/user/withdraw");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,
				"name={$hiveUser}&amount={$balance}&secret={$hiveKey}");

			// in real life you should use something like:
			// curl_setopt($ch, CURLOPT_POSTFIELDS,
			//          http_build_query(array('postvar1' => 'value1')));

			// receive server response ...
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$server_output = curl_exec ($ch);

			curl_close ($ch);

			// further processing ....
			//if ($server_output == "OK") { ... } else { ... }

			/* OK. Pulling log table to post return to it. What could go wrong? */
			/* Honestly, we should always refer to table by the actual table?   */

			/* Just checking to see if balance is 0. If it is, no need to do anything other than return the results.*/
			if( $balance > 0 )
			{
        //Ok we need to actually use $wpdb here as its going to feed into the log of course.
				global $wpdb;

				$table_log = $wpdb->prefix . 'vyps_points_log';
				$reason = "Coinhive Mining"; //I feel like this should be a shortcode attr but maybe pro version feature.
				$amount = doubleval($balance); //Well in theory the json_decode could blow up I suppose better safe than sorry.
        $pointType = intval($pointID); //Point type should be int.
				$user_id = get_current_user_id();

        //Inserting Coin Hive row.
				$data = [
						'reason' => $reason,
						'point_id' => $pointType,
						'points_amount' => $amount,
						'user_id' => $user_id,
						'time' => date('Y-m-d H:i:s')
				];
				$wpdb->insert($table_log, $data);
			} else {

        $balance = 0; //I remembered if it gets returned a blank should be made a zero.
      }

			$redeem_output = "<tr><td>$balance Coinhive hashes redeemed.</td></tr>"; //putting this in a table

      //Fix this into table with output as I don't like miner move but also needs to be someplace uniform
      $simple_miner_output = "
      <table>
        $site_warning
        <tr><td>
          <script src=\"https://authedmine.com/lib/simple-ui.min.js\" async></script>
          <div class=\"coinhive-miner\"
            style=\"width: 256px; height: 310px\"
            data-key=\"$sm_site_key\"
            data-threads=\"$sm_threads\"
            data-throttle=\"$sm_throttle\"
            data-user=\"$sm_user\"
            >
            <em>Loading... You may have an adblocker on or javascript turned off.</em>
            </div>
        </td>
        <tr><td>
          <form method=\"post\">
            <input type=\"hidden\" value=\"\" name=\"redeem\"/>
          <input type=\"submit\" class=\"button-secondary\" value=\"Redeem Hashes\" onclick=\"return confirm('Did you want to sync your mined hashes with this site?');\" />
          </form>
        </td></tr>
        $redeem_output
      </table>";


	} else {

		 return; //Well. Niether consent button or redeem were clicked sooo.... You get nothing.

	}

  return $simple_miner_output;

}

/* Telling WP to use function for shortcode for sm-consent*/

add_shortcode( 'vyps-256', 'vyps_vy256_solver_func');



/* Shortcode for the API call to create a lot entry */
/* There is some debate if this should be a button, but I'm just going to run on the code on page load and the admins can just make a button that runs the smart code if they want */

function vyps_solver_consent_button_func( $atts ) {

  //Some shortcode attributes to create custom button message
  $atts = shortcode_atts(
  array(

      'txt' => 'I agree and consent',

  ), $atts, 'vyps-ch-consent' );

  $button_text = $atts['txt'];

	/* User needs to be logged into consent. NO EXCEPTIONS */

	if ( is_user_logged_in() ) {

		return "<form method=\"post\">
                <input type=\"hidden\" value=\"\" name=\"consent\"/>
                <input type=\"submit\" class=\"button-secondary\" value=\"$button_text\" onclick=\"return confirm('Did you read everything and consent to letting this page browser mine with your CPU?');\" />
                </form>";

	} else {

		return "You need to be logged in to consent!"; //I feel like admin an use a
	}

}

add_shortcode( 'vyps-256-consent', 'vyps_solver_consent_button_func');