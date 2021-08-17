<?php
/*
Plugin Name: Akamai mPulse RUM
Plugin URI: https://www.akamai.com/us/en/products/web-performance/mpulse-real-user-monitoring.jsp
Description: What is the percieved speed of your web page & how does that impact your visitors? This is a plugin which allows you to include <a href="https://www.akamai.com/us/en/products/web-performance/mpulse-real-user-monitoring.jsp">mPulse Real User Measurement</a> in your WordPress site.
Version: 2.7
Author: Akamai
*/

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    exit;
}

// Clean up after ourselves on delete
function mpulse_cleanup() {
    delete_option('mpulse_api_key');
}

// Add submenu to plugin menu
function mpulse_plugin_config() {
    add_options_page('Akamai mPulse Real User Measurement Configuration', 'Akamai mPulse', 'manage_options', 'mpulse_plugin_page', 'mpulse_plugin_page');
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

    wp_enqueue_style("akamai_wp", plugin_dir_url("/", __FILE__) . trim(dirname(plugin_basename(__FILE__)), '/') . "/akamai_wp.css");
?>

<div id='container' class='wrap'>
    <img src="<?php echo plugin_dir_url("/", __FILE__) . trim(dirname(plugin_basename(__FILE__)), '/'); ?>/akamai_logo.png" alt="Akamai Logo" />

    <h1>Akamai mPulse - Real User Measurement</h1>
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

    <p>If you do not yet have an mPulse account, you can <a href="https://www.akamai.com/us/en/products/web-performance/mpulse-real-user-monitoring.jsp" target="_blank" title="Create a free mPulse account">set one up (for FREE!)</a></p>

    <form method="post" action="" class="akamai_form">
        <input name="mpulse_api_key" type="text" id="mpulse_api_key" class="akamai_input" value="<?php echo get_option('mpulse_api_key');?>" maxlength="29" placeholder="A1A1A-B2B2B-C3C3C-D4D4D-E5E5E"/>
        <input name="submit" type="submit" class="akamai_submit"/>
    </form>

    <p><a href="<?php echo get_bloginfo('wpurl');?>/wp-admin/plugins.php">Back to Plugins...</a></p>
</div>

<?php
}

// Include the snippet in the head of each page
function mpulse_add_rum_header() {
?>
<script>
(function() {
    // Boomerang Loader Snippet version 15
    if (window.BOOMR && (window.BOOMR.version || window.BOOMR.snippetExecuted)) {
        return;
    }

    window.BOOMR = window.BOOMR || {};
    window.BOOMR.snippetStart = new Date().getTime();
    window.BOOMR.snippetExecuted = true;
    window.BOOMR.snippetVersion = 15;

    window.BOOMR.url = "https://c.go-mpulse.net/boomerang/<?php echo get_option('mpulse_api_key'); ?>";

    var // document.currentScript is supported in all browsers other than IE
        where = document.currentScript || document.getElementsByTagName("script")[0],
        // Parent element of the script we inject
        parentNode = where.parentNode,
        // Whether or not Preload method has worked
        promoted = false,
        // How long to wait for Preload to work before falling back to iframe method
        LOADER_TIMEOUT = 3000;

    // Tells the browser to execute the Preloaded script by adding it to the DOM
    function promote() {
        if (promoted) {
            return;
        }

        var script = document.createElement("script");
        script.id = "boomr-scr-as";
        script.src = window.BOOMR.url;

        // Not really needed since dynamic scripts are async by default and the script is already in cache at this point,
        // but some naive parsers will see a missing async attribute and think we're not async
        script.async = true;

        parentNode.appendChild(script);

        promoted = true;
    }

    // Non-blocking iframe loader (fallback for non-Preload scenarios) for all recent browsers.
    // For IE 6/7/8, falls back to dynamic script node.
    function iframeLoader(wasFallback) {
        promoted = true;

        var dom, doc = document, bootstrap, iframe, iframeStyle, win = window;

        window.BOOMR.snippetMethod = wasFallback ? "if" : "i";

        // Adds Boomerang within the iframe
        bootstrap = function(parent, scriptId) {
            var script = doc.createElement("script");
            script.id = scriptId || "boomr-if-as";
            script.src = window.BOOMR.url;

            BOOMR_lstart = new Date().getTime();

            parent = parent || doc.body;
            parent.appendChild(script);
        };

        // For IE 6/7/8, we'll just load the script in the current frame:
        // * IE 6/7 don't support 'about:blank' for an iframe src (it triggers warnings on secure sites)
        // * IE 8 required a doc write call for it to work, which is bad practice
        // This means loading on IE 6/7/8 may cause SPoF.
        if (!window.addEventListener && window.attachEvent && navigator.userAgent.match(/MSIE [678]\./)) {
            window.BOOMR.snippetMethod = "s";

            bootstrap(parentNode, "boomr-async");
            return;
        }

        // The rest of this function is for browsers that don't support Preload hints but will work with CSP & iframes
        iframe = document.createElement("IFRAME");

        // An empty frame
        iframe.src = "about:blank";

        // We set title and role appropriately to play nicely with screen readers and other assistive technologies
        iframe.title = "";
        iframe.role = "presentation";

        // Ensure we're not loaded lazily
        iframe.loading = "eager";

        // Hide the iframe
        iframeStyle = (iframe.frameElement || iframe).style;
        iframeStyle.width = 0;
        iframeStyle.height = 0;
        iframeStyle.border = 0;
        iframeStyle.display = "none";

        // Append to the end of the current block
        parentNode.appendChild(iframe);

        // Try to get the iframe's document object
        try {
            win = iframe.contentWindow;
            doc = win.document.open();
        }
        catch (e) {
            // document.domain has been changed and we're on an old version of IE, so we got an access denied.
            // Note: the only browsers that have this problem also do not have CSP support.

            // Get document.domain of the parent window
            dom = document.domain;

            // Set the src of the iframe to a JavaScript URL that will immediately set its document.domain to match the parent.
            // This lets us access the iframe document long enough to inject our script.
            // Our script may need to do more domain massaging later.
            iframe.src = "javascript:var d=document.open();d.domain='" + dom + "';void 0;";
            win = iframe.contentWindow;

            doc = win.document.open();
        }

        // document.domain hasn't changed, regular method should be OK
        win._boomrl = function() {
            bootstrap();
        };

        if (win.addEventListener) {
            win.addEventListener("load", win._boomrl, false);
        }
        else if (win.attachEvent) {
            win.attachEvent("onload", win._boomrl);
        }

        // Finish the document
        doc.close();
    }

    // See if Preload is supported or not
    var link = document.createElement("link");

    if (link.relList &&
        typeof link.relList.supports === "function" &&
        link.relList.supports("preload") &&
        ("as" in link)) {
        window.BOOMR.snippetMethod = "p";

        // Set attributes to trigger a Preload
        link.href = window.BOOMR.url;
        link.rel  = "preload";
        link.as   = "script";

        // Add our script tag if successful, fallback to iframe if not
        link.addEventListener("load", promote);
        link.addEventListener("error", function() {
            iframeLoader(true);
        });

        // Have a fallback in case Preload does nothing or is slow
        setTimeout(function() {
            if (!promoted) {
                iframeLoader(true);
            }
        }, LOADER_TIMEOUT);

        // Note the timestamp we started trying to Preload
        BOOMR_lstart = new Date().getTime();

        // Append our link tag
        parentNode.appendChild(link);
    }
    else {
        // No Preload support, use iframe loader
        iframeLoader(false);
    }

    // Save when the onload event happened, in case this is a non-NavigationTiming browser
    function boomerangSaveLoadTime(e) {
        window.BOOMR_onload = (e && e.timeStamp) || new Date().getTime();
    }

    if (window.addEventListener) {
        window.addEventListener("load", boomerangSaveLoadTime, false);
    }
    else if (window.attachEvent) {
        window.attachEvent("onload", boomerangSaveLoadTime);
    }
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
