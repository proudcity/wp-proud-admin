<div class="dashboard-proud dashboard-proud-welcome">
  <div class="left">
    <div id="player" style="width:480px;height:270px;background:black;"></div>
  </div>
  <div class="right get-started">
    <h3>Getting started with ProudCity</h3>
    <ol class="welcome-checklist" id="checklist">
      <li>Familiarize yourself with The Editor <a href="#" class="video" rel="H69hP7alHUw">Watch video</a></li>
      <li>Add answers <a href="#" class="video" rel="NAWSHKfjCZw">Watch video</a> <a href="/wp-admin/edit.php?post_type=question">Get started &raquo;</a></li>
      <li>Set up payments <a href="#" class="video" rel="_sCEkbaX6eE">Watch video</a> <a href="/wp-admin/edit.php?post_type=payment">Get started &raquo;</a></li>
      <li>Create agencies <a href="#" class="video" rel="yIUkhv0HYKo">Watch video</a> <a href="/wp-admin/edit.php?post_type=agency">Get started &raquo;</a></li>
      <li>Add forms <!--<a href="#" class="video" rel="H69hP7alHUw">Watch video</a>--> <a href="/wp-admin/admin.php?page=wpcf7">Get started &raquo;</a></li>
      <!--<li>Set up social feeds <a href="#" class="video" rel="H69hP7alHUw">Watch video</a> <a href="/wp-admin/edit.php?post_type=payment">Get started &raquo;</a></li>-->
    </ol>
  </div>
  <div class="clearfix"></div>
</div>    

<script>
  // 2. This code loads the IFrame Player API code asynchronously.
  var tag = document.createElement('script');

  tag.src = "https://www.youtube.com/iframe_api";
  var firstScriptTag = document.getElementsByTagName('script')[0];
  firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

  // 3. This function creates an <iframe> (and YouTube player)
  //    after the API code downloads.
  var player;
  function onYouTubeIframeAPIReady() {
    player = new YT.Player('player', {
      width: '480',
      height: '270',
      videoId: 'KyjmUMX2meo',
      events: {
        'onReady': onPlayerReady,
        'onStateChange': onPlayerStateChange
      }
    });
  }

  // 4. The API will call this function when the video player is ready.
  function onPlayerReady(event) {
    //event.target.playVideo();
  }

  // 5. The API calls this function when the player's state changes.
  //    The function indicates that when playing a video (state=1),
  //    the player should play for six seconds and then stop.
  var done = false;
  function onPlayerStateChange(event) {
  }
  function stopVideo() {
  }

  function changeVideo(id) {
    player.loadVideoById(id);
  }


  (function($) {

    $('#checklist a.video').bind('click', function(e) {
      changeVideo($(this).attr('rel'));
      e.preventDefault();
    })

  })(jQuery);
</script>