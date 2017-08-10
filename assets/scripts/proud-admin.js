(function($) {

  // Link admin_bar logo to proudcity.com
  $('#wp-admin-bar-wp-logo .ab-item').attr('href', 'https://my.proudcity.com').attr('title', 'My ProudCity Sites');

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

  // Add the 'Save Order' button above Post, Taxonomy lists
  var $form = $('form#posts-filter');
  if ($form.length && $form.find('tbody.ui-sortable').length) {
    $('<div class="alignleft actions"><input type="submit" id="save-sort-order" class="button action" value="Save Order"></div>')
      .bind('click', function(e) {
        location.reload();
        e.preventDefault();
      }).prependTo($form.find('.tablenav.top'));
  }

  // Unhide the Screen options tab
  if ($form.length) {
    $('#screen-meta-links').show();
    $('#screen-options-wrap').removeClass('hidden');
  }

  // See if sp-pagebuilder is active
  setTimeout(function(){
    if ($('.tmce-active').css('display') === 'none') {
      console.log('hidden');
      $('body').addClass('sp-pagebuild-enabled');
    }
  }, 3000);


})(jQuery);