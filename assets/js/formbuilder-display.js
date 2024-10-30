jQuery(document).ready(function ($) {

	fb = new Formbuilder({
        selector: '.fb-main'
      });

      fb.on('save', function(payload){
        //console.log(payload);
      })
});