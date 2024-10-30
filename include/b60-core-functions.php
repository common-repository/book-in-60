<?php 
/*
*
*	***** Booking 60 *****
*
*	Core Functions
*	
*/
// If this file is called directly, abort. //
if ( ! defined( 'WPINC' ) ) {die;} // end if
/*
*
* Custom Front End Ajax Scripts / Loads In WP Footer
*
*/
function b60_frontend_ajax_form_scripts(){
?>
<script type="text/javascript">
jQuery(document).ready(function($){
    "use strict";
    // add basic front-end ajax page scripts here
    $('#b60_custom_plugin_form').submit(function(event){
        event.preventDefault();
        // Vars
        var myInputFieldValue = $('#myInputField').val();
        // Ajaxify the Form
        var data = {
            'action': 'b60_custom_plugin_frontend_ajax',
            'myInputFieldValue':   myInputFieldValue,
        };
        
        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        var ajaxurl = "<?php echo admin_url('admin-ajax.php');?>";
        $.post(ajaxurl, data, function(response) {
                console.log(response);
                if(response.Status == true)
                {
                    console.log(response.message);
                    $('#b60_custom_plugin_form_wrap').html(response);

                }
                else
                {
                    console.log(response.message);
                    $('#b60_custom_plugin_form_wrap').html(response);
                }
        });
    });
}(jQuery));    
</script>
<?php }
add_action('wp_footer','b60_frontend_ajax_form_scripts');

/**
 * Available Schedule Times
 */

function get_appointment_time($schedule = '', $_24 = false)
{
    $interval = '+5 minutes';
    $output = array();

    if ($schedule == 'schedule') {
        $output['out'] = "Out";
    }

    $current = strtotime('00:00');
    $end     = strtotime('23:59');

    if ($_24) {
        $current = strtotime('00:05');
    }

    while ($current <= $end) {
        $time = date('H:i', $current);

        $output[$time] = date('g:i a', $current);
        $current = strtotime($interval, $current);
    }

    if ($_24) {
        $output['24:00'] = "12:00 am";
    }

    return $output;
}

/**
 * Service Capacity Options
 */
function services_capacity_options()
{
    $capacity = array();

    foreach (range(1, 500) as $num) {
        $capacity[$num] = $num;
    }

    return $capacity;
}