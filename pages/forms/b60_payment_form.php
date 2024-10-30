<?php

/** @var stdClass $paymentForm */

$formNameAsIdentifier = esc_attr( $paymentForm->name );

$this->db     = new B60_Database();

$options = get_option( 'b60_api_keys' );

if($options['enable_help_link'] !== '') {

  $show_help = 'style="display:block"';

  $paymentForm = $this->db->get_payment_form_by_name( $formNameAsIdentifier );

  $redirectToPageOrPostHelp = $paymentForm->redirectToPageOrPostHelp;
  $redirectPostIDHelp       = $paymentForm->redirectPostIDHelp;
  $redirectUrlHelp          = $paymentForm->redirectUrlHelp;

  if ( $redirectToPageOrPostHelp == 1 ) {
    if ( $redirectPostIDHelp != 0 ) {
      $redirectURL = get_page_link( $redirectPostIDHelp );
    }
  } else {
    $redirectURL = $redirectUrlHelp;
  }   
} else {
  $show_help = 'style="display:none"';  
}

?>
<script type="text/javascript">

jQuery(document).ready(function($) {

   var lead_form_ = '<?php echo wp_kses_post( stripslashes( get_option('b60_lead_formbuilder') ) );  ?>';
   var lead_form = $.parseJSON(lead_form_);
   var booking_data = '<?php echo wp_kses_post( stripslashes( get_option('b60_booking_formbuilder') ) );  ?>';
   var booking_form = $.parseJSON(booking_data);
   var booking_form_new = new Array();
   var arr_service = new Array();
   var arr_frequency = new Array();
   var arr_addon = new Array();
   var arr_pricing = new Array();
   var default_data, saved_data;
   var step_index = new Array();
   var step_1 = new Array();
   var step_2 = new Array();
   var step_3 = new Array();
   var step_4 = new Array();
   var step_5 = new Array();
   var required = '';

   if(localStorage.getItem('customer_info') != null) {
       var customer_info = JSON.parse(localStorage.getItem('customer_info'));
       var customer_id = customer_info['id'];
   } else {
       var customer_id = '';
   } 

   localStorage.setItem('discount_type', '');
   localStorage.setItem('discount_type_value','');

   for(var i=0; i<lead_form.length;i++) {
      if (lead_form[i].field_type == "text") {
        if(lead_form[i].required == true) {
          required = ' ' + 'required';
        } else {
          required = '';
        }

        jQuery("<input>").attr({type:'text', name:(lead_form[i].label).toLowerCase().replace(/ /g, "_"), class:(lead_form[i].label).toLowerCase().replace(/ /g, "_") +' '+ (lead_form[i].label).toLowerCase().replace(/ /g, "_") +'-crm ' +required, id:(lead_form[i].label).toLowerCase().replace(/ /g, "_"), 'data-bind':(lead_form[i].label).toLowerCase().replace(/ /g, "_"), placeholder:lead_form[i].label}).appendTo(jQuery('#lead-form-services-container'));
      } 
   }   

   jQuery.each( booking_form, function( key, value ) {
      if(value.required == true) {
        required = ' ' + 'required';
      } else {
        required = '';
      }
      if(value.field_type === 'sd_service'){
        jQuery('#lead-form-services-container').append(`<select name="booking_service_lead" id="booking_service_lead" class="booking-service-lead field-select`+required+`"><option value="-1">Select Service</option></select>`);
      } 

      if(value.field_type === 'sd_frequency'){
        jQuery('#lead-form-frequency-container').append(`<select name="frequency_lead" id="frequency_lead" class="frequency-lead field-select`+required+`"><option value="-1">Select Frequency</option></select>`);
      } 
   });     

   if(localStorage.getItem('customer_info') == null) {

      setTimeout(function () {
        var customer_info = {}

        customer_info['first_name'] = jQuery('#first_name').val();
        customer_info['last_name'] = jQuery('#last_name').val();
        customer_info['email'] = jQuery('#email').val();
        customer_info['phone'] = jQuery('#phone').val();

        localStorage.setItem('customer_info', JSON.stringify(customer_info));
      }, 1000);  

   } else {

      var obj = JSON.parse(localStorage.getItem('customer_info'));       

      jQuery.each(obj, function(key, value){
          first_name = obj['first_name'];
          last_name = obj['last_name'];
          email = obj['email'];
          phone = obj['phone'];            
          address = obj['address'];
          apartment = obj['apartment'];            
          city = obj['city'];
          state = obj['state'];
          zip = obj['zip'];
      });  

      //console.log(address);

      var unformattedMask = Inputmask.unmask(phone, { mask: "(999)-99999-99" });

      setTimeout(function () {
        jQuery('.first_name').val(first_name);
        jQuery('.last_name').val(last_name);
        jQuery('.email').val(email);                                   
        jQuery('.phone').val(phone);                    
        jQuery('#unmask_phone').val(unformattedMask);                    
        jQuery('.address').val(address);                    
        jQuery('.apartment').val(apartment);                    
        jQuery('.city').val(city);                    
        jQuery('.state').val(state);                    
        jQuery('.zip').val(zip);                    
        jQuery('#b60_name').val(first_name +' '+last_name);
        //console.log(unformattedMask);
      }, 1000);   
   }

  

  jQuery.each( booking_form, function( key, value ) {
      if((value.step === 'step_1') || (value.step === 'step_2') || (value.step === 'step_3') || (value.step === 'step_4') || (value.step === 'step_5')) {
        step_index.push({
          "key": key,
          "value": value
        });
      }
  });


  jQuery.each( booking_form, function( key, value ) {
      if((key > step_index[0].key) && (key < step_index[1].key)){
        step_1.push(value);
      } else if((key > step_index[1].key) && (key < step_index[2].key)){
        step_2.push(value);
      } else if((key > step_index[2].key) && (key < step_index[3].key)){
        step_3.push(value);
      } else if((key > step_index[3].key) && (key < step_index[4].key)){
        step_4.push(value);
      } else if((key > step_index[4].key)){
        step_5.push(value);
      } 
  });

  jQuery.ajax({
     type: "POST",
     dataType: "json",
     url: ajaxurl.admin_ajaxurl,
     data: {'action': 'get_post_types'},
     success: function( response ) { 

       jQuery.each( response, function( key, value ) {
          if(value.post_type === 'service'){
            arr_service.push(value);
          }  
          if(value.post_type === 'service_frequencies'){
            arr_frequency.push(value);
          }
       });

       jQuery.each( step_1, function( key, value ) {           
          field_type_check(value, '#step-1', arr_service, arr_frequency, 1);
       });

       jQuery.each( step_2, function( key, value ) {
          field_type_check(value, '#step-2', arr_service, arr_frequency, 1);          
       });

       jQuery.each( step_3, function( key, value ) {
          field_type_check(value, '#step-3', arr_service, arr_frequency, 1);          
       });

       //jQuery("<button>").attr({type:'submit'}).text('Book Now').appendTo(jQuery('#lead-form-services-container')); 

    }
  });

  jQuery.each( step_4, function( key, value ) {
     field_type_check(value, '#step-4', '', '', 1);    
  });

  jQuery.each( step_5, function( key, value ) {
     field_type_check(value, '#step-5', '', '', 1);          
  });

});

function field_type_check(sd, step, service, frequency, required) {

   var label, description;
   var subtotal = 0;
   var subtotalArray = {}
   var isRequired = required;
   var selected_ = getCachedType('booking_service');
   var select_option = '';
   var checkRequired = 0;
   var required = '';

   getCachedAmount();

   if(sd.label !== undefined) {
      label = `<span class="booking-title">`+sd.label+`</span>`;
   } else {
      label = '';
   }

   if(sd.description !== undefined) {
      description = `<span class="booking-subtitle">`+sd.description+`</span>`;
   } else {
      description = '';
   }

   if(sd.required === true) {
      required = ' ' + 'required';      
    } else {
      required = '';
    }

    if(sd.field_type === 'sd_service'){

      jQuery(step).append(`<span class="booking-title">`+sd.label+`</span>
          <span class="booking-subtitle">`+sd.description+`</span>
          <div class="input_container input_container_radio booking-service">
            <ul class="main form-container ">
              <li id="booking-service">
                <div class="container_select">
                  <select name="booking_service" id="booking_service" class="field-select`+required+`">
                    <option value="-1">Select Option</option>
                  </select>
                </div>
              </li>
            </ul>
          </div>`);

      jQuery.each(service, function( key, value ) {
         if(selected_ == value.id) {
             select_option = 'selected';
         } else {
             select_option = ''
         }

         jQuery('#booking_service').append(`<option data-price="`+value.service_price+`" value="`+value.id+`" `+select_option+`>`+value.post_title+`</option>`);

         jQuery('#booking_service_lead').append(`<option data-price="`+value.service_price+`" value="`+value.id+`" `+select_option+`>`+value.post_title+`</option>`);

      });     

      pricing_parameters(selected_, service, subtotalArray, required); 

      if(localStorage.getItem('step_1') != null) {
         jQuery('#step-1-text').html(localStorage.getItem('step_1'));
      }      

      if(localStorage.getItem('_cart_type') == null){
        jQuery('.right').css('display', 'none'); 
      }

      if(localStorage.getItem('date') == null){
        jQuery('#date-list').css('display', 'none'); 
      } else {
        jQuery('#date-list').css('display', 'block');

        setTimeout(function () {
           jQuery('#cal-single').val(localStorage.getItem('date'));
        }, 1000);
      }

      if(localStorage.getItem('time') == null){
        jQuery('#time-list').css('display', 'none'); 
      } else {
        jQuery('#time-list').css('display', 'block'); 
        jQuery('#single-time-slots').append(`<option value="`+localStorage.getItem('time')+`">`+localStorage.getItem('time')+`</option>`);
      }

      if(localStorage.getItem('step_3') == null){
        jQuery('#frequency-list').css('display', 'none'); 
      } else {
        jQuery('#frequency-list').css('display', 'block'); 
      }

      jQuery('#load1').click(function () { 
       var CouponVal= jQuery('#coupon_code').val();
       var formSelector = '#payment-form';
       var $form = jQuery(formSelector);

       if(CouponVal != '') {
          jQuery.ajax({
               type: "POST",
               dataType: "json",
               url: ajaxurl.admin_ajaxurl,
               data: {'action': 'check_coupon_code','couponcode':CouponVal},
               success: function( response ) {
                 
                 var CouponType = response.discount_type;
                 var discountCodeID = response.id;

                 //console.log(discountCodeID);
                 if(CouponType === 'booking') {
                   if(response.status=='ok'){
                       localStorage.setItem('discount_type', response.type);
                       localStorage.setItem('discount_type_value', response.value);
                       localStorage.setItem('discount_redeemed', response.redeemed);
                       localStorage.setItem('discount_balance', response.balance);
                       $form.append("<input type='hidden' name='discountCodeID' value='" + discountCodeID + "' />");
                       getCachedAmount();
                   } else {
                       alert(response.status);
                       jQuery('#coupon_code').val('');
                       localStorage.setItem('discount_type', '');
                       localStorage.setItem('discount_type_value','');
                       localStorage.setItem('discount_redeemed', '');
                       localStorage.setItem('discount_balance', '');
                       getCachedAmount();
                   }
                 } else {
                      alert('Invalid Coupon may have reached usage limit or expired.');
                 }                 
               }
           });  
       } else {
          jQuery('.coupon-discount-text').css('display','none');
          jQuery('.coupon-discount-value').html(''); 
       }
              
      });

      jQuery('#booking_service, #booking_service_lead').change(function(){    

          var obj = {}

           obj['booking_service'] = jQuery(this).val();    

           localStorage.setItem('_cart_type', JSON.stringify(obj));

           localStorage.setItem('step_1', jQuery('option:selected', this).text());
          
           jQuery('#step-1-text').html(jQuery('option:selected', this).text());

          var id = jQuery(this).val();

          if(id == -1) {
               jQuery('.option').remove();
               jQuery('.service-addons').remove();
               localStorage.removeItem('_cart_type');
               localStorage.removeItem('exclude_addon_frequency');
               localStorage.removeItem('exclude_addon_discounts');
               jQuery('#subtotal').html('0.00');
               jQuery('#step-1-text').html('---');
               jQuery('#step-2-text').html('---');
               jQuery('#step-4-text').html('---');
               jQuery('#step-5-text').html('---');
               jQuery('#services-list').css('display', 'none'); 
               jQuery('.right').css('display', 'none'); 
          } else {
               jQuery('.service-addons').remove();       
               localStorage.removeItem('exclude_addon_frequency');
               localStorage.removeItem('exclude_addon_discounts');        
               getCachedAmount();
               pricing_parameters(id, service, subtotalArray, required);
               service_addons(id, service, subtotalArray, required);

               jQuery('.items-checkout li div').remove();

               jQuery('.items-checkout li#services-list').append(`<div id="services-checkout"><img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/house.png' ); ?>"><span class="booking-summary-left" id="service-checkout-text"></span><span class="booking-summary-right" id="summary-checkout-amount"></span>
                           </div>`);  

               jQuery('#services-list').css('display', 'block'); 
               jQuery('.right').css('display', 'block'); 

               if(localStorage.getItem('frequency') != null){
                  var obj = JSON.parse(localStorage.getItem('_cart_type'));
                  var frequency = JSON.parse(localStorage.getItem('frequency'));

                  if(localStorage.getItem('frequency_type') == 'amount_d'){
                      obj['frequency_radio'] = frequency['selected-frequency-price'];
                  } else {
                      obj['frequency_radio'] = parseFloat(frequency['selected-frequency-price'] / 100);
                  }

                  localStorage.setItem('_cart_type', JSON.stringify(obj));
                  localStorage.removeItem('frequency');                  
               }

               getCachedAmount();

               localStorage.removeItem('step_2');
               localStorage.removeItem('step_4');
               //localStorage.removeItem('date');
               //localStorage.removeItem('time');
          }   
                   
      });       
    }

    if(sd.field_type === 'sd_addon'){

      jQuery(step).append(`                 
           <ul class="input_container form-container">                     
                         <li id="row-6" class="row-6">`+label+description+`
                            <div class="extras-box input_container_radio">
                               <ul class="addon-services-checkbox" id="addon-services-checkbox"></ul>
                            </div>
                         </li>
                         <li id="no-refrigerator" class="field left_half">
                            <div class="container_select">
                               <label><strong>No. of Refrigerator</strong></label>
                               <select name="no_oven" id="no_oven" class="field-select_">
                                  <option value="1">1</option>
                                  <option value="1">2</option>
                                  <option value="1">3</option>
                                  <option value="1">4</option>
                                  <option value="1">5</option>
                                  <option value="1">6</option>
                                  <option value="1">7</option>
                                  <option value="1">8</option>
                                  <option value="1">9</option>
                                  <option value="1">10</option>
                               </select>
                            </div>
                         </li>       
                         <li id="no-oven" class="field left_half">
                            <div class="container_select">
                               <label><strong>No. of Oven</strong></label>
                               <select name="no_oven" id="no_oven" class="field-select_">
                                  <option value="1">1</option>
                                  <option value="1">2</option>
                                  <option value="1">3</option>
                                  <option value="1">4</option>
                                  <option value="1">5</option>
                                  <option value="1">6</option>
                                  <option value="1">7</option>
                                  <option value="1">8</option>
                                  <option value="1">9</option>
                                  <option value="1">10</option>
                               </select>
                            </div>
                         </li>  
                     </ul>`);

      service_addons(selected_, service, subtotalArray, required);
    }

    if(sd.field_type === 'sd_frequency'){

      jQuery(step).append(label+description+`
                    <div class="input_container input_container_radio">
                       <ul class="field_radio service_frequencies" id="how-often"></ul>
                    </div>`);

      service_frequencies('', frequency, subtotalArray, required)
    }

    if(sd.field_type === 'sd_calendar'){

      if(localStorage.getItem('step_4') != null) {
         jQuery('#step-4-text').html(localStorage.getItem('step_4'));
      }  

      if(localStorage.getItem('date') != null){
        jQuery('#date-checkout').html(localStorage.getItem('date'));
      } 
      if(localStorage.getItem('time') != null){
        jQuery('#time-checkout').html(localStorage.getItem('time'));
      }

      jQuery(step).append(label+description+`
        <div class="input_container single-cal-time-container"><div class="cal-single-page"><input type='text' id="cal-single" class='datepicker-here cal-single required' data-language='en'/></div><div class="time-single-page"><select id="single-time-slots" class="required"></select></div></div>`);            
    }


    if(sd.field_type === 'sd_customer_info'){

      
      var fname, last_name, email, phone;

      jQuery(step).append(label+description+`
               <div class="input_container address-form">           
                  <ul class="form-container" id="service-type">
                        <li class="field left_half">
                            <div class="field">
                                <input name="first_name" id="first_name" type="text" data-bind="first_name" class="medium field-select`+required+`" placeholder="First Name">
                            </div>
                        </li>   
                        <li class="field right_half">
                            <div class="field">
                                <input name="last_name" id="last_name" type="text" data-bind="last_name" class="medium field-select`+required+`" placeholder="Last Name">
                            </div>
                        </li>
                        <li class="field left_half">
                            <div class="field">
                                <input name="email" id="email" type="email" data-bind="email" class="email medium field-select`+required+`" placeholder="Email">
                            </div>
                        </li>   
                        <li class="field right_half">
                            <div class="field">
                                <input name="phone" id="phone" class="phone" type="text" data-bind="phone" class="medium field-select`+required+`" placeholder="Phone">
                                <input name="unmask_phone" id="unmask_phone" type="hidden" class="unmask_phone">
                            </div>
                        </li>
                  </ul>
               </div>`);
    }

    if(sd.field_type === 'sd_address'){

      jQuery(step).append(label+description+`
               <div class="input_container address-form">           
                  <ul class="form-container" id="service-type">
                        <li id="" class="field left_half">
                            <div class="field">
                                <input name="address" id="address" data-bind="address" type="text" value="" class="address medium field-select`+required+`" placeholder="Address">
                            </div>
                        </li>   
                        <li id="" class="field right_half">
                            <div class="field">
                                <input name="apartment" id="apartment" data-bind="apartment" type="text" value="" class="apartment medium" placeholder="Apt/Suite#">
                            </div>
                        </li>
                        <li id="" class="field left_3">
                            <div class="field">
                                <input name="city" id="city" type="city" data-bind="city" value="" class="city medium field-select`+required+`" placeholder="City">
                            </div>
                        </li>   
                        <li id="" class="field middle_3">
                            <div class="field">
                                <select name="state" id="state" data-bind="state" class="state medium field-select field_select`+required+`"><option value="" selected="selected" class="placeholder">State</option><option value="AK">AK</option><option value="AL">AL</option><option value="AR">AR</option><option value="AZ">AZ</option><option value="CA">CA</option><option value="CO">CO</option><option value="CT">CT</option><option value="DC">DC</option><option value="DE">DE</option><option value="FL">FL</option><option value="GA">GA</option><option value="HI">HI</option><option value="IA">IA</option><option value="ID">ID</option><option value="IL">IL</option><option value="IN">IN</option><option value="KS">KS</option><option value="KY">KY</option><option value="LA">LA</option><option value="MA">MA</option><option value="MD">MD</option><option value="ME">ME</option><option value="MI">MI</option><option value="MN">MN</option><option value="MO">MO</option><option value="MS">MS</option><option value="NC">NC</option><option value="ND">ND</option><option value="NE">NE</option><option value="NH">NH</option><option value="NJ">NJ</option><option value="NM">NM</option><option value="NY">NY</option><option value="OH">OH</option><option value="OK">OK</option><option value="OR">OR</option><option value="PA">PA</option><option value="RI">RI</option><option value="SC">SC</option><option value="SD">SD</option><option value="TN">TN</option><option value="TX">TX</option><option value="UT">UT</option><option value="VA">VA</option><option value="VT">VT</option><option value="WA">WA</option><option value="WI">WI</option><option value="WV">WV</option><option value="WY">WY</option></select>
                            </div>
                        </li>
                        <li id="" class="field right_3">
                            <div class="field">
                                <input name="zip" id="zip" data-bind="zip" type="text" value="" class="zip medium field-select`+required+`" placeholder="Zip">
                            </div>
                        </li>   
                  </ul>
               </div>`);
    }

    // if(sd.field_type === 'sd_discount'){

    //   jQuery(step).append(label+description+`
    //     <div class="input_container coupon-container" id="coupons_container">
    //               <input placeholder="Discount code (or leave this blank)" id="coupon_code" name="coupon_code" class="coupon_code" type="text">
    //               <button type="button" class="coupon_apply btn-primary btn-lg " id="load1" data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Applying">Apply Coupon</button>
    //            </div>`);
    // }

}

function pricing_parameters(id, arr_service, subtotalArray, required) { 

    var isRequired = required;

    jQuery.each(arr_service, function( key, value ) {

        //console.log(value);
      
        if((value.id == id) || (value.id == localStorage.getItem('booking_service'))){
           jQuery('.option').remove();

           jQuery.each(value.meta, function( key, value ) {

              var range_from = value.range_from;
              var range_to = value.range_to;
              var cleaners_from = value.cleaners_from;
              var cleaners_to = value.cleaners_to;
              var hours_to = value.hours_to;
              var hours_from = value.hours_from;
              var _title = value.title;
              var lastChar = _title.slice(-1);
              var parameter_title = '';
              var final_title = '';
              var options = new Array();
              var options_ = new Array();
              var title = '';
              var select_option = '';          

              if(lastChar == 's') {
                parameter_title = _title;
              } else {
                parameter_title = _title + 's';
              }

              if (value.pricing_type == 'range_price') {

                jQuery.each(value.price, function( key, value_price ) {

                    if(key == getCachedType(value.slug)) {
                        select_option = 'selected';   
                    } else {
                        select_option = ''
                    }
                    options.push(`<option class="option" id="`+key+`" value="`+value_price.range_price+`" `+select_option+`>`+value_price.label+`</option>`);
                });

                jQuery('.booking-service').append(`<ul class="option form-container">
                   <li id="booking-pricing"><div class="container_select">
                       <select name="`+value.slug+`" id="`+value.slug+`" class="field-select`+required+`">
                        <option value="-1">Select Option</option>`+options+`
                       </select>
                     </div>
                   </li>
                 </ul>`);    

                 jQuery('#lead-form-services-container').append(`<select name="`+value.slug+`_lead" id="`+value.slug+`_lead" class="option booking-service-lead field-select`+required+`">
                         <option value="-1">Select Option</option>`+options+`</select>`);      

                 jQuery('#'+value.slug+'_lead').change(function(){   
                   var obj = JSON.parse(localStorage.getItem('_cart_type'));

                   obj[value.slug] = jQuery('option:selected', this).attr('id');                  
                   localStorage.setItem('_cart_type', JSON.stringify(obj));
                 });  

                jQuery('#'+value.slug).change(function(){   
                  var obj = JSON.parse(localStorage.getItem('_cart_type'));

                  obj[value.slug] = jQuery('option:selected', this).attr('id');                  
                  localStorage.setItem('_cart_type', JSON.stringify(obj));

                  getCachedAmount();

                  jQuery('#'+value.slug+'-checkout').remove();

                  jQuery('.items-checkout li:first').append(`<div id="`+value.slug+`-checkout"><i class="fa fa-check" style="width:11%;margin-left:10px; display: inline-block;text-align:center">
                                </i><span class="booking-summary-left">`+jQuery('#'+value.slug+ ' option[id='+obj[value.slug]+']').text()+`</span>
                                <span class="booking-summary-right">$`+parseFloat(jQuery('#'+value.slug+ ' option[id='+obj[value.slug]+']').val()).toFixed(2)+`</span></div>
                             `);  
                });         

            } else if (value.pricing_type == 'flat_price') {

                for(i=range_from;i<=range_to;i++) {

                  if (i == 1) {
                    if(lastChar == 's') {
                      final_title = _title.slice(0,-1);
                    }                                
                  } else {  
                    final_title = parameter_title;
                  }

                  if(getCachedType(value.slug) == i) {
                      select_option = 'selected';
                  } else {
                      select_option = ''
                  }

                  if(i == 0) {
                    options.push(`<option id="`+i+`" class="option" value="0" `+select_option+`>`+i+` `+final_title+`</option>`);
                    
                  } else {
                    options.push(`<option id="`+i+`" class="option" value="`+(i*value.price).toFixed(2)+`" `+select_option+`>`+i+` `+final_title+`</option>`);
                  }
                  
                }                          
                
                jQuery('.booking-service').append(`<ul class="option form-container">
                   <li id="booking-pricing"><div class="container_select">
                       <select name="`+value.slug+`" id="`+value.slug+`" class="field-select`+required+`">
                         <option value="-1">Select Option</option>`+options+`
                       </select>
                     </div>
                   </li>
                 </ul>`);

                jQuery('#lead-form-services-container').append(`<select name="`+value.slug+`_lead" id="`+value.slug+`_lead" class="option booking-service-lead field-select`+required+`">
                         <option value="-1">Select Option</option>`+options+`</select>`);

                jQuery('#'+value.slug+'-checkout').remove();

                jQuery('#'+value.slug+'_lead').change(function(){
                    var obj = JSON.parse(localStorage.getItem('_cart_type'));             

                    obj[value.slug] = jQuery('option:selected', this).attr('id');                    
                    localStorage.setItem('_cart_type', JSON.stringify(obj));                    
                });

                jQuery('#'+value.slug).change(function(){
                    var obj = JSON.parse(localStorage.getItem('_cart_type'));             

                    obj[value.slug] = jQuery('option:selected', this).attr('id');                    
                    localStorage.setItem('_cart_type', JSON.stringify(obj));

                    getCachedAmount();

                    jQuery('#'+value.slug+'-checkout').remove();

                    jQuery('.items-checkout li:first').append(`<div id="`+value.slug+`-checkout"><i class="fa fa-check" style="width: 11%;margin-left:10px; display: inline-block;text-align:center">
                                  </i><span class="booking-summary-left">`+jQuery('#'+value.slug+ ' option[id='+obj[value.slug]+']').text()+`</span>
                                  <span class="booking-summary-right">$`+parseFloat(jQuery('#'+value.slug+ ' option[id='+obj[value.slug]+']').val()).toFixed(2)+`</span></div>
                               `);  

                });
            } else {

                for(i=parseInt(cleaners_from);i<=parseInt(cleaners_to);i++) {

                  if (i == 1) {
                    final_title = 'Cleaner';                               
                  } else {  
                    final_title = 'Cleaners';
                  }

                  if(getCachedType('no-cleaners') == i) {
                      select_option = 'selected';
                  } else {
                      select_option = ''
                  }

                  if(i == 0) {
                    options.push(`<option id="`+i+`" class="option" value="0" `+select_option+`>`+i+` `+final_title+`</option>`);
                    
                  } else {
                    options.push(`<option id="`+i+`" class="option" value="`+(i*value.price).toFixed(2)+`" `+select_option+`>`+i+` `+final_title+`</option>`);
                  }
                  
                }

                //console.log(cleaners_from, cleaners_to);
                //console.log(hours_from, hours_to);

                for(i=parseInt(hours_from);i<=parseInt(hours_to);i++) {

                  if (i == 1) {
                    final_title = 'Hour';                               
                  } else {  
                    final_title = 'Hours';
                  }

                  if(getCachedType('no-hours') == i) {
                      select_option = 'selected';
                  } else {
                      select_option = ''
                  }

                  if(i == 0) {
                    options_.push(`<option id="`+i+`" class="option" value="0" `+select_option+`>`+i+` `+final_title+`</option>`);                  
                  } else {                  
                    options_.push(`<option id="`+i+`" class="option" value="`+i+`" `+select_option+`>`+i+` `+final_title+`</option>`);
                  }
                  
                }

                var obj = JSON.parse(localStorage.getItem('_cart_type'));
              
                jQuery('.booking-service').append(`<ul class="option form-container">
                   <li id="booking-pricing"><div class="container_select">
                       <select name="`+value.slug+`-cleaners" id="no-cleaners" class="field-select`+required+`">
                        <option value="-1">Select Option</option>`+options+`
                       </select>
                     </div>
                   </li>
                 </ul>`);

                jQuery('.booking-service').append(`<ul class="option form-container">
                   <li id="booking-pricing"><div class="container_select">
                       <select name="`+value.slug+`-hours" id="no-hours" class="field-select`+required+`"">
                          <option value="-1">Select Option</option>`+options_+`
                       </select>
                     </div>
                   </li>
                 </ul>`);    

                 jQuery('#lead-form-services-container').append(`<select name="`+value.slug+`-cleaners-lead" id="no-cleaners-lead" class="option booking-service-lead field-select`+required+`">
                         <option value="-1">Select Option</option>`+options+`</select>`); 

                 jQuery('#lead-form-services-container').append(`<select name="`+value.slug+`-hours-lead" id="no-hours-lead" class="option booking-service-lead field-select`+required+`"">
                          <option value="-1">Select Option</option>`+options_+`</select>`);

                 jQuery('#'+value.slug+'-checkout').remove();       

                jQuery('#no-cleaners, #no-cleaners-lead').change(function(){                                   

                    obj['no-cleaners'] = jQuery('option:selected', this).attr('id');                  
                    localStorage.setItem('_cart_type', JSON.stringify(obj)); 

                    var item = jQuery('#no-cleaners option[id='+obj['no-cleaners']+']').text();
                    var price = jQuery('#no-cleaners option[id='+obj['no-cleaners']+']').val();

                    if(required == 1) {
                       if(localStorage.getItem('_required') == null){
                          var required = {}
                       } else {
                          var required = JSON.parse(localStorage.getItem('_required'));
                       }
                       required = value.slug+'-cleaners';
                       localStorage.setItem('_required', JSON.stringify(required));
                    }

                    jQuery('#no-cleaners-checkout').remove();

                    jQuery('.items-checkout li:first').append(`<div id="no-cleaners-checkout"><i class="fa fa-check" style="width: 11%;margin-left:10px; display: inline-block;text-align:center">
                          </i><span class="booking-summary-left">`+jQuery('#no-cleaners option[id='+obj['no-cleaners']+']').text()+` x <span id='no-hours-checkout'>`+jQuery('#no-hours option[id='+obj['no-hours']+']').text()+`</span></span>
                          <span class="booking-summary-right" id="no-cleaners-amount">$`+(price*obj['no-hours']).toFixed(2)+`</span></div>
                       `);

                    getCachedAmount();
                });

                jQuery('#no-hours, #no-hours-lead').change(function(){        

                    obj['no-hours'] = jQuery(this).val();       
                    localStorage.setItem('_cart_type', JSON.stringify(obj));

                    var cleaners = jQuery('#no-cleaners option[id='+obj['no-cleaners']+']').val();
                    var hours = jQuery(this).val();

                    jQuery('#'+value.slug+'-checkout').remove();
                    jQuery('#no-hours-checkout').html(jQuery('#no-hours option[id='+jQuery(this).val()+']').text());
                    jQuery('#no-cleaners-amount').html('$'+(cleaners * hours).toFixed(2));

                    getCachedAmount();
                });              
            }
         });

          var services = JSON.parse(localStorage.getItem('_cart_type'));

          jQuery.each(services, function(key, value){
              var item = jQuery('#'+key+ ' option[id='+services[key]+']').text();
              var price = jQuery('#'+key+ ' option[id='+services[key]+']').val();

              jQuery('#no-hours-checkout').html(jQuery('#no-hours option[id='+services['no-hours']+']').text());

              if((key != "booking_service") && (key != "no-cleaners") && (key != "no-hours")) {
                  if(value != "checked") {
                    jQuery('.items-checkout li:first').append(`<div id="`+key+`-checkout"><i class="fa fa-check" style="width: 11%;margin-left:10px; display: inline-block;text-align:center">
                            </i><span class="booking-summary-left">`+item+`</span>
                            <span class="booking-summary-right">$`+parseFloat(price).toFixed(2)+`</span></div>
                         `);
                  }                
              } else if(key == "no-cleaners") {
                jQuery('.items-checkout li:first').append(`<div id="no-cleaners-checkout"><i class="fa fa-check" style="width: 11%;margin-left:10px; display: inline-block;text-align:center">
                          </i><span class="booking-summary-left">`+jQuery('#no-cleaners option[id='+services['no-cleaners']+']').text()+` x <span id='no-hours-checkout'>`+jQuery('#no-hours option[id='+services['no-hours']+']').text()+`</span></span>
                          <span class="booking-summary-right" id="no-cleaners-amount">$`+(price*services['no-hours']).toFixed(2)+`</span></div>
                       `);
              } else {
                // jQuery('.items-checkout li:first').append(`<div id="`+key+`-checkout"><i class="fa fa-check" style="width: 11%;margin-left:10px; display: inline-block;text-align:center">
                //           </i><span class="booking-summary-left">`+item+`</span>
                //           <span class="booking-summary-right">$`+parseFloat(price).toFixed(2)+`</span></div>
                //        `);
              }
          });
        }
    }); 
}

function service_addons(id, arr_service, subtotalArray, required) { 

  jQuery.each(arr_service, function( key, value ) {
    
      if((value.id == id) || (value.id == getCachedType('booking_service'))){  

        if((value.addons).length > 0) {
          jQuery('#row-step-2').css('display', 'block'); 
        } else {
          jQuery('#row-step-2').css('display', 'none'); 
        }

         jQuery.each(value.addons, function( key, value ) {

            var selected_ = getCachedType(value.slug);            

            if(selected_ == 'checked') {
                select_option = 'checked';
            } else {
                select_option = ''
            }

            jQuery('#addon-services-checkbox').append(`<style>
                   .addon-services-checkbox li#`+value.slug+`-img label:before {
                       content: '';
                       background: url(`+value.thumb+`);
                       background-repeat: no-repeat;
                       background-position: center;
                       border-radius: 5px;
                       display: flex;
                       align-items: center;
                       justify-content:center;
                       padding: 20px;
                       border: 1px solid #ccc;
                       margin-bottom: 15px;
                       min-height:115px;
                       cursor: pointer;
                   }
                 </style><li class="service-addons" id="`+value.slug+`-img">
              <input name="service-addons" data-bind="" type="checkbox" class="`+required+`" value="`+value.addon_price+`" id="`+value.slug+`" `+select_option+`>
              <label for="`+value.slug+`" id="`+value.slug+`">`+value.title+`</label>
            </li>`);     

            if(localStorage.getItem('step_2') != null) {
              var step_item = JSON.parse(localStorage.getItem('step_2'));
               jQuery('#step-2-text').html(step_item[Object.keys(step_item)[0]]);
            }

            if(value.addon_sales_tax !== '') {
              var tax_inc = ' <span class="badge-tax" data-exclude="'+value.addon_price+'" title="Price inclusive of '+value.addon_sales_tax+'% tax."><i class="fa-solid fa-percent"></i></span>';

              if(value.addon_exclude_from === 'checked'){
                var exclude_from_frequency = ' <span class="badge-exclude-frequency" id="badge-exclude-frequency" data-exclude="'+value.addon_price+'" title="Excluded from frequency discounts"><i class="fa-solid fa-arrow-down-short-wide"></i></span>';
              } else {
                var exclude_from_frequency = '';
              }

              if(value.addon_code_discounts === 'checked'){
                var exclude_from_discount = ' <span class="badge-exclude-discount" id="badge-exclude-discount" data-exclude="'+value.addon_price+'" title="Excluded from discount coupons"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>';
              } else {
                var exclude_from_discount = '';
              }       
              
              //var addon_price = (parseFloat(value.addon_price) + parseFloat(value.addon_price * (value.addon_sales_tax /100))).toFixed(2);
            } else {
              var tax_inc = '';
              var exclude_from_frequency = '';
              var exclude_from_discount = '';
              //var addon_price = value.addon_price;
            }

            if(jQuery('#'+value.slug).prop('checked') == true){
                jQuery('.items-checkout li:first').append(`<div id="`+value.slug+`-checkout"><i class="fa-solid fa-check" style="width: 11%;margin-left:10px; display: inline-block;text-align:center">
                          </i><div class="booking-summary-left" style="text-transform:capitalize">`+value.title+tax_inc+exclude_from_frequency+exclude_from_discount+`</div>
                          <div class="booking-summary-right">$`+(value.addon_price).toFixed(2)+`</div></div>
                       `); 
            }          

            jQuery("#"+value.slug).change(function() {
                if(this.checked) {                    
                    var obj = JSON.parse(localStorage.getItem('_cart_type'));

                    if(localStorage.getItem('exclude_addon_frequency') != null) {
                        var obj_addon_frequency = JSON.parse(localStorage.getItem('exclude_addon_frequency'));
                    } else {
                        var obj_addon_frequency = {}
                    } 

                    if(localStorage.getItem('exclude_addon_discounts') != null) {
                        var obj_addon_discounts = JSON.parse(localStorage.getItem('exclude_addon_discounts'));
                    } else {
                        var obj_addon_discounts = {}
                    }  

                    if(localStorage.getItem('step_2') != null) {
                        var step_item = JSON.parse(localStorage.getItem('step_2'));
                    } else {
                        var step_item = {}
                    }                    

                    jQuery('#step-2-text').html(value.title);

                    obj[value.slug] = 'checked';                    
                    step_item[value.slug] = value.title;

                    if(value.addon_exclude_from === 'checked'){
                      obj_addon_frequency[value.slug] = 'checked';
                    }

                    if(value.addon_code_discounts === 'checked'){
                      obj_addon_discounts[value.slug] = 'checked';
                    }
                    
                    localStorage.setItem('_cart_type', JSON.stringify(obj));
                    localStorage.setItem('exclude_addon_frequency', JSON.stringify(obj_addon_frequency));
                    localStorage.setItem('exclude_addon_discounts', JSON.stringify(obj_addon_discounts));
                    localStorage.setItem('step_2', JSON.stringify(step_item));

                    getCachedAmount();

                    jQuery('.items-checkout li:first').append(`<div id="`+value.slug+`-checkout"><i class="fa-solid fa-check" style="width: 11%;margin-left:10px; display: inline-block;text-align:center">
                              </i><span class="booking-summary-left" style="text-transform:capitalize">`+value.title+tax_inc+exclude_from_frequency+exclude_from_discount+`</span>
                              <span class="booking-summary-right">$`+(value.addon_price).toFixed(2)+`</span></div>
                           `); 
                } else {
                    var obj = JSON.parse(localStorage.getItem('_cart_type'));
                    var obj_addon_frequency = JSON.parse(localStorage.getItem('exclude_addon_frequency'));
                    var obj_addon_discounts = JSON.parse(localStorage.getItem('exclude_addon_discounts'));
                    var step_item = JSON.parse(localStorage.getItem('step_2'));

                    if((Object.keys(step_item).length-1) == 0) {
                         jQuery('#step-2-text').html('---');
                    } 
                    delete step_item[value.slug];

                    jQuery('#step-2-text').html(step_item[Object.keys(step_item)[0]]);       

                    delete obj[value.slug];
                    delete obj_addon_frequency[value.slug];
                    delete obj_addon_discounts[value.slug];
                    
                    localStorage.setItem('_cart_type', JSON.stringify(obj));
                    localStorage.setItem('exclude_addon_frequency', JSON.stringify(obj_addon_frequency));
                    localStorage.setItem('exclude_addon_discounts', JSON.stringify(obj_addon_discounts));
                    localStorage.setItem('step_2', JSON.stringify(step_item));      



                    getCachedAmount(); 
                    jQuery('#'+value.slug+'-checkout').remove();                
                }
            });
         });
      }
  });
}

function service_frequencies(id, arr_frequency, subtotalArray) { 

   var select_option = '';

   jQuery.each(arr_frequency, function( key, value ) {

      var obj_frequency = JSON.parse(localStorage.getItem('frequency_discount'));
      var selected;

      jQuery.each(obj_frequency, function(key, value_f){
          if(value_f == value.post_name) {
              select_option = 'selected';
          } else {
              select_option = ''
          }
      });  

      jQuery('#frequency_lead').append(`<option value="`+value.frequency_price+`" data-frequency-type="`+value.frequency_type+`" id="`+value.post_name+`" `+select_option+`>`+value.post_title+`</option>`);

      jQuery('.service_frequencies').append(`<li class=""><input class="frequency_radio" name="frequency_radio" type="radio" value="`+value.frequency_price+`" id="`+value.post_name+`" `+select_option+`><label for="`+value.post_name+`">`+value.post_title+`</label></li>`);

      if(localStorage.getItem('frequency_discount') !== null) {
        var frequency = JSON.parse(localStorage.getItem('frequency_discount'));
        var selected_ = frequency['selected-frequency'];

        if(selected_ == value.post_name) {
            jQuery("#"+value.post_name).prop("checked", true);
            jQuery('#step-3-text').html(jQuery("label[for="+value.post_name+"]").html());
            jQuery('#frequency-checkout').html(jQuery("label[for="+value.post_name+"]").html());            
        } 
      } else {
        if(value.default_frequency == 'checked'){
          var obj = {}
          var obj_addon_frequency = {}
          var frequency = {}

          jQuery("#"+value.post_name).prop("checked", true);

          jQuery('#step-3-text').html(jQuery("label[for="+value.post_name+"]").html());

          obj['frequency_radio'] = value.post_name;
          frequency['selected-frequency'] = value.post_name;

          localStorage.setItem('frequency_discount', JSON.stringify(frequency));
          localStorage.setItem('frequency_type', value.frequency_type);          
          localStorage.setItem('step_3', jQuery("label[for="+value.post_name+"]").html());

          jQuery('#frequency-checkout').html(jQuery("label[for="+value.post_name+"]").html());

          jQuery('#subtotal').html('0.00');
        }
      }

      if(localStorage.getItem('step_3') != null) {
         jQuery('#step-3-text').html(localStorage.getItem('step_3'));
      }            

      jQuery("#"+value.post_name).change(function() {

          var obj = JSON.parse(localStorage.getItem('frequency_discount'));
          var radioValue = jQuery("input[name='frequency_radio']:checked").val();           

          if(radioValue){
              jQuery('#step-3-text').html(value.post_title);

              obj['selected-frequency'] = value.post_name;

              localStorage.setItem('frequency_discount', JSON.stringify(obj));
              localStorage.setItem('frequency_type', value.frequency_type);
              localStorage.setItem('step_3', value.post_title);
              jQuery('#frequency-checkout').html(jQuery("label[for="+value.post_name+"]").html()); 

              getCachedAmount();
          }
      });
   }); 

   jQuery("#frequency_lead").change(function() {

       var obj = JSON.parse(localStorage.getItem('frequency_discount'));
       var selectId = jQuery('option:selected', this).attr('id');
       var selectValue = jQuery('option:selected', this).val();
       var selectFrequencyType = jQuery('option:selected', this).data('frequency-type');
       var selectText = jQuery('option:selected', this).text();

       if(selectValue){
           jQuery('#step-3-text').html(selectText);

           obj['selected-frequency'] = selectId;

           localStorage.setItem('frequency_discount', JSON.stringify(obj));
           localStorage.setItem('frequency_type', selectFrequencyType);
           localStorage.setItem('step_3', selectText);
           jQuery('#frequency-checkout').html(jQuery("label[for="+selectId+"]").html()); 

           getCachedAmount();
       }
   });

   getCachedAmount();     
}

function getCachedType(type){
  if(localStorage.getItem('_cart_type') !== null) {
    var obj = JSON.parse(localStorage.getItem('_cart_type'));
    var selected;

    jQuery.each(obj, function(key, value){
      if(key == type) {
        selected = value;
      }        
    });  
    return selected;
  }
}

function getCachedAmount(){

  if(localStorage.getItem('_cart_type') !== null) {

      var cart_total = JSON.parse(localStorage.getItem('_cart_type'));
      var frequency_discount = JSON.parse(localStorage.getItem('frequency_discount'));
      var DiscountType = localStorage.getItem('discount_type');
      var DiscountTypeValue = localStorage.getItem('discount_type_value');
      var DiscountRedeemed = localStorage.getItem('discount_redeemed');
      var DiscountBalance = localStorage.getItem('discount_balance');
      var subtotal = 0;
      var p = 1;
      var frequency_type;
      var discount = 0;
      var discount_ = 0;
      var sales_tax = 0;
      var sales_tax_applied = '<?php echo esc_attr( get_option( 'b60_settings_option' )['sales_tax_applied'] ) ?>';

      jQuery.each(cart_total, function(key, value){
        if(key == 'booking_service') {

          subtotal = parseFloat(jQuery("#booking_service option[value="+cart_total['booking_service']+"]").attr('data-price'));

        } else if((key == 'no-cleaners') || (key == 'no-hours')) {

          var hours = 0;
          if(cart_total['no-hours'] != null) {
            hours = parseFloat(cart_total['no-hours']);
          } 
          subtotal = parseFloat(jQuery('#no-cleaners').val()) * hours;

        } else {
          subtotal += parseFloat(jQuery("#"+key).val());
        }         
      });

      if(localStorage.getItem('frequency_type') == 'amount_d'){
          p = parseFloat(jQuery('input[name="frequency_radio"]:checked').val());
          frequency_type = 'amount_d';
      } else {
          p = parseFloat(jQuery('input[name="frequency_radio"]:checked').val() / 100);
          frequency_type = 'percentage_d';
      }

      if(localStorage.getItem('exclude_addon_frequency') !== null) {
        var obj = JSON.parse(localStorage.getItem('exclude_addon_frequency'));
        var exclude_addon_frequency = 0;
        jQuery.each(obj, function(key, value){
            exclude_addon_frequency += parseFloat(jQuery('#'+key).val());
        });  
      } else {
        var exclude_addon_frequency = 0;
      }

      if(localStorage.getItem('exclude_addon_discounts') !== null) {
        var obj = JSON.parse(localStorage.getItem('exclude_addon_discounts'));
        var exclude_addon_discounts = 0;
        jQuery.each(obj, function(key, value){
            exclude_addon_discounts += parseFloat(jQuery('#'+key).val());            
        });  
      } else {
        var exclude_addon_discounts = 0;
      }


      if('<?php echo esc_attr( get_option( 'b60_settings_option' )['enable_sales_tax'] ) ?>' != 0) { 
        if(sales_tax_applied  == 'sales_tax_applied_before') {
          sales_tax = (parseFloat(<?php echo esc_attr( get_option( 'b60_settings_option' )['sales_tax'] ) ?>) / 100) * subtotal;
          var booking_subtotal = parseFloat(subtotal);
          jQuery('.sales-tax').html('+ $'+sales_tax.toFixed(2));
          jQuery('.booking-subtotal').html('$'+booking_subtotal.toFixed(2));
          jQuery('#subtotal').html(subtotal.toFixed(2));
          jQuery('.actual-subtotal').html('$'+(subtotal).toFixed(2));   

          if(exclude_addon_frequency === undefined) {
            var subtotal_frequency = 0; 
          } else {
            var subtotal_frequency = subtotal - parseFloat(exclude_addon_frequency);
          }

          if(exclude_addon_discounts === undefined) {
            var subtotal_discounts = 0; 
          } else {
            var subtotal_discounts = subtotal - parseFloat(exclude_addon_discounts);
          }        

          if(p != 1) {  
            if((subtotal_frequency != 0) || (subtotal_discounts != 0)) {
              if(frequency_type == 'amount_d') {
                  discount = p;
                  discount_ = p;
                  console.log('amount_d: ', p);
              } else {                  
                  discount = (subtotal_frequency  + sales_tax) * p;
                  discount_ = (subtotal_discounts  + sales_tax) * p;
              }
            } else {
                
                if(frequency_type == 'amount_d') {
                    discount = p;
                    discount_ = p;
                } else {                  
                    discount = (subtotal + sales_tax) * p;
                    discount_ = (subtotal + sales_tax) * p;
                }
            }
              
              jQuery('.discount-value').html('- $'+discount.toFixed(2));  
              subtotal = subtotal;          
          } else {
              subtotal = subtotal * p;
          }    

          if(DiscountType!='' && DiscountTypeValue!=''){                      
                    if (DiscountType == 'percentage') {
                        var withoutdiscount = subtotal - parseFloat(exclude_addon_discounts);
                        var DiscountValue = (withoutdiscount * DiscountTypeValue)/100;
                        var FinalafterDiscount = subtotal - discount - DiscountValue;   
                        jQuery('.coupon-discount-text').css('display','block');
                        jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2));                    
                    } else if (DiscountType == 'flat') {
                        var withoutdiscount = subtotal - discount ;
                        var DiscountValue = parseFloat(DiscountTypeValue);
                        var FinalafterDiscount = withoutdiscount - DiscountTypeValue;
                        jQuery('.coupon-discount-text').css('display','block');
                        jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2));
                    } else {
                        if (DiscountBalance != 'null') {
                          if (DiscountBalance == 0) {
                            alert('Discount coupon can not be applied to this booking. You have $'+parseFloat(DiscountBalance).toFixed(2)+' balance.');
                            jQuery('.coupon-discount-text').css('display','none');
                            jQuery('.coupon-discount-value').html(''); 
                            var withoutdiscount = subtotal;
                            var DiscountValue = parseFloat(DiscountBalance);
                            var FinalafterDiscount = withoutdiscount - discount;
                          } else {
                            if (DiscountBalance > ((subtotal + sales_tax) - discount)) {
                                var withoutdiscount = subtotal - discount ;
                                var DiscountValue = parseFloat(withoutdiscount) +  sales_tax;
                                var FinalafterDiscount = subtotal - (withoutdiscount + discount) - sales_tax;
                            } else {
                                var withoutdiscount = subtotal - discount;
                                var DiscountValue = parseFloat(DiscountBalance);
                                var FinalafterDiscount = withoutdiscount - DiscountBalance;
                            }  
                            jQuery('.coupon-discount-text').css('display','block');
                            jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2));    
                          }
                        } else {
                          if (DiscountTypeValue > (subtotal - discount)) {
                              var withoutdiscount = subtotal - discount;
                              var DiscountValue = parseFloat(withoutdiscount) + sales_tax;
                              var FinalafterDiscount = subtotal - (withoutdiscount + discount) - sales_tax;
                          } else {
                              var withoutdiscount = subtotal - discount;
                              var DiscountValue = parseFloat(DiscountTypeValue);
                              var FinalafterDiscount = withoutdiscount - DiscountTypeValue;
                          }

                          jQuery('.coupon-discount-text').css('display','block');
                          jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2)); 
                        }
                    }

                    if((FinalafterDiscount+sales_tax) === 0) {
                      jQuery('#cc-form').css('display', 'none');
                    } else {
                      jQuery('#cc-form').css('display', 'block');
                    }

                    jQuery('.sales-tax').html('+ $'+sales_tax.toFixed(2));
                    jQuery('.recurring-price').html('$'+(FinalafterDiscount + sales_tax).toFixed(2));
                    jQuery('#b60_custom_amount').val((FinalafterDiscount + sales_tax).toFixed(2));                           
          } else {
            //sales_tax = (parseFloat(<?php echo get_option( 'b60_settings_option' )['sales_tax'] ?>) / 100) * (subtotal - discount);
            var FinalafterDiscount = subtotal - discount;
                jQuery('.sales-tax').html('+ $'+sales_tax.toFixed(2));
                    jQuery('.coupon-discount-text').css('display','none');
                    jQuery('.coupon-discount-value').html('');    
                    jQuery('.recurring-price').html('$'+((subtotal - discount) + sales_tax).toFixed(2)); 
                    jQuery('#b60_custom_amount').val(((subtotal - discount) + sales_tax).toFixed(2)); 
          }

          jQuery('.sales_tax-after-discount').remove();
          jQuery('.subtotal_after-discount').remove();     
        } else {

          if(exclude_addon_frequency === undefined) {
            var subtotal_frequency = 0; 
          } else {
            var subtotal_frequency = subtotal - parseFloat(exclude_addon_frequency);
          }

          if(exclude_addon_discounts === undefined) {
            var subtotal_discounts = 0; 
          } else {
            var subtotal_discounts = subtotal - parseFloat(exclude_addon_discounts);
          }        

          if(p != 1) {  
            if((subtotal_frequency != 0) || (subtotal_discounts != 0)) {
              if(frequency_type == 'amount_d') {
                  discount = p;
                  discount_ = p;
              } else {                  
                  discount = subtotal_frequency * p;
                  discount_ = subtotal_discounts * p;
              }
            } else {
              if(frequency_type == 'amount_d') {
                  discount = p;
                  discount_ = p;

              } else {                  
                  discount = subtotal * p;
                  discount_ = subtotal * p;
              }

            }

              jQuery('.discount-value').html('- $'+discount.toFixed(2));  
              subtotal = subtotal;          
          } else {
              subtotal = subtotal * p;
          }
                    
          jQuery('.sales_tax-before-discount').remove();  
          jQuery('.subtotal_before-discount').remove();  
          jQuery('.subtotal_after-discount').remove();  
          jQuery('.actual-subtotal').html('$'+subtotal.toFixed(2));
          jQuery('.booking-subtotal').html('$'+subtotal.toFixed(2));
                 

          if(DiscountType!='' && DiscountTypeValue!=''){    

            sales_tax = (parseFloat(<?php echo esc_attr( get_option( 'b60_settings_option' )['sales_tax'] ) ?>) / 100) * (subtotal - discount);

                    if (DiscountType == 'percentage') {
                        var withoutdiscount = subtotal - parseFloat(exclude_addon_discounts);
                        var DiscountValue = (withoutdiscount * DiscountTypeValue)/100;
                        var FinalafterDiscount = subtotal - discount - DiscountValue; 
                        jQuery('.coupon-discount-text').css('display','block');
                        jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2));  
                        sales_tax = (parseFloat(<?php echo esc_attr( get_option( 'b60_settings_option' )['sales_tax'] ) ?>) / 100) * (subtotal - discount - DiscountValue);                       
                    } else if (DiscountType == 'flat') {
                        var withoutdiscount = subtotal - discount ;
                        var DiscountValue = parseFloat(DiscountTypeValue);
                        var FinalafterDiscount = withoutdiscount - DiscountTypeValue;
                        jQuery('.coupon-discount-text').css('display','block');
                        jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2));  
                        sales_tax = (parseFloat(<?php echo esc_attr( get_option( 'b60_settings_option' )['sales_tax'] ) ?>) / 100) * (subtotal - discount - DiscountValue);
                    } else {                       
                        if (DiscountBalance != 'null') {
                          if (DiscountBalance == 0) {
                            alert('Discount coupon can not be applied to this booking. You have $'+parseFloat(DiscountBalance).toFixed(2)+' balance.');
                            jQuery('.coupon-discount-text').css('display','none');
                            jQuery('.coupon-discount-value').html(''); 
                            var withoutdiscount = subtotal;
                            var DiscountValue = parseFloat(DiscountBalance);
                            var FinalafterDiscount = (withoutdiscount - discount) + sales_tax;                           
                          } else {
                            if (DiscountBalance > ((subtotal - discount) + sales_tax) ){
                                var withoutdiscount = subtotal - discount ;
                                var DiscountValue = parseFloat(withoutdiscount) + sales_tax;
                                var FinalafterDiscount = subtotal - (withoutdiscount + discount);                                                             
                            } else {
                                var withoutdiscount = subtotal - discount;
                                var DiscountValue = parseFloat(DiscountBalance);
                                var FinalafterDiscount = ((subtotal - discount) + sales_tax) - DiscountBalance;                                
                            }
                            jQuery('.coupon-discount-text').css('display','block');
                            jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2)); 
                          }
                        } else {
                          if (DiscountTypeValue > ((subtotal - discount) + sales_tax)) {
                              var withoutdiscount = subtotal - discount ;
                              var DiscountValue = parseFloat(withoutdiscount) + sales_tax;
                              var FinalafterDiscount = subtotal - (withoutdiscount + discount); 
                          } else {
                              var withoutdiscount = (subtotal - discount) + sales_tax;
                              var DiscountValue = parseFloat(DiscountTypeValue);
                              var FinalafterDiscount = withoutdiscount - DiscountTypeValue;                              
                          }                          

                          jQuery('.coupon-discount-text').css('display','block');
                          jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2)); 
                        }
                    }

                    if((FinalafterDiscount+sales_tax) === 0) {
                      jQuery('#cc-form').css('display', 'none');
                    } else {
                      jQuery('#cc-form').css('display', 'block');
                    }

                    jQuery('.sales-tax').html('+ $'+sales_tax.toFixed(2));
                    jQuery('.recurring-price').html('$'+(FinalafterDiscount).toFixed(2));
                    jQuery('#b60_custom_amount').val((FinalafterDiscount).toFixed(2));                           
          } else {
            sales_tax = (parseFloat(<?php echo esc_attr( get_option( 'b60_settings_option' )['sales_tax'] ) ?>) / 100) * (subtotal - discount);
            var FinalafterDiscount = subtotal - discount;
                jQuery('.sales-tax').html('+ $'+sales_tax.toFixed(2));
                    jQuery('.coupon-discount-text').css('display','none');
                    jQuery('.coupon-discount-value').html('');    
                    jQuery('.recurring-price').html('$'+((subtotal - discount) + sales_tax).toFixed(2)); 
                    jQuery('#b60_custom_amount').val(((subtotal - discount) + sales_tax).toFixed(2)); 
          }               
        }         
        
      } else {

        jQuery('.sales_tax-before-discount').remove();  
        jQuery('.sales_tax-after-discount').remove();  
        jQuery('.subtotal_before-discount').remove();      
        jQuery('.subtotal_after-discount').remove();  

        if(exclude_addon_frequency === undefined) {
          var subtotal_frequency = 0; 
        } else {
          var subtotal_frequency = subtotal - parseFloat(exclude_addon_frequency);
        }

        if(exclude_addon_discounts === undefined) {
          var subtotal_discounts = 0; 
        } else {
          var subtotal_discounts = subtotal - parseFloat(exclude_addon_discounts);
        }               

        if(p != 1) {  
          if((subtotal_frequency != 0) || (subtotal_discounts != 0)) {
            if(frequency_type == 'amount_d') {
                discount = p;
                discount_ = p;

            } else {                  
                discount = subtotal_frequency * p;
                discount_ = subtotal_discounts * p;
            }
          } else {
            if(frequency_type == 'amount_d') {
                discount = p;
                discount_ = p;

            } else {                  
                discount = subtotal * p;
                discount_ = subtotal * p;
            }
          }
            
            jQuery('.discount-value').html('- $'+discount.toFixed(2));  
            subtotal = subtotal;          
        } else {
            subtotal = subtotal * p;
        }

        jQuery('.booking-subtotal').html('$'+subtotal.toFixed(2));
        jQuery('#subtotal').html(subtotal.toFixed(2));
        jQuery('.actual-subtotal').html('$'+subtotal.toFixed(2));     

        if((DiscountType!='' && DiscountTypeValue!='') && (jQuery('.coupon_code').val() !== '')){
                  if (DiscountType == 'percentage') {
                      var withoutdiscount = subtotal - parseFloat(exclude_addon_discounts);
                      var DiscountValue = (withoutdiscount * DiscountTypeValue)/100;
                      var FinalafterDiscount = subtotal - discount - DiscountValue;
                      jQuery('.coupon-discount-text').css('display','block');
                      jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2));

                      // console.log('exclude_addon_discounts: ', exclude_addon_discounts); 
                      // console.log('DiscountValue: ', DiscountValue); 
                      // console.log('FinalafterDiscount: ', FinalafterDiscount); 

                  } else if (DiscountType == 'flat') {
                      var withoutdiscount = subtotal;
                      var DiscountValue = parseFloat(DiscountTypeValue);
                      var FinalafterDiscount = withoutdiscount - discount - DiscountTypeValue;
                      jQuery('.coupon-discount-text').css('display','block');
                      jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2));  
                  } else {
                      if (DiscountBalance != 'null') {
                        if (DiscountBalance == 0) {
                          alert('Discount coupon can not be applied to this booking. You have $'+parseFloat(DiscountBalance).toFixed(2)+' balance.');
                          jQuery('.coupon-discount-text').css('display','none');
                          jQuery('.coupon-discount-value').html('');
                          var withoutdiscount = subtotal;
                          var DiscountValue = parseFloat(DiscountTypeValue);
                          var FinalafterDiscount = withoutdiscount - discount;
                        } else {
                          if (DiscountBalance > (subtotal - discount)) {
                              var withoutdiscount = subtotal - discount ;
                              var DiscountValue = parseFloat(withoutdiscount);
                              var FinalafterDiscount = subtotal - (withoutdiscount + discount);                                                          
                          } else {
                            var withoutdiscount = subtotal;
                            var DiscountValue = parseFloat(DiscountBalance);
                            var FinalafterDiscount = withoutdiscount - discount - DiscountBalance;                                               
                          }  
                          jQuery('.coupon-discount-text').css('display','block');
                          jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2));      
                        }
                      } else {
                        if (DiscountTypeValue > (subtotal - discount)) {
                            var withoutdiscount = subtotal - discount ;
                            var DiscountValue = parseFloat(withoutdiscount);
                            var FinalafterDiscount = subtotal - (withoutdiscount + discount);                            
                        } else {
                          var withoutdiscount = subtotal;
                          var DiscountValue = parseFloat(DiscountTypeValue);
                          var FinalafterDiscount = withoutdiscount - discount - DiscountTypeValue;                                               
                        }
                        jQuery('.coupon-discount-text').css('display','block');
                        jQuery('.coupon-discount-value').html('- $'+(DiscountValue).toFixed(2)); 
                      }
                  }                

                  if(FinalafterDiscount === 0) {
                    jQuery('#cc-form').css('display', 'none');
                  } else {
                    jQuery('#cc-form').css('display', 'block');
                  }
                  
                  jQuery('.recurring-price').html('$'+(FinalafterDiscount).toFixed(2));
                  jQuery('#b60_custom_amount').val(FinalafterDiscount.toFixed(2));                            
        } else {
                  jQuery('.coupon-discount-text').css('display','none');
                  jQuery('.coupon-discount-value').html('');    
                  jQuery('.recurring-price').html('$'+(subtotal - discount).toFixed(2));
                  jQuery('#b60_custom_amount').val((subtotal - discount).toFixed(2)); 
        }
      }

    var services = JSON.parse(localStorage.getItem('_cart_type'));
    var service_current_price = parseFloat(jQuery("#booking_service option[value="+services['booking_service']+"]").attr('data-price'));
    var service_price = 0;    

    jQuery('#service-checkout-text').html(jQuery("#booking_service option[value="+services['booking_service']+"]").text());

    if(service_current_price != 0) {
        jQuery('#summary-checkout-amount').html('$'+service_current_price.toFixed(2)); 
        jQuery('#service-checkout-text').css('width', '57%'); 
        jQuery('#summary-checkout-amount').css('display', 'inline-block'); 
        //jQuery('#row-step-2').css('display', 'block'); 
    } else {
        jQuery('#service-checkout-text').css('width', '85%'); 
        jQuery('#summary-checkout-amount').css('display', 'none'); 
        //jQuery('#row-step-2').css('display', 'none');
    }
         
  }
}

</script>

<div id="single-page" class="single-page-container">
  <form action="" method="POST" id="payment-form" class="form-horizontal single-page">
      <input type='hidden' name="issubmit" value="1">
      <input type="hidden" name="action" value="b60_stripe_payment_charge"/>
      <input type="hidden" name="formName" value="<?php echo esc_attr( $formNameAsIdentifier ); ?>"/>
      <input type="hidden" name="b60_custom_amount" id="b60_custom_amount"/>
      <div class="">
        <div class="main-form">
          <div class="form-left">
              <div class="left">
                <div class="form-wrapper">
                  <ul class="form-container">    
                      <li id="row-step-1" class="row-step-1">
                        <div id="step-1"></div>
                      </li>
                      <li id="row-step-2" class="row-step-2 addon">
                        <div id="step-2"></div>
                      </li>
                      <li id="row-step-3" class="row-step-3">
                        <div id="step-3"></div>
                      </li>
                      <li id="row-step-4" class="row-step-4">
                        <div id="step-4"></div>
                      </li>
                      <li id="row-step-5" class="row-step-5">
                        <div id="step-5"></div>                                      
                      </li>
                      <li id="cc-form" class="row-step-6">
                        <div id="step-6">
                          <span class="forms-title">Payment Method</span>
                          <p>Enter your card information below. You will be charged after service has been rendered.</p>
                          <div class="input_container cc-container">
                            <div id="legend"></div>
                            <!-- Name -->
                            <div class="control-group card-name">
                              <label class="control-label b60-form-label"><?php _e( 'Card Holder\'s Name', 'bookin60' ); ?></label>
                              <div class="controls">
                                <input type="text" placeholder="" class="required input-xlarge b60-form-input b60-name" name="b60_name" id="b60_name" data-stripe="name">
                              </div>
                            </div>
                            <!-- Card Number -->
                              <div class="control-group card-no">
                                <label class="control-label b60-form-label"><?php _e( 'Credit Card Number', 'bookin60' ); ?></label>
                                <div class="controls">
                                  <input type="text" autocomplete="off" class="required card-no-input input-xlarge b60-form-input" size="20" data-stripe="number">
                                </div>
                              </div>
                              <!-- Expiry-->
                              <div class="control-group expiry">
                                <label class="control-label b60-form-label"><?php _e( 'Expiry Date', 'bookin60' ); ?></label>
                                <div class="controls">
                                  <input type="text" style="width: 60px;margin-right:5px" size="2" placeholder="MM" data-stripe="exp-month" class="required exp-month-input b60-form-input"/>
                                  <input type="text" style="width: 80px;" size="4" placeholder="YYYY" data-stripe="exp-year" class="required exp-year-input b60-form-input"/>
                                </div>
                              </div>
                              <!-- CVV -->
                              <div class="control-group">
                                <label class="control-label b60-form-label"><?php _e( 'CVC', 'bookin60' ); ?></label>
                                <div class="controls">
                                  <input type="password" autocomplete="off" placeholder="" class="input-mini b60-form-input" size="4" data-stripe="cvc"/>
                                </div>
                              </div>
                          </div>
                          <img alt="Cards" class="img-responsive" title="credit cards" src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/cards.png' ); ?>">
                        </div>                          
                      </li>
                      <li class="row-step-6">
                          <p>Our service is backed by our 100% Happiness Guarantee! By clicking below you are agreeing to our Terms of Service and Privacy Policy.</p>
                          <br>
                          <p><button>Book Now</button></p>
                          <br><br>
                      </li>
                  </ul> <!-- end form-container -->
                </div> <!-- end form-wrapper -->             
              </div> <!-- end left -->
          </div><!-- end form-left -->
          <div class="form-right fixed">
              <button class="show-button"><i id="show-button" class="fas fa-chevron-up"></i></button>
              <div class="container">
                <div class="right-box">
                  <p style="text-align: center;"><img class="size-full wp-image-8229" src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/time.png' ); ?>" alt="saves-you-time-img" width="28" height="28"><br>
                  <span class="box-header">SAVES YOU TIME</span>
                  We can help you live smarter, giving you time to focus on what's most important.</p>
                </div>
                <div class="separator"></div>
                <div class="right-box">
                  <p style="text-align: center;"><img class="alignnone size-full wp-image-8224" src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/best-quality.png' ); ?>" alt="only-the-best-quality-img"><br>
                  <span class="box-header">ONLY THE BEST QUALITY</span>
                  Our professional cleaners go above and beyond on every job. Cleaners are rated and reviewed after each cleaning.</p>
                </div>
                <div class="separator"></div>
                <div class="right-box">
                  <p style="text-align: center;"><img class="alignnone size-full wp-image-8224" src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/easy.png' ); ?>" alt="its-easy-to-get-maids" ><br>
                  <span class="box-header">IT'S EASY TO GET MAIDS</span>
                  Select the size of your house, a date for service and relax while we take care of your home.</p>
                </div>
                <div class="separator"></div>
                <div class="right-box">
                  <p style="text-align: center;"><img class="size-full wp-image-8229" src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/seamless-communication.png' ); ?>" alt="seamless-communication" ><br>
                  <span class="box-header">SEAMLESS COMMUNICATION</span>
                  Call, email or chat us anytime! Our average response time is <20 mins during normal business hours.</p>
                </div>
                <div class="separator"></div>
                <div class="right-box">
                  <p style="text-align: center;"><img class="alignnone size-full wp-image-8224" src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/credit-card.png' ); ?>" alt="accept-all-major-credit-cards" ><br>
                  <span class="box-header">WE ACCEPT ALL MAJOR CREDIT CARDS</span>
                  Pay securely online via 256-bit SSL encryption technology.</p>
                </div>
                <div class="separator"></div>
                <div class="right-box">
                  <p style="text-align: center;"><img class="alignnone size-full wp-image-8224" src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/safety.png' ); ?>" alt="24-hr-clean-warranty"><br>
                  <span class="box-header">24-HOUR CLEAN WARRANTY</span>
                  Did we miss a spot? Our services have a 24-Hour Warranty! If we don’t clean something to your satisfaction, contact us within 24-Hours of your service and we will return to clean any unsatisfactory areas.</p>
                </div>
              </div> <!-- end container -->
              <div id="summary-right" class="right">
                          <div class="booking-summary-header">
                              <p>Booking Summary</p>
                          </div>
                          <div class="summary-content">
                            <div class="module-content">
                                <ul class="entry-content-wrapper items-checkout">
                                   <li id="services-list" style="color: #ababab;">
                                      <div id="services-checkout">
                                        <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/house.png' ); ?>">
                                        <span class="booking-summary-left" id="service-checkout-text"></span>
                                        <span class="booking-summary-right" id="summary-checkout-amount"></span>
                                      </div>
                                   </li>
                                   <li id="date-list" style="color: #ababab;">
                                      <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/Date.png' ); ?>">
                                      </i><span class="booking-summary-left" id="date-checkout"></span>
                                   </li>
                                   <li id="time-list" style="color: #ababab;">
                                      <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/date-time.png' ); ?>">
                                      </i><span class="booking-summary-left" id="time-checkout"></span>
                                   </li>
                                   <li id="frequency-list" style="color: #ababab;">
                                      <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ).'../../assets/img/cycle.png' ); ?>">
                                      </i><span class="booking-summary-left" id="frequency-checkout"></span>
                                   </li>
                                </ul>
                            </div>

                            <div class="module-separator recurring-separator">
                               <div class="module-content">
                                  <div class="separator"></div>
                               </div>
                            </div>

                            <div class="module-html">
                               <div class="module-content">
                                  <div class="html">
                                     <ul class="entry-content-wrapper subtotal">
                                     <?php 
                                        $options = get_option( 'b60_settings_option' );
                                     ?><li><span class="booking-subtotal-left total-text">Sub total</span><span class="booking-subtotal-right actual-subtotal"></span></li><li id="sales-tax" class="sales_tax-before-discount"><span class="booking-subtotal-left" ><?php echo esc_html( $options['sales_tax'] ); ?>% Sales Tax</span><span id="sales-tax-before" class="sales-tax booking-subtotal-right">$<?php echo esc_html( $sales_tax ); ?></span></li><li class="discount-text"><span class="booking-subtotal-left">Frequency Discount</span><span class="booking-subtotal-right discount-value"></span></li><li class="subtotal_after-discount"><span class="booking-subtotal-left total-text">Sub Total</span><span class="booking-subtotal-right booking-subtotal"></span></li><li id="sales-tax" class="sales_tax-after-discount"><span class="booking-subtotal-left" ><?php echo esc_html( $options['sales_tax'] ); ?>% Sales Tax</span><span id="sales-tax-after" class="sales-tax booking-subtotal-right">$<?php echo esc_html( $sales_tax ); ?></span></li><li class="coupon-discount-text" style="display:none"><span class="booking-subtotal-left">Coupon Applied</span><span class="booking-subtotal-right coupon-discount-value"></span></li>
                                     </ul>
                                  </div>
                               </div>
                            </div>
                            <div class="module-separator recurring-separator">
                               <div class="module-content">
                                  <div class="separator"></div>
                               </div>
                            </div>
                            <div class="module-html recurring-price-container">
                               <div class="module-content">
                                  <div class="html">
                                     <ul class="entry-content-wrapper subtotal">
                                        <li><span class="booking-total-summary-left recurring-text">Total</span><span class="recurring-price booking-total-summary-right">$0.00</span></li>
                                     </ul>
                                  </div>
                               </div>
                            </div>
                            
                          </div>
                          
                    </div> <!-- end right -->
          </div> <!-- end form-right -->
        </div> <!-- end main-form -->         
      </div> <!-- end -->
    </form> <!--  end form -->
    <a href="<?php echo esc_url( $redirectURL ); ?>" class="float" <?php echo esc_attr( $show_help ); ?> target="_blank">
      <i class="fa fa-question my-float"></i>
    </a>
</div> <!-- end single-page-container -->

<form id="lead-form" class="form-inline" action="" method="POST">
    <input type="hidden" name="action" value="b60_lead_entries"/> 
    <div class="lead-form-services-container" id="lead-form-services-container"></div>    
    <div class="lead-form-frequency-container" id="lead-form-frequency-container"></div>    
    <img src="<?php echo esc_attr( plugins_url( '../assets/img/loader.gif', dirname( __FILE__ ) ) ); ?>"  id="showLoading" style="display: none;"/>
    <button type="submit">Book Now</button>
</form>