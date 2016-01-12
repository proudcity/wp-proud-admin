(function($) {

  // Link admin_bar logo to proudcity.com
  $('#wp-admin-bar-wp-logo .ab-item').attr('href', 'http://proudcity.com');

  // Collapse metaboxes by default
  // @todo: check this is actually a post page?
  $("#wpseo_meta, #submitdiv, #tagsdiv-post_tag").addClass("closed");
})(jQuery);
