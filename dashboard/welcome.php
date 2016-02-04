<?php 
$steps = array(
  'editor' => array(
    'title' => 'Learn about The Editor',
    'icon' => 'fa-list-alt',
    'link' => null,
    'video' => 'H69hP7alHUw',
  ),
  'media' => array(
    'title' => 'Add imagery',
    'icon' => 'fa-picture-o',
    'link' => '/wp-admin/upload.php',
    'video' => null,
  ),
  'appearance' => array(
    'title' => 'Configure appearance',
    'icon' => 'fa-paint-brush',
    'link' => 'wp-admin/customize.php?return=/wp-admin',
    'video' => null,
  ),
  'integrations' => array(
    'title' => 'Select integrations',
    'icon' => 'fa-share-square',
    'link' => '/wp-admin/admin.php?page=integrations',
    'video' => null,
  ),
  'social' => array(
    'title' => 'Set up social feed',
    'icon' => 'fa-comments',
    'link' => '/wp-admin/admin.php?page=social',
    'video' => null,
  ),
  'home' => array(
    'title' => 'Edit homepage',
    'icon' => 'fa-th-large',
    'link' => '/wp-admin/post.php?post=139&action=edit', // @todo: use get_option('page_on_front')?
    'video' => null,
  ),
  'answers' => array(
    'title' => 'Add answers',
    'icon' => 'fa-list-alt',
    'link' => '/wp-admin/edit.php?post_type=question', // @todo: use get_option('page_on_front')?
    'video' => 'NAWSHKfjCZw',
  ),
  'payments' => array(
    'title' => 'Set up payments',
    'icon' => 'fa-credit-card',
    'link' => '/wp-admin/edit.php?post_type=payment', // @todo: use get_option('page_on_front')?
    'video' => '_sCEkbaX6eE',
  ),
  'agencies' => array(
    'title' => 'Create agencies',
    'icon' => 'fa-university',
    'link' => '/wp-admin/edit.php?post_type=agency', // @todo: use get_option('page_on_front')?
    'video' => '_sCEkbaX6eE',
  ),
  'forms' => array(
    'title' => 'Add forms',
    'icon' => 'fa-check-square-o',
    'link' => '/wp-admin/admin.php?page=wpcf7', // @todo: use get_option('page_on_front')?
    'video' => '_sCEkbaX6eE',
  ),
);
$i = 0;

$completed = array('editor', 'media', 'appearance', 'integrations');

$count_completed = count($completed);
$count = count($steps);

?>


<div class="progress">
  <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $count_completed; ?> of <?php echo $count; ?> steps completed"
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
          <span class="checklist-number pull-left"><?php echo $i; ?></span>
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