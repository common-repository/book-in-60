Stripe.setPublishableKey(stripe.key);


jQuery(document).ready(function ($) {

    function showErrorMessage(message, formSelector, afterSelector) {
        var errorPanel;
        if (typeof afterSelector == "undefined") {
            errorPanel = __getMessagePanelFor(formSelector, null);
        } else {
            errorPanel = __getMessagePanelFor(formSelector, afterSelector);
        }
        errorPanel.addClass('alert alert-error').html(message);
        __scrollToMessagePanel();
    }

    function showInfoMessage(message, formSelector, afterSelector) {
        var infoPanel;
        if (typeof afterSelector == "undefined") {
            infoPanel = __getMessagePanelFor(formSelector, null);
        } else {
            infoPanel = __getMessagePanelFor(formSelector, afterSelector);
        }
        infoPanel.addClass('alert alert-success').html(message);
        __scrollToMessagePanel();
    }

    function clearMessagePanel(formSelector, afterSelector) {
        var panel = __getMessagePanelFor(formSelector, afterSelector);
        panel.removeClass('alert alert-error alert-success');
        panel.html("");
    }

    function __getMessagePanelFor(formSelector, afterSelector) {
        var panel = $('.payment-errors');
        if (panel.length == 0) {
            if (afterSelector == null) {
                panel = $('<p>', {class: 'payment-errors'}).prependTo(formSelector);
            } else {
                panel = $('<p>', {class: 'payment-errors'}).insertAfter(afterSelector);
            }
        }
        return panel;
    }

    function __scrollToMessagePanel() {
        var panel = $('.payment-errors');
        if (panel && panel.offset() && panel.offset().top) {
            if (!__isInViewport(panel)) {
                $('html, body').animate({
                    scrollTop: panel.offset().top - 100
                }, 1000);
            }
        }
        if (panel) {
            panel.fadeIn(500).fadeOut(500).fadeIn(500);
        }
    }

    function __isInViewport($elem) {
        var $window = $(window);

        var docViewTop = $window.scrollTop();
        var docViewBottom = docViewTop + $window.height();

        var elemTop = $elem.offset().top;
        var elemBottom = elemTop + $elem.height();

        return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
    }

    $("#showLoading").hide();
    $(".phone").inputmask("(999)-999-9999");

    var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
    };

    var pageUrl = getUrlParameter('p');

    if(pageUrl == 'single') {
      $('.single-page-container').css('display', 'block');
      $('#lead-form').remove();
    }

    $('.coupon_apply').on('click', function() {
        var $this = $(this);
      $this.button('loading');
        setTimeout(function() {
           $this.button('reset');
       }, 8000);
    });

    if(localStorage.getItem('customer_info') != null) {

          var obj = JSON.parse(localStorage.getItem('customer_info')); 

          jQuery.each(obj, function(key, value){
              $('#first_name').val(obj['first_name']); 
              $('#last_name').val(obj['last_name']); 
              $('#email').val(obj['email']); 
              $('#phone').val(obj['phone']);               
          });  
    }

    $('#lead-form').submit(function (e) {

        var formSelector = '#lead-form';
        var $form = $(formSelector);
        var checkRequired = 0;
        var validEmail = 0;

        e.preventDefault();    

        if(localStorage.getItem('customer_info') != null) {

          var obj = JSON.parse(localStorage.getItem('customer_info')); 

          jQuery.each(obj, function(key, value){
              email = obj['email']; 
              phone = obj['phone']; 
          });            

          var unformattedMask = Inputmask.unmask(phone, { mask: "(999)-999-9999" });
          var phoneIsValid = Inputmask.isValid(phone, { mask: "(999)-999-9999"});

          $('#lead-form').find('input').each(function(){
                if($(this).hasClass('required')) {
                    if($(this).val() == '') {
                        $(this).css('border', '1px solid red');
                        checkRequired = 1;
                        $("#showLoading").hide();
                        $form.find('button').prop('disabled', false);
                    } else {      
                        if(validateEmail(email) == false){
                             $('.email').css('border', '1px solid red');
                             checkRequired = 1;
                             $("#showLoading").hide();
                             $form.find('button').prop('disabled', false);
                        } else if(!phoneIsValid) {
                            $('.phone').css('border', '1px solid red');
                            checkRequired = 1;
                            $("#showLoading").hide();
                            $form.find('button').prop('disabled', false);
                        } else {
                            $(this).css('border', '1px solid #ccc');
                        }
                    }      
                } 
            });
        }

        if(checkRequired == 0) {

            $("#showLoading").show();

            //clearMessagePanel('#lead-form', '#legend');

            $form.find('button').prop('disabled', true);

            var fields = {};

            $('#lead-form-services-container').children('input, select, textarea').each(function(key){
                if($(this).attr('type') != 'hidden') {
                    fields[($(this).attr('id'))] = $(this).val();
                }                
            });

            $.ajax({
                type: "POST",
                url: ajaxurl.admin_ajaxurl,
                data: $form.serialize()+'&unmask_phone='+unformattedMask,
                cache: false,
                dataType: "json",
                success: function (data) {
                    $("#showLoading").hide();

                    if (data.success) {

                        console.log(data);
                        
                        if(localStorage.getItem('customer_info') != null) {
                              var obj = JSON.parse(localStorage.getItem('customer_info')); 
                              obj['id'] = data.id;
                              localStorage.setItem('customer_info', JSON.stringify(obj));
                        }

                        $.ajax({
                           type: "POST",
                           dataType: "json",
                           url: ajaxurl.admin_ajaxurl,
                           data: {'action': 'email_lead_confirmation', 'fields': fields},
                           success: function( response ) {
                              console.log(response);
                           }
                        }); 

                        if (data.redirectTo == "1") {

                           $form.find('button').prop('disabled', true);

                            setTimeout(function () {
                               window.location = data.redirectURL +'?p=single';
                            }, 1500);

                        } else {   
                            $('#multisteps').css('display', 'block');
                            $('#multisteps').popup('show');                                                                     
                        }                       
                    } else {
                        $('.phone').css('border', '1px solid red');
                        // re-enable the submit button
                        $form.find('button').prop('disabled', false);
                    }
                }
            });
        }

        return false;
    });

    $('#payment-form').submit(function (e) {

        var formSelector = '#payment-form';
        var $form = $(formSelector);
        var $form = $(this);
        var amount_ref = jQuery('#b60_custom_amount').val();

        e.preventDefault();
        

        if($(this).hasClass('single-page')) {
            $("#showLoading").show();
            var checkRequired = 0;
            var checked, selector;

            clearMessagePanel('#payment-form', '#legend');

            if(amount_ref < 1) {
                 jQuery('.b60-name').removeClass('required');
                 jQuery('.card-no-input').removeClass('required');
                 jQuery('.exp-year-input').removeClass('required');
                 jQuery('.exp-month-input').removeClass('required');
                 //console.log('Amount is equal to zero.');
            }   

            if(localStorage.getItem('customer_info') != null) {

              var obj = JSON.parse(localStorage.getItem('customer_info')); 

              jQuery.each(obj, function(key, value){
                  email = obj['email']; 
                  phone = obj['phone']; 
              });  

              var phoneIsValid = Inputmask.isValid(phone, { mask: "(999)-999-9999"}); 
            }

            $form.find('select').each(function(){
                if(($(this).hasClass('required')) && ($(this).val() == -1)) {
                    $(this).css('border', '1px solid red');
                    checkRequired = 1;
                    selector = $(this).closest('.input_container').parent().attr('id');
                } else {
                    $(this).css('border', '1px solid #ccc');
                }
            });

            $form.find('input[name=service-addons]').each(function(){
                if($(this).hasClass('required')) {
                    checked = $("input[name=service-addons]:checked").length;
                    selector = $(this).closest('.input_container').parent().attr('id');
                } 
            });

            $form.find('input').each(function(){
                if(($(this).hasClass('required')) && ($(this).val() == '')) {
                    $(this).css('border', '1px solid red');
                    checkRequired = 1;
                    selector = $(this).closest('.input_container').parent().attr('id');
                } else {
                    $(this).css('border', '1px solid #ccc');
                }
            });    


            if(validateEmail($('.email').val()) == false){
                 $('.email').css('border', '1px solid red');
                 checkRequired = 1;
                 $("#showLoading").hide();
                 $form.find('button').prop('disabled', false);
            } else if(!phoneIsValid) {
                $('.phone').css('border', '1px solid red');
                checkRequired = 1;
                $("#showLoading").hide();
                $form.find('button').prop('disabled', false);
            } else {
                //$(this).css('border', '1px solid #ccc');
            }

            if(checkRequired == 1) {
                $('html, body').animate({
                    scrollTop: $('#' + selector).offset().top - 100
                }, 2000);
            }   

            if((checked == 0) && (checkRequired == 1)) {          
              $('html, body').animate({
                 scrollTop: $('#' + selector).offset().top - 100
              }, 2000);
              $form.find('button').prop('disabled', false);
            }

            if(checkRequired == 0) {
               $form.find('button').prop('disabled', true);
               if(amount_ref > 0) {
                    Stripe.createToken($form, stripeResponseHandler); 
               } else {
                    ResponseHandler();
               }           
            }
        }

        if($(this).hasClass('multi-steps')) {
            $form.find('button').prop('disabled', true);
            if(amount_ref > 0) {
                Stripe.createToken($form, stripeResponseHandler);
            } else {                
                jQuery('.b60-name').removeClass('required');
                jQuery('.card-no-input').removeClass('required');
                jQuery('.exp-year-input').removeClass('required');
                jQuery('.exp-month-input').removeClass('required');                
                ResponseHandler();
            }  
        }
        
        return false;
    });

    var stripeResponseHandler = function (status, response) {
        var formSelector = '#payment-form';
        var $form = $(formSelector);

        if (response.error) {

            // Show the errors
            if (response.error.code && wpfsf_L10n.hasOwnProperty(response.error.code)) {
                showErrorMessage(wpfsf_L10n[response.error.code], formSelector, '#legend');
            } else {
                showErrorMessage(response.error.message, formSelector, '#legend');
            }            

            $form.find('button').prop('disabled', false);
            $("#showLoading").hide();

            if (response.error.code == 'missing_payment_information') {
              $('.card-no-input').css('border', '1px solid red');
              $('.exp-month-input').css('border', '1px solid red');
              $('.exp-year-input').css('border', '1px solid red');
            }

            if ((response.error.code == 'invalid_number') || (response.error.code == 'incorrect_number')) {
              $('.card-no-input').css('border', '1px solid red');
            }

            if (response.error.code == 'invalid_expiry_month') {
              $('.exp-month-input').css('border', '1px solid red');
            }

            if (response.error.code == 'invalid_expiry_year') {
              $('.exp-year-input').css('border', '1px solid red');
            }
        } else {
            // token contains id, last4, and card type
            var token = response.id;
            var customer_info = JSON.parse(localStorage.getItem('customer_info')); 
            var customerID = customer_info['id']; 

            $form.append("<input type='hidden' name='stripeToken' value='" + token + "' />");             
            $form.append("<input type='hidden' name='customerID' value='" + customerID + "' />");  
            //console.log(customerID);          

            ResponseHandler();
        }
    };

    $('#wizard').smartWizard({
          selected: 0,
          transition: {
              animation: 'fade', // Effect on navigation, none/fade/slide-horizontal/slide-vertical/slide-swing
              speed: '400', // Transion animation speed
              easing:'' // Transition animation easing. Not supported without a jQuery easing plugin
          },
          autoAdjustHeight: false,
          //disabledSteps: [disabledSteps],
          //errorSteps: [0]
        });
    
    $("#wizard").on("leaveStep", function(e, anchorObject, currentStepIndex, nextStepIndex, stepDirection) {

        var step_num = currentStepIndex+1;

        if(validateSteps(step_num)) {
            $("#step_"+step_num).removeClass('danger');
        } else {
            $("#step_"+step_num).addClass('danger');
        }       

        return validateSteps(step_num);
    });

    $("#wizard").on("showStep", function(e, anchorObject, stepIndex, stepDirection) {
        //console.log(stepIndex);
       if(stepIndex == 5) {
            jQuery('.toolbar').css('display', 'none');
        } else {
            jQuery('.toolbar').css('display', 'flex');
        }
    });

    function ResponseHandler() {
        var formSelector = '#payment-form';
        var $form = $(formSelector);

        var booking_data = {}
        var booking_details = new Array();
        var tempData = {}
        var i = 0;

        var customer_info = JSON.parse(localStorage.getItem('customer_info')); 
        var customerID = customer_info['id']; 
        $form.append("<input type='hidden' name='customerID' value='" + customerID + "' />");

        if(localStorage.getItem('_cart_type') != null) {
           var obj = JSON.parse(localStorage.getItem('_cart_type')); 
           
           $.each(obj, function(key, value){
                if(key == 'booking_service') {
                    tempData[i] = {
                        type: 'service',
                        title: $("#"+key+" option[value="+obj[key]+"]").text(),
                        price: parseFloat($("#"+key+" option[value="+obj[key]+"]").attr('data-price')).toFixed(2),
                    } 
                } else if(key == 'no-cleaners') {                    
                    var cleaners_title = $("#"+key+" option:selected").text();
                    var cleaners = $("#"+key+" option:selected").val();
                    var hours_title = $("#no-hours option:selected").text();
                    var hours = $("#no-hours option:selected").val(); 

                    tempData[i] = {
                        type: 'service',
                        title: cleaners_title+' x '+hours_title,
                        price: parseFloat(cleaners * hours).toFixed(2),
                    }
                } else if(key != 'no-hours') {
                    if(value == 'checked') {
                        tempData[i] = {
                            type: 'addon',
                            title: $('label[for="' + key + '"]').html(),
                            price: parseFloat($("#"+key).val()).toFixed(2),                       
                        }
                    } else {
                        tempData[i] = {
                            type: 'service',
                            title: $("#"+key+" option:selected").text(),
                            price: parseFloat($("#"+key+" option:selected").val()).toFixed(2),
                        }
                    }                    
                } else {

                }
                i++;             
           });

           booking_data['selected_service'] = tempData;
        }


        if(localStorage.getItem('customer_info') != null) {
          var obj = JSON.parse(localStorage.getItem('customer_info')); 
           booking_data['customer_info'] = obj;
        }

        if(localStorage.getItem('date') != null && localStorage.getItem('time') != null) {
           booking_data['appointment_schedule'] = { date: localStorage.getItem('date'), time: localStorage.getItem('time') };
           $form.append("<input type='hidden' name='schedule_date' value='" + localStorage.getItem('date') + "' />");
           $form.append("<input type='hidden' name='schedule_time' value='" + localStorage.getItem('time') + "' />");
        } else{
           booking_data['appointment_schedule'] = 'No Schedule Selected.'
        }

        if(localStorage.getItem('step_3') != null) {
            booking_data['frequency_discount'] = localStorage.getItem('step_3');
            if(localStorage.getItem('frequency_type') == 'amount_d') {
                booking_data['frequency_type'] = 'amount_discount';
            } else {
                 booking_data['frequency_type'] = 'percentage_discount';
            }
            $form.append("<input type='hidden' name='frequency' value='" + booking_data['frequency_discount'] + "' />");  
        } else {
            booking_data['frequency_discount'] = 'No Frequency Selected.';
        }

        var subtotal = ($('.actual-subtotal').text()).replace(/\$/g, "");
        var frequency_discount = (($('.discount-value').text()).replace(/[\-\+\$]/g, "")).replace(/ /g, "");
        var coupon_discount = (($('.coupon-discount-value').text()).replace(/[\-\+\$]/g, "")).replace(/ /g, "");
        var sales_tax = (($('.sales-tax').text()).replace(/[\-\+\$]/g, "")).replace(/ /g, "");
        var total_sales = ($('.recurring-price').text()).replace(/\$/g, "");
        var bookingDetails = new Array();
        var booking_service = new Array();
        var booking_addon = new Array();
        var i = 0;
        var sales_tax_symbol = '';
        
        if($("#sales-tax-before").hasClass("sales-tax")){
            sales_tax_symbol = 'before';
        }

        if($("#sales-tax-after").hasClass("sales-tax")){
            sales_tax_symbol = 'after';
        }

        $.each(booking_data['selected_service'], function(key, value){
            if(value.type == 'service') {
                booking_service.push({
                        'title': value.title,
                        'price': value.price,          
                });
            } else {
                booking_addon.push({
                        'title': value.title,
                        'price': value.price,         
                });
            }   
            i++;            
        });

        var calendarNotes = "Booking Details: \n" + booking_details.join("") +
                      "\n\nService Schedule: \n" + booking_data['appointment_schedule'].date + 
                      " @ " + booking_data['appointment_schedule'].time + 
                      "\n\nFrequency: \n" +booking_data['frequency_discount'];

        bookingDetails.push(booking_service);
        bookingDetails.push(booking_addon);

        bookingDetails.push({
            "subtotal": subtotal,
            "frequency_discount": frequency_discount,
            "coupon_discount": coupon_discount,
            "sales_tax": sales_tax,
            "sales_tax_symbol": sales_tax_symbol,
            "total_sales": total_sales
        });

        $form.append("<input type='hidden' name='bookingDetails' value='" + JSON.stringify(bookingDetails) + "' />"); 

        $.ajax({
            type: "POST",
            url: ajaxurl.admin_ajaxurl,
            data: $form.serialize(),
            cache: false,
            dataType: "json",
            success: function (data) {
                console.log(data);
                $("#showLoading").hide();
                if (data.success) { 
                    $.ajax({
                       type: "POST",
                       dataType: "json",
                       url: ajaxurl.admin_ajaxurl,
                       data: {'action': 'email_booking_confirmation', 'data': booking_data},
                       success: function( response ) {
                       }
                    }); 

                    $.ajax({
                       type: "POST",
                       dataType: "json",
                       url: ajaxurl.admin_ajaxurl,
                       data: {'action': 'email_booking_confirmation_admin', 'data': booking_data},
                       success: function( response ) {
                       }
                    }); 
                    // $form.find('button').prop('disabled', false);
                    if (data.redirect == true) {
                        console.log('Redirect now');
                        setTimeout(function () {
                            window.location = data.redirectURL;
                        }, 1500);
                        localStorage.clear();
                    }
                } else {
                    // re-enable the submit button
                    $form.find('button').prop('disabled', false);
                    // show the errors on the form
                    showErrorMessage(data.msg, formSelector, '#legend');
                }
            }
        });
    }

    function validateSteps(step){

      var isStepValid = true;
      var selector =  $('.input_container').parent().attr('id');
      var curStep = 'step-'+step;
      var checked = 0;


      if(localStorage.getItem('customer_info') != null) {

        var obj = JSON.parse(localStorage.getItem('customer_info')); 

        jQuery.each(obj, function(key, value){
            email = obj['email']; 
            phone = obj['phone']; 
        });  

        var phoneIsValid = Inputmask.isValid(phone, { mask: "(999)-999-9999"}); 
      }

      $('#'+curStep).find('select').each(function(){
          if(($(this).hasClass('required')) && ($(this).val() == -1)) {
              $(this).css('border', '1px solid red');
              isStepValid = false;
          } else {
              $(this).css('border', '1px solid #ccc');
          }
      });

      $('#'+curStep).find('input').each(function(){
          if($(this).hasClass('required')) {
              if($(this).val() == '') {
                  $(this).css('border', '1px solid red');
                  isStepValid = false;
              } else {      
                  if(validateEmail(email) == false){
                       $('.email').css('border', '1px solid red');
                       isStepValid = false;
                  } else if(!phoneIsValid) {
                      $('.phone').css('border', '1px solid red');
                      isStepValid = false;
                  } else {
                      $('.phone').css('border', '1px solid #ccc');
                      $(this).css('border', '1px solid #ccc');
                  }
                  $(this).css('border', '1px solid #ccc');
              }      
          } 
      });    

      $('#'+curStep).find('input[name=service-addons]').each(function(){
          if($(this).hasClass('required')) {
              checked = $("input[name=service-addons]:checked").length;
              if (checked == 0) {
                isStepValid = false;
              } 
          }           
      });     

      if($('#'+curStep).find('div').hasClass('calendar-display')) {
          if ( $(".-selected-").length == 0) { 
            isStepValid = false;
          }

          $('.time-slot-list').find('input[name=time_radio]').each(function(){
              //if($(this).hasClass('required')) {
                 var checked = $("input[name=time_radio]:checked").length;
                  if (checked == 0) {
                    isStepValid = false;
                  } 
             // }           
          });  
      }

      return isStepValid;
    }

    function validateEmail(Email) {
        var pattern = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;

        return $.trim(Email).match(pattern) ? true : false;
    }

    $('.nav-link').on('click', function() {
        $(this).parent().parent().parent().removeClass('responsive');
        console.log('test');
    });

    // Start Binding

    var viewData;

    viewData = {};

    function updateViewData(key, value) {      

        if(localStorage.getItem('customer_info') != null) {
          var customer_info = JSON.parse(localStorage.getItem('customer_info')); 
          customer_info[key] = value;
          localStorage.setItem('customer_info', JSON.stringify(customer_info));
        }

        viewData[key] = value;
    }

    // Register all bindable elements
    function detectBindableElements() {
        var bindableEls;

        bindableEls = $('[data-bind]');

        // Add event handlers to update viewData and trigger callback event.
        bindableEls.on('change', function() {
            var $this, $wordCount;
            
            $this = $(this);
            
            updateViewData($this.data('bind'), $this.val());

            $wordCount = $this.val().split(' ').length;
            //$("#yourdropdownid option:selected").text();
            //console.log($this.text());

            $(document).trigger('updateDisplay');
        });

        // Add a reference to each bindable element in viewData.
        bindableEls.each(function() {
            updateViewData($(this));
        });
    }

    // Trigger this event to manually update the list of bindable elements, useful when dynamically loading form fields.
    $(document).on('updateBindableElements', detectBindableElements);

    detectBindableElements();

    // An example of how the viewData can be used by other functions.
    function updateDisplay() {
        var updateElsText,updateElsInput,updateElsAmount;

        updateElsText = $('[data-update-text]');
        updateElsInput = $('[data-update-input]');
        updateElsAmount = $('[data-update-price]');

        updateElsText.each(function() {
            $(this).html(viewData[$(this).data('update-text')]);
        });

        updateElsInput.each(function() {
            $(this).val(viewData[$(this).data('update-input')]);
        });

        updateElsAmount.each(function() {
            $(this).val(viewData[$(this).data('update-price')]);
        });

    }

    // Run updateDisplay on the callback.
    $(document).on('updateDisplay', updateDisplay);

    // End Binding

    

    var holidayDates = new Array();

    $.ajax({
       type: "POST",
       dataType: "json",
       url: ajaxurl.admin_ajaxurl,
       data: {'action': 'get_holidays'},
       success: function( response ) {
          var holidays = response;
          
          $.each(holidays, function(key, value) {
              date = new Date(value.holiday_date);
              pretty_date = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
              holidayDates.push(pretty_date); 
          });
       }
    });               

    $.ajax({
       type: "POST",
       dataType: "json",
       url: ajaxurl.admin_ajaxurl,
       data: {'action': 'get_service_slot'},
       success: function( response ) {
          var obj = response;
          var disabled_days = holidayDates;
          var selectedDate = localStorage.getItem('date');
          var selectedTime = localStorage.getItem('time');
          var timeslots = new Array();
          var checked, time, myDate,crmTime,adjustMonth,crmTimePm, crmTimeMin;

          if($('#payment-form').hasClass('multi-steps')) {
              $('#cal').data('datepicker').selectDate(new Date(selectedDate));
          }
          
          $('.calendar-display').append(`<div class="datepicker-time-slots-default"></div>`);
         
          $.each(obj, function(key, value) {
              if(key == new Date(selectedDate).getDay()) {    
                $('.datepicker-time-slots').css('display', 'block');
                $.each(value, function(key, value) {

                    time = value.start_time+` - `+value.end_time;

                    if(selectedTime == time) {
                      checked = 'checked';
                    } else {
                        checked = '';
                    }

                    timeslots.push(`<li class="">
                      <input class="field-select" name="time_radio" type="radio" value="`+value.start_time+` - `+value.end_time+`" id="timeslot_`+key+`"`+ checked+`>
                      <label for="timeslot_`+key+`">`+value.start_time+` - `+value.end_time+`</label>
                    </li>`);
                });         
              } 
          });

          $('.datepicker-time-slots').css('display', 'none');
          $('.datepicker-time-slots-default').append(`<ul class="time-slot-list">`+timeslots.join("")+`</ul>`);
          $('input[type=radio][name=time_radio]').change(function() {
              $('#time-checkout').html(this.value); 
              if((this.value).substr(4,1) == " ") {
                crmTime = "0"+(this.value).substr(0,5).trim();
              } else {
                crmTime = (this.value).substr(0,5);
              }
              if((this.value).indexOf("pm") != -1){
                crmTime = parseInt(crmTime.substr(0,2))+12 +":"+ crmTime.substr(3,4);
              }
              if(new Date(selectedDate).getMonth() < 10) {
                adjustMonth = "0"+(new Date(selectedDate).getMonth()+1);
              } else {
                adjustMonth = (new Date(selectedDate).getMonth()+1);
              }
              myDate = new Date(selectedDate).getFullYear()+'-'+adjustMonth+'-'+new Date(selectedDate).getDate()+'T'+crmTime+':00'+response.selectedOffset;                    
              localStorage.setItem('time', this.value);
              localStorage.setItem('selectedTimezone', response.selectedTimezone);
              localStorage.setItem('selectedSlot', myDate);
          });

          $('#cal').datepicker({
              minDate: addDays(new Date(), 2),              
              inline: true,
              onRenderCell: function (date, cellType) {
                pretty_date = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
                var disabled = false

                if (cellType == 'day' && disabled_days.indexOf(pretty_date) != -1) {
                    return {
                        classes: 'holiday-class',
                        //disabled: true
                    }
                }                   
              },
              onSelect: function onSelect(fd, date) {
                  var title = '', content = ''
                  var timeslots = new Array();                  
                  
                  $('#step-4-text').html(fd);
                  $('#date-checkout').html(fd);
                  localStorage.setItem('step_4', fd);
                  localStorage.setItem('date', fd);                         

                  $('.datepicker-time-slots-default').remove();
                  $('.time-slot-list').remove();

                  $.each(obj, function(key, value) {
                      if(key == date.getDay()) {    
                        $('.datepicker-time-slots').css('display', 'block');
                        $.each(value, function(key, value) {
                            timeslots.push(`<li class="">
                              <input class="field-select" name="time_radio" type="radio" value="`+value.start_time+` - `+value.end_time+`" id="timeslot_`+key+`">
                              <label for="timeslot_`+key+`">`+value.start_time+` - `+value.end_time+`</label>
                            </li>`);
                        });         
                      } 
                  });

                  $('.datepicker-holidays').css('display', 'none');
                  $('.datepicker-time-slots').css('display', 'block');
                  $('.datepicker-time-slots').append(`<ul class="time-slot-list">`+timeslots.join("")+`</ul>`);
                  $('input[type=radio][name=time_radio]').change(function() {
                      $('#time-checkout').html(this.value);   

                      if((this.value).substr(4,1) == " ") {
                        crmTime = "0"+(this.value).substr(0,5).trim();
                      } else {
                        crmTime = (this.value).substr(0,5);
                      }
                      if((this.value).indexOf("pm") != -1){
                        crmTime = parseInt(crmTime.substr(0,2))+12 +":"+ crmTime.substr(3,4);
                      }
                      if(new Date(date).getMonth() < 10) {
                        adjustMonth = "0"+(new Date(date).getMonth()+1);
                      } else {
                        adjustMonth = (new Date(date).getMonth()+1);
                      }
                      myDate = new Date(date).getFullYear()+'-'+adjustMonth+'-'+new Date(date).getDate()+'T'+crmTime+':00'+response.selectedOffset;                    
                      localStorage.setItem('time', this.value);
                      localStorage.setItem('selectedTimezone', response.selectedTimezone);
                      localStorage.setItem('selectedSlot', myDate);
                  });

                  $.ajax({
                     type: "POST",
                     dataType: "json",
                     url: ajaxurl.admin_ajaxurl,
                     data: {'action': 'get_holidays'},
                     success: function( response ) {
                        var holidays = response;
                        var holiday_date = new Array();
                        date = new Date(fd);
                        pretty_date = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
                
                        if(holidays.length > 0) {
                          
                          $.each(holidays, function(key, value) {

                              holidayDates_ = new Date(value.holiday_date);
                              pretty_date_ = holidayDates_.getFullYear() + '-' + (holidayDates_.getMonth() + 1) + '-' + holidayDates_.getDate();
                              holiday_date.push(pretty_date_);

                              if(pretty_date == pretty_date_) {  
                                $('.holiday-date').remove();
                                $('.holiday-name').remove();
                                $('.holiday-description').remove();
                                $('.datepicker-time-slots').css('display', 'none');
                                $('.datepicker-holidays').css('display', 'block');    
                                $('.datepicker-holidays').append(`<div class="holiday-date">HOLIDAY: `+value.holiday_date+` </div>
                                  <div class="holiday-name">`+value.holiday_name+`</div><div class="holiday-description">`+value.holiday_description+`</div></div>`);                             
                              }
                          });                          
                        } 

                        if (date && holiday_date.indexOf(date.getDay()) != -1) {
                          $('.datepicker-holidays').css('display', 'none');
                          $('.datepicker-time-slots').css('display', 'block');    
                        }
                     }
                   });
              }
          });

          $('#cal-single').datepicker({
              minDate: addDays(new Date(), 2),
              inline: false,
              onRenderCell: function (date, cellType) {
                pretty_date = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
                var disabled = false

                if (cellType == 'day' && disabled_days.indexOf(pretty_date) != -1) {
                    return {
                        classes: 'holiday-class',
                        //disabled: true
                    }
                }              
              },
              onSelect: function onSelect(fd, date) {
                  var title = '', content = ''
                  var timeslots = new Array();
                  var timeslots_options = new Array();
                  //var myDate,crmTime;

                  $('#date-list').css('display', 'block');
                  $('#time-list').css('display', 'block'); 
                  $('#step-4-text').html(fd);
                  $('#date-checkout').html(fd);
                  localStorage.setItem('step_4', fd);
                  localStorage.setItem('date', fd);

                  $('#single-time-slots option').remove();

                  $.each(obj, function(key, value) {
                      if(key == date.getDay()) {    
                        $.each(value, function(key, value) {
                            timeslots.push(value.start_time+` - `+value.end_time);
                            timeslots_options.push(`<option class="" value="`+value.start_time+` - `+value.end_time+`" id="timeslot_`+key+`">`+value.start_time+` - `+value.end_time+`</option>`);
                        });         
                      } 
                  });

                  localStorage.setItem('time', timeslots[0]);

                  if((localStorage.getItem('time')).substr(4,1) == " ") {
                    crmTime = "0"+(localStorage.getItem('time')).substr(0,5).trim();
                  } else {
                    crmTime = (localStorage.getItem('time')).substr(0,5);
                  }
                  if((localStorage.getItem('time')).indexOf("pm") != -1){
                    crmTime = parseInt(crmTime.substr(0,2))+12 +":"+ crmTime.substr(3,4);
                  }
                  if(new Date(date).getMonth() < 10) {
                    adjustMonth = "0"+(new Date(date).getMonth()+1);
                  } else {
                    adjustMonth = (new Date(date).getMonth()+1);
                  }
                  myDate = new Date(date).getFullYear()+'-'+adjustMonth+'-'+new Date(date).getDate()+'T'+crmTime+':00'+response.selectedOffset;  
                  localStorage.setItem('selectedTimezone', response.selectedTimezone);
                  localStorage.setItem('selectedSlot', myDate);

                  $('#time-checkout').html(localStorage.getItem('time'));                         

                  $('.datepicker-holidays').css('display', 'none');
                  $('#single-time-slots').append(timeslots_options.join(""));
                  $('#single-time-slots').change(function() {
                      $('#time-checkout').html(this.value);

                      if((this.value).substr(4,1) == " ") {
                        crmTime = "0"+(this.value).substr(0,5).trim();
                      } else {
                        crmTime = (this.value).substr(0,5);
                      }
                      if((this.value).indexOf("pm") != -1){
                        crmTime = parseInt(crmTime.substr(0,2))+12 +":"+ crmTime.substr(3,4);
                      }
                      if(new Date(date).getMonth() < 10) {
                        adjustMonth = "0"+(new Date(date).getMonth()+1);
                      } else {
                        adjustMonth = (new Date(date).getMonth()+1);
                      }
                      myDate = new Date(date).getFullYear()+'-'+adjustMonth+'-'+new Date(date).getDate()+'T'+crmTime+':00'+response.selectedOffset;                    
                      localStorage.setItem('time', this.value);
                      localStorage.setItem('selectedTimezone', response.selectedTimezone);
                      localStorage.setItem('selectedSlot', myDate);                      
                  });

                  $.ajax({
                     type: "POST",
                     dataType: "json",
                     url: ajaxurl.admin_ajaxurl,
                     data: {'action': 'get_holidays'},
                     success: function( response ) {
                        var holidays = response;
                        var holiday_date = new Array();
                        date = new Date(fd);
                        pretty_date = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
                
                        if(holidays.length > 0) {
                          
                          $.each(holidays, function(key, value) {

                              holidayDates_ = new Date(value.holiday_date);
                              pretty_date_ = holidayDates_.getFullYear() + '-' + (holidayDates_.getMonth() + 1) + '-' + holidayDates_.getDate();
                              holiday_date.push(pretty_date_);

                              if(pretty_date == pretty_date_) {  
                                $('.holiday-date').remove();
                                $('.holiday-name').remove();
                                $('.holiday-description').remove();
                                $('.datepicker-time-slots').css('display', 'none');
                                $('.datepicker-holidays').css('display', 'block');    
                                $('.datepicker-holidays').append(`<div class="holiday-date">HOLIDAY: `+value.holiday_date+` </div>
                                  <div class="holiday-name">`+value.holiday_name+`</div><div class="holiday-description">`+value.holiday_description+`</div></div>`);                             
                              }
                          });                          
                        } 

                        if (date && holiday_date.indexOf(date.getDay()) != -1) {
                          $('.datepicker-holidays').css('display', 'none');
                          $('.datepicker-time-slots').css('display', 'block');    
                        }
                     }
                   });
              }
          })
       }
    });

    //Method to add days to date
    function addDays(date, days) {
      var dat = date;
      dat.setDate(dat.getDate() + days);
      return dat;
    }

    $('.show-button').on('click', function() {
        let target = $('.summary-content');
        target.slideToggle(function() {
            if($('.summary-content').is(":hidden")){
                $("#show-button").removeClass("fa-chevron-down");
                $("#show-button").addClass("fa-chevron-up");
            } else if($('.summary-content').is(":visible")){
                $("#show-button").removeClass("fa-chevron-up");
                $("#show-button").addClass("fa-chevron-down");
            }   
        });        
    });

    // if($('#summary-right').hasClass('right')) {
    //     var $sidebar   = $(".form-right .right"), 
    //               $window    = $(window),
    //               offset     = $sidebar.offset(),
    //               topPadding = 80;

    //       $window.scroll(function() {
    //         //if (typeof offeset !== 'undefined') {
    //           if ($window.scrollTop() > offset.top) {
    //               $sidebar.stop().animate({
    //                   //marginTop: $window.scrollTop() - offset.top + topPadding
    //                   marginTop: 730
    //               });
    //           } else {
    //               $sidebar.stop().animate({
    //                   marginTop: 30
    //               });
    //           }  
    //         //}
    //       });  
    // }

    
});

