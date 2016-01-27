<script type="text/javascript">

(function($) {
  
  // From http://stackoverflow.com/questions/10943544/how-to-parse-an-rss-feed-using-javascript
  $.ajax({
    url      : document.location.protocol + '//ajax.googleapis.com/ajax/services/feed/load?v=1.0&num=10&callback=?&q=' + encodeURIComponent('https://working.proudcity.com/feed'),
    dataType : 'json',
    success  : function (data) {
      if (data.responseData.feed && data.responseData.feed.entries) {
        $news = $('#proud-dashboard-news');
        $.each(data.responseData.feed.entries, function (i, e) {
          console.log(e);
          e.description = e.contentSnippet != undefined ? e.contentSnippet : '';
          $news.append('<li><h3><strong><a href="'+ e.link +'">'+ e.title +'</a></strong></h3><p>'+ e.publishedDate.substring(0, 16) +'</p><p style="margin-bottom:1em;">'+ e.description +'</p></li>');
        });
      }
    }
  });
  /*$.get('https://working.proudcity.com/feed', function (data) {
      $(data).find("entry").each(function () { // or "item" or whatever suits your feed
          var el = $(this);

          console.log("------------------------");
          console.log("title      : " + el.find("title").text());
          console.log("author     : " + el.find("author").text());
          console.log("description: " + el.find("description").text());
      });
  });*/

})(jQuery);

</script>

<ul id="proud-dashboard-news"></ul>