<br><br><div class='fb-main'></div>

<script>
    jQuery(function(){      

      var data;

      <?php 
          if( get_option( 'b60_lead_formbuilder' ) ) { ?>
            data = <?php echo stripslashes( get_option( 'b60_lead_formbuilder' ) ); ?>;
      <?php  } else { ?>
            data = [{
              "label": "First Name",
              "field_type": "text",
              "required": true,
              "size": "large",
              "min_max_length_units": "characters",
              "cid": "c1"
            },{
              "label": "Last Name",
              "field_type": "text",
              "required": true,
              "size": "large",
              "min_max_length_units": "characters",
              "cid": "c2"
            },{
              "label": "Email",
              "field_type": "text",
              "required": true,
              "size": "large",
              "min_max_length_units": "characters",
              "cid": "c6"
            },{
              "label": "Phone",
              "field_type": "text",
              "required": true,
              "size": "large",
              "min_max_length_units": "characters",
              "cid": "c10"
            },];
      <?php    

    $data = '[{\"label\":\"First Name\",\"field_type\":\"text\",\"required\":true,\"size\":\"large\",\"min_max_length_units\":\"characters\",\"cid\":\"c1\"},{\"label\":\"Last Name\",\"field_type\":\"text\",\"required\":true,\"size\":\"large\",\"min_max_length_units\":\"characters\",\"cid\":\"c2\"},{\"label\":\"Email\",\"field_type\":\"text\",\"required\":true,\"size\":\"large\",\"min_max_length_units\":\"characters\",\"cid\":\"c6\"},{\"label\":\"Phone\",\"field_type\":\"text\",\"required\":true,\"size\":\"large\",\"min_max_length_units\":\"characters\",\"cid\":\"c10\"}]';
       
       update_option( 'b60_lead_formbuilder', $data );  

     }      ?>

      fb = new Formbuilder({
        selector: '.fb-main',
        bootstrapData: data
      });

      fb.on('save', function(payload){
        console.log(payload);
      })
    });
  </script>