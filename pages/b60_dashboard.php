<?php


?>
<div class="wrap">
	<h2> <?php echo __( 'Dashboard', 'bookin60' ); ?> </h2>
</div>

<div class='fb-main'></div>

<script>
    jQuery(function(){

      //var data = JSON.parse('<?php echo stripslashes( get_option( 'b60_formbuilder' ) ); ?>');
      

      var data;

      <?php 
          if( get_option( 'b60_formbuilder' ) ) { ?>
            data = <?php echo stripslashes( get_option( 'b60_formbuilder' ) ); ?>;
      <?php  } else { ?>
            data = [{
            "label": "Step 1",
            "field_type": "section_break"
          },
          {
            "label": "Step 2",
            "field_type": "section_break"
          },
          {
            "label": "Step 3",
            "field_type": "section_break"
          },
          {
            "label": "Step 4",
            "field_type": "section_break"
          },
          {
            "label": "Step 5",
            "field_type": "section_break"
          }];
      <?php    }      ?>

      fb = new Formbuilder({
        selector: '.fb-main',
        bootstrapData: data
        // bootstrapData: [
        //   {
        //     "label": "Do you have a website?",
        //     "field_type": "website",
        //     "required": false,
        //     "cid": "c1"
        //   },
        //   {
        //     "label": "Please enter your clearance number",
        //     "field_type": "text",
        //     "required": true,
        //     "size": "large",
        //     "min_max_length_units": "characters",
        //     "cid": "c6"
        //   },
        //   {
        //     "label": "Radio Buttons",
        //     "field_type": "radio",
        //     "required": true,
        //     "options": [{
        //             "label": "Yes",
        //             "checked": true
        //         }, {
        //             "label": "No",
        //             "checked": false
        //         }],
        //     "include_other_option": true,            
        //     "cid": "c10"
        //   }]
      });

      fb.on('save', function(payload){
        console.log(payload);
      })
    });
  </script>