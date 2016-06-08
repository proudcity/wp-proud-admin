(function($) {

  // Link admin_bar logo to proudcity.com
  $('#wp-admin-bar-wp-logo .ab-item').attr('href', 'http://proudcity.com');

  // Collapse metaboxes by default
  // @todo: check this is actually a post page?
  $("#wpseo_meta, #submitdiv, #tagsdiv-post_tag").addClass("closed");

  // Adds table class to tables in tinymce on init
  $('body').once('proud_tinymce', function() {
    $(this).on('tinyEditorInit', function(event) {
      function addTableClasses() {
        var tables = event.ed.dom.select('table');
        if(tables && tables.length) {
          for(var i = 0; i < tables.length; i++) {
            event.ed.dom.addClass(tables[i], 'table');
          }
        }
      }
      // @TODO look into events which would help facilitate 
      // this working on paste
      addTableClasses(event.ed);
    });
  });

  // Hide the Gravityforms From and BCC fields in notifications
  if ( $('#tab_notification').length ) {
    $('#gform_notification_from, #gform_notification_bcc').parents('tr').hide();
  }

})(jQuery);