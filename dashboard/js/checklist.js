(function($, Proud) {
  Proud.behaviors.proud_dashboard_checklist = {
    attach: function(context, settings) {alert('a');
      
      $('#checklist input').bind('click', function() {
        var params = settings.proud_checklist.global.params;
          params.completed = [];
        $('#checklist input:checked').each(function() {
          params.completed.push($(this).val());
        });

        $.ajax({
          url: settings.proud_checklist.global.url,
          data: params,
          success: function(data) {
            alert('sucs');
          }
        });
      });
      
    }
  };
})(jQuery, Proud);