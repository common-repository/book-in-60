<br><br><div class='fb-main'></div>

<script>
    jQuery(function(){

      var arr_service = new Array();
      var arr_frequency = new Array();
      var arr_addon = new Array();
      var arr_pricing = new Array();
      var default_data, saved_data;

      jQuery.ajax({
         type: "POST",
         dataType: "json",
         url: fb_ajaxurl.admin_ajaxurl,
         data: {'action': 'get_post_types'},
         success: function( response ) { 
           jQuery.each( response, function( key, value ) {
              if(value.post_type === 'service'){
                arr_service.push({
                  "id": value.ID,
                  "label": value.post_title,
                  "name": value.post_name,
                  "type": value.post_type
                });
              }
              if(value.post_type === 'service_frequencies'){
                arr_frequency.push({
                  "id": value.ID,
                  "label": value.post_title,
                  "name": value.post_name,
                  "type": value.post_type
                });
              }
              if(value.post_type === 'service_addons'){
                arr_addon.push({
                  "id": value.ID,
                  "label": value.post_title,
                  "name": value.post_name,
                  "type": value.post_type
                });
              }
              if(value.post_type === 'pricing_parameters'){
                arr_pricing.push({
                  "id": value.ID,
                  "label": value.post_title,
                  "name": value.post_name,
                  "type": value.post_type
                });               
              }
            });

           //console.log(arr_service);

           <?php 
              if( get_option( 'b60_booking_formbuilder' ) ) { ?>
                 saved_data = <?php echo stripslashes(get_option( 'b60_booking_formbuilder' )); ?>;
                 

                 jQuery.each( saved_data, function( key, value ) {
                     if(value.field_type === 'sd_service') {
                       value['services'] = arr_service;
                     }
                 });

                 default_data = saved_data;

                 console.log(default_data);
           <?php 
         } else { ?>
              default_data = [{
                 "label": "Step 1",
                 "step": "step_1",
                 "field_type": "section_break"
               },
               {
                 "label":"Some info about your home","description":"","field_type":"sd_service","required":true,"Formbuilder":{"options":{"mappings":{}}},"services":arr_service,"Formbuilder":{"options":{"mappings":{}}},"pricing":arr_pricing,"cid":"c34"
               },
               {
                 "label": "Step 2",
                 "step": "step_2",
                 "field_type": "section_break"
               },
               {
                 "label":"Select Extras","description":"","field_type":"sd_addon","required":true,"Formbuilder":{"options":{"mappings":{}}},"addons":arr_addon,"cid":"c34"
               },               
               {
                 "label": "Step 3",
                 "step": "step_3",
                 "field_type": "section_break"
               },
               {
                 "label":"How often would you like service?","description":"","field_type":"sd_frequency","required":true,"Formbuilder":{"options":{"mappings":{}}},"frequencies":arr_frequency,"cid":"c34"
               },
               {
                 "label": "Step 4",
                 "step": "step_4",
                 "field_type": "section_break"
               },
               {
                 "label":"When would you like us to come?","field_type":"sd_calendar","required":true,"Formbuilder":{"options":{"mappings":{}}},"cid":"c34"
               },
               {
                 "label": "Step 5",
                 "step": "step_5",
                 "field_type": "section_break"
               },
               {
                 "label":"Who you are",
                 "field_type":"sd_customer_info",
                 "required":true,
                 "Formbuilder":{"options":{"mappings":{}}},"cid":"c48"
               },
               {
                 "label":"Your Address",
                 "field_type":"sd_address",
                 "required":true,"Formbuilder":{"options":{"mappings":{}}},"cid":"c42"
               },
               {
                 "label":"Discount code",
                 "field_type":"sd_discount",
                 "required":false,
                 "Formbuilder":{"options":{"mappings":{}}},"cid":"c49"
               }];

               jQuery.ajax({
                  type: "POST",
                  dataType: "json",
                  url: fb_ajaxurl.admin_ajaxurl,
                  data: {'action': 'save_booking_formbuilder', 'data': JSON.stringify(default_data)},
                  success: function( response ) { 
                      console.log(response);
                  }
              });
               
           <?php  
              
            }    
              
            ?>

           //console.log(default_data);

           fb = new Formbuilder({
             selector: '.fb-main',
             bootstrapData: default_data
           });

           fb.on('save', function(payload){
             console.log(payload);
           })
         },
         error: function( error ) {
           alert( error );
         }
      });  
    });
  </script>