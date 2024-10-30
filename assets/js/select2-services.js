jQuery(function($){

    'use strict';

	$('#addon_select2_cat').select2();
	$('#addon_select2_posts').select2({
  		ajax: {
    			url: ajaxurl.admin_ajaxurl, 
    			dataType: 'json',
    			delay: 250, 
    			data: function (params) {

      				return {
        				q: params.term,
        				action: 'getposts' 
      				};
    			},
    			processResults: function( data ) {
    				console.log(data);
				var options = [];
				if ( data ) {
 
					$.each( data, function( index, text ) { 
						options.push( { id: text[0], text: text[1]  } );
					});
 
				}
				return {
					results: options
				};
			},
			cache: true
		},
		minimumInputLength: 3 
	});

    $('#title').prop('required', true);
    $('#service_price').prop('required', true);
    $('#addon_price').prop('required', true);    

	$('#flat_price_radio').click(function () {
        $('#flat_pricing_container').css('display', 'table-row');
        $('#values-container').css('display', 'table-row');
        $('#parameter-duration-container').css('display', 'table-row');
        $('#price_ranges_container').css('display', 'none');
        $('#flat_price_amount').prop('required', true);
    });

    $('#delete-price-parameter').closest('.price-range-content #delete-price-parameter').css('visibility', 'hidden');
    
    if ($('#flat_price_radio').prop('checked')) {
        $('#flat_price_amount').prop('required', true);
        $('#range-label').prop('required', false);
        $('#range-qty-min').prop('required', false);
        $('#range-qty-max').prop('required', false);
        $('#range-price').prop('required', false);
    }

    if ($('#price_ranges_radio').prop('checked')) {
        $('#range-label').prop('required', true);
        $('#range-qty-min').prop('required', true);
        $('#range-qty-max').prop('required', true);
        $('#range-price').prop('required', true);
        $('#flat_price_amount').prop('required', false);
        $('.table-price-ranges .price-range-content #range-qty-max').prop('disabled', true);
        $('.table-price-ranges .price-range-content #range-qty-min').prop('disabled', true);
        $('.table-price-ranges .price-range-content:last-child #range-qty-max').prop('disabled', false);
        $('.table-price-ranges .price-range-content:last-child #range-qty-min').prop('disabled', false);
    }

    $('#price_ranges_radio').click(function () {
        $('#price_ranges_container').css('display', 'table-row');
        $('#flat_pricing_container').css('display', 'none');
        $('#values-container').css('display', 'none');
        $('#parameter-duration-container').css('display', 'none');
        $('#range-label').prop('required', true);
        $('#range-qty-min').prop('required', true);
        $('#range-qty-max').prop('required', true);
        $('#range-price').prop('required', true);
        $('#flat_price_amount').prop('required', false);
        $('#delete-price-parameter').css('visibility', 'hidden');
    });

    $( ".table-price-ranges" ).validate({
      rules: {
        ranges_max: {
          required: true,
          min: 13
        }
      }
    });

    $('#percentage_d').click(function () {
        $('#discount_container').css('display', 'block');
        $('#amount_discount_container').css('display', 'none');
    });

    $('#amount_d').click(function () {
        $('#discount_container').css('display', 'none');
        $('#amount_discount_container').css('display', 'block');
    });

    $('#percentage_d_d').click(function () {
        $('#discount_container_d').css('display', 'block');
        $('#amount_discount_container_d').css('display', 'none');
    });

    $('#amount_d_d').click(function () {
        $('#discount_container_d').css('display', 'none');
        $('#amount_discount_container_d').css('display', 'block');
    });

    $('#expiry_date').datepicker();

    $('#hourly_rate').click(function () {
        if ($('#hourly_rate').prop('checked')) {
            $('#allow-increments-container').css('display', 'table-row');
            $('#duration-container').css('display', 'none');
            $('#cleaners-container').css('display', 'table-row');
            $('#hourly-container').css('display', 'table-row');
        } else {
            $('#duration-container').css('display', 'table-row');
            $('#cleaners-container').css('display', 'none');
            $('#hourly-container').css('display', 'none');
            $('#allow-increments-container').css('display', 'none');
        }
    });

    $('#allow_increments').click(function () {
        if ($('#allow_increments').prop('checked')) {
            //$('#allow-increments-container').css('display', 'block');
        } else {
            //$('#allow-increments-container').css('display', 'none');
        }
    });

    $('#frequency').change(function(){
      if($(this).val() == 'c'){ 
        $('#repeats-week-container').css('display', 'table-row');
      } else {
        $('#repeats-week-container').css('display', 'none');
      }
    });

    var min = $('.table-price-ranges .price-range-content:last-child #range-qty-min').val();
    var max = $('.table-price-ranges .price-range-content:last-child #range-qty-max').val();

    $('body').on('click', '.add_price_range', function() { 
        
            var data = {
                'action': 'price_range'
            };  
            var min = $('.table-price-ranges .price-range-content:last-child #range-qty-min').val();
            var max = $('.table-price-ranges .price-range-content:last-child #range-qty-max').val();

            if(($('.table-price-ranges .price-range-content:last-child #range-label').val() === '') || ($('.table-price-ranges .price-range-content:last-child #range-qty-min').val() === '') || ($('.table-price-ranges .price-range-content:last-child #range-qty-max').val() === '') || ($('.table-price-ranges .price-range-content:last-child #range-price').val() === '') )  {               
                $('.table-price-ranges .price-range-content:last-child #range-label').css('border-color', 'red');
                $('.table-price-ranges .price-range-content:last-child #range-qty-min').css('border-color', 'red');
                $('.table-price-ranges .price-range-content:last-child #range-qty-max').css('border-color', 'red');
                $('.table-price-ranges .price-range-content:last-child #range-price').css('border-color', 'red');
            } else {
                if(min > max){
                    $('#min-max-error').css('display', 'table-row');
                } else {
                    $('.table-price-ranges .price-range-content:last-child #range-label').css('border-color', '');
                    $('.table-price-ranges .price-range-content:last-child #range-qty-min').css('border-color', '');
                    $('.table-price-ranges .price-range-content:last-child #range-qty-max').css('border-color', '');
                    $('.table-price-ranges .price-range-content:last-child #range-price').css('border-color', '');
                    $('.table-price-ranges .price-range-content #range-qty-max').prop('disabled', true);
                    $('.table-price-ranges .price-range-content #range-qty-min').prop('disabled', true);
                    $('.table-price-ranges .price-range-content:nth-last-child(1) #range-qty-max').prop('disabled', true);
                    $('.table-price-ranges .price-range-content:nth-last-child(1) #range-qty-min').prop('disabled', true);
                    $.post(ajaxurl.admin_ajaxurl, data, function(response) {
                       $('.price-range-body').append( response );
                       $('.table-price-ranges .price-range-content:last-child #range-label').prop('required', true);
                       $('.table-price-ranges .price-range-content:last-child #range-qty-max').prop('required', true);
                       $('.table-price-ranges .price-range-content:last-child #range-qty-min').prop('required', true);
                       $('.table-price-ranges .price-range-content:last-child #range-qty-min').val(max);
                       $('.table-price-ranges .price-range-content:last-child #range-price').prop('required', true);                       
                    }); 
                    $('#min-max-error').css('display', 'none');
                } 
            }  
       }); 

    $('body').on('click', '.delete-price-parameter', function() {
        $(this).parent().parent('.price-range-content').fadeOut(150, function() {
            $(this).remove();
            $('.table-price-ranges .price-range-content:nth-last-child(1) #range-qty-max').prop('disabled', false);
            $('.table-price-ranges .price-range-content:nth-last-child(1) #range-qty-min').prop('disabled', false);
       }); 
     }); 

    $('body').on('click', '#publish', function() {
        var min = $('.table-price-ranges .price-range-content:last-child #range-qty-min').val();
        var max = $('.table-price-ranges .price-range-content:last-child #range-qty-max').val();
        var price = $('.table-price-ranges .price-range-content:last-child #range-price').val();

        if((min > max) || (max === '')) {
           $('.table-price-ranges .price-range-content #range-qty-max').prop('disabled', true);
           $('.table-price-ranges .price-range-content #range-qty-min').prop('disabled', true);
           $('.table-price-ranges .price-range-content:last-child #range-qty-max').prop('disabled', false);
           $('.table-price-ranges .price-range-content:last-child #range-qty-min').prop('disabled', false);
           $('.table-price-ranges .price-range-content:last-child #range-qty-max').val('');
        } else {           
           if(price !== '') {
             $('.table-price-ranges .price-range-content #range-qty-max').prop('disabled', false);
             $('.table-price-ranges .price-range-content #range-qty-min').prop('disabled', false);
             $('.table-price-ranges .price-range-content:last-child #range-qty-max').prop('disabled', false);
             $('.table-price-ranges .price-range-content:last-child #range-qty-min').prop('disabled', false);
           }
        }
    });
});