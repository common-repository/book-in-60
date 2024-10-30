/*-----------------------------
* Build Your Plugin JS / $
-----------------------------*/
/*
$ Ready!
*/
jQuery(document).ready(function($){
    "use strict";

    $('#mySpecialButton').click(function(){
        $(this).addClass('active');    
    });
    
    $('#addon-refrigerator').click(function(){

        if($(this).prop("checked") == true){
           $('#no-refrigerator').css('display', 'inline-block');
        } else if($(this).prop("checked") == false){
           $('#no-refrigerator').css('display', 'none');
        }

    });

    $('#addon-oven').click(function(){

        if($(this).prop("checked") == true){
           $('#no-oven').css('display', 'inline-block');
        } else if($(this).prop("checked") == false){
           $('#no-oven').css('display', 'none');
        }

    });

    $(".submit").click(function(){
      return false;
    });

    $(".nav-step a").click(function(){        
        $("#wizard").removeClass("responsive");
    }); 
});

function myFunction() {
  var x = document.getElementById("wizard");
  if (x.className === "swMain") {
    x.className += " responsive";
  } else {
    x.className = "swMain";
  }
}