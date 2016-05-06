<?php 
$i = 0;
?>
<div class="progress">
  <div id="checklist-progress" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $count_completed; ?> of <?php echo $count; ?> steps completed"
  aria-valuemin="0" aria-valuemax="<?php echo $count; ?>" style="width:<?php echo round( ($count_completed/$count) * 100 ) ?>%">
    <?php echo $count_completed; ?> of <?php echo $count; ?> steps completed
  </div>
</div>

<div class="dashboard-proud dashboard-proud-welcome">
  <div class="get-started">
    <h3>Getting started with ProudCity</h3>
    <ul class="title-list list-unstyled proud-checklist" id="checklist">
      <?php foreach ($steps as $key => $step): ?>
        <?php $i++; ?>
        <li class="<?php if(in_array($key, $completed)): ?>completed<?php endif; ?>">
          <input type="checkbox" name="steps" value="<?php echo $key; ?>" class="pull-left" <?php if(in_array($key, $completed)): ?>checked="checked"<?php endif; ?> />
          <!--<span class="checklist-number pull-left"><?php echo $i; ?></span>-->
          <h3><?php echo $step['title']; ?></h3>
          <p>
            <?php if(!empty($step['link'])): ?><a class="btn btn-default btn-xs" href="<?php echo $step['link']; ?>" title="<?php echo $step['title']?> now"><i class="fa fa-fw fa-external-link"></i>Do it</a><?php endif;?>
            <?php if(!empty($step['video'])): ?><a class="btn btn-default btn-xs video" href="#" rel="<?php echo $step['video']; ?>" title="Watch a short video demonstrating how to <?php echo strtolower($step['title'])?>"><i class="fa fa-fw fa-youtube-play"></i>Watch it</a><?php endif;?>
          </p>
        </h3>
      <?php endforeach; ?>
    </ul>
  </div>
  <!--<div class="col-md-5">
    <div id="player" style="width:350px;height:197px;background:black;"></div>
  </div>-->
</div> 

<script type="text/javascript">
(function($, Proud) {
  Proud.behaviors.proud_dashboard_checklist = {
    attach: function(context, settings) {console.log(settings);
      var params = {
        action: 'wp-proud-checklist',
        _wpnonce: '<?php echo wp_create_nonce( $this->textdomain ); ?>'
      };
      var url = '<?php echo admin_url( 'admin-ajax.php' ) ?>';
      
      $('#checklist input').bind('click', function() {
        params.completed = [];
        $('#checklist li').removeClass('completed');
        $('#checklist input:checked').each(function() {
          params.completed.push($(this).val());
          $(this).parent().addClass('completed');
        });
        $('#checklist-progress')
          .css('width',  Math.round( (params.completed.length / $('#checklist li').length ) * 100 ) + '%' )
          .text(params.completed.length +' of '+ $('#checklist li').length);

        $.ajax({
          url: url,
          data: params,
          success: function(data) {
          }
        });
      });
      
    }
  };
})(jQuery, Proud);
</script>