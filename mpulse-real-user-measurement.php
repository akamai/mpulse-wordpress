<?php
/*
Plugin Name: SOASTA mPulse RUM
Plugin URI: http://www.soasta.com/mpulse/
Description: What is the percieved speed of your web page & how does that impact your visitors? This is a plugin which allows you to include <a href="http://www.soasta.com/mpulse">mPulse Real User Measurement</a> in your WordPress site.
Version: 2.3
Author: SOASTA
*/

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

// Clean up after ourselves on delete
function mpulse_cleanup() {
    delete_option('mpulse_api_key');
}

// Add submenu to plugin menu
function mpulse_plugin_config() {
    add_options_page('SOASTA mPulse Real User Measurement Configuration', 'SOASTA mPulse', 'manage_options', 'mpulse_plugin_page', 'mpulse_plugin_page');
}

// Add plugin action link to Plugin page for mPulse Settings
function mpulse_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=mpulse_plugin_page') . '">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

// Show mPulse Settings page
function mpulse_plugin_page() {
    if (!current_user_can( 'manage_options'))  {
        wp_die(__('Sorry, you do not have sufficient permissions to access this page.'));
    }

    wp_enqueue_style("soasta_wp", plugin_dir_url("/", __FILE__) . trim(dirname(plugin_basename(__FILE__)), '/') . "/soasta_wp.css");
?>

<div id='container' class='wrap'>
    <img src="<?php echo plugin_dir_url("/", __FILE__) . trim(dirname(plugin_basename(__FILE__)), '/'); ?>/soasta_logo.png" alt="SOASTA Logo" />

    <h1>SOASTA mPulse - Real User Measurement</h1>
<?php
    if (isset($_POST['submit'])) {
        $mp_key = trim($_POST['mpulse_api_key']);
        $mp_pattern = '/^([A-Z0-9]{5}-){4}[A-Z0-9]{5}$/';

        if (preg_match($mp_pattern, $mp_key)) {
            update_option('mpulse_api_key', $mp_key);
            echo "<div class=\"updated\"><p>Your key has been updated.</p></div>";
        } else {
            $error = new WP_Error();
            $error->add('regerror','Foo!');
            echo "<div class=\"error\"><p>Your API key is invalid! Format: A1A1A-B2B2B-C3C3C-D4D4Dl-E5E5E</p></div>";
        }
    }
?>

    <p>Please enter your mPulse app's API KEY below. This can be found in your domain configuration within the <a href="http://mpulse.soasta.com">mPulse dashboard</a>.</p>

    <p>If you do not yet have an mPulse account, you can <a href="http://www.soasta.com/free" target="_blank" title="Create a free mPulse account">set one up (for FREE!)</a></p>

    <form method="post" action="" class="soasta_form">
        <input name="mpulse_api_key" type="text" id="mpulse_api_key" class="soasta_input" value="<?php echo get_option('mpulse_api_key');?>" maxlength="29" placeholder="A1A1A-B2B2B-C3C3C-D4D4D-E5E5E"/>
        <input name="submit" type="submit" class="soasta_submit"/>
    </form>

    <p><a href="<?php echo get_bloginfo('wpurl');?>/wp-admin/plugins.php">Back to Plugins...</a></p>
</div>

<?php
}

// Include the snippet in the head of each page
function mpulse_add_rum_header() {
?>
<script>
(function(){
  // Boomerang Loader Snippet version 10
  if (window.BOOMR && (window.BOOMR.version || window.BOOMR.snippetExecuted)) {
    return;
  }

  window.BOOMR = window.BOOMR || {};
  window.BOOMR.snippetExecuted = true;

  var dom, doc, where, iframe = document.createElement("iframe"), win = window;

  function boomerangSaveLoadTime(e) {
    win.BOOMR_onload = (e && e.timeStamp) || new Date().getTime();
  }

  if (win.addEventListener) {
    win.addEventListener("load", boomerangSaveLoadTime, false);
  } else if (win.attachEvent) {
    win.attachEvent("onload", boomerangSaveLoadTime);
  }

  iframe.src = "javascript:void(0)";
  iframe.title = "";
  iframe.role = "presentation";
  (iframe.frameElement || iframe).style.cssText = "width:0;height:0;border:0;display:none;";
  where = document.getElementsByTagName("script")[0];
  where.parentNode.insertBefore(iframe, where);

  try {
    doc = iframe.contentWindow.document;
  } catch (e) {
    dom = document.domain;
    iframe.src = "javascript:var d=document.open();d.domain='" + dom + "';void(0);";
    doc = iframe.contentWindow.document;
  }

  doc.open()._l = function() {
    var js = this.createElement("script");
    if (dom) {
      this.domain = dom;
    }
    js.id = "boomr-if-as";
    js.src = '//c.go-mpulse.net/boomerang/' +
    "<?php echo get_option('mpulse_api_key');?>";
    BOOMR_lstart = new Date().getTime();
    this.body.appendChild(js);
  };
  // NOTE: Using String.fromCharCode to avoid an issue with W3TC plugin
  doc.write('<bo' + 'dy onload="document._l();"' + String.fromCharCode(62));
  doc.close();
})();
</script>
<?php
}

add_action('wp_head', 'mpulse_add_rum_header', 0);
add_action('admin_menu', 'mpulse_plugin_config');
add_filter('plugin_action_links', 'mpulse_plugin_action_links', 10, 2);

// If deleting mPulse plugin, clean up after ourselves
if (function_exists('register_uninstall_hook')) {
    register_uninstall_hook(__FILE__, 'mpulse_cleanup');
}
?>
