(function($) {
  google.maps.event.addDomListener(window, 'load', function () {
    var places = new google.maps.places.Autocomplete(document.getElementById('city_input'), options);
    google.maps.event.addListener(places, 'place_changed', function () {
      $('#city-input-wrapper-header').addClass('active');
      var place = places.getPlace();
      console.log(place);
      //window.location = 'https://demo.proudcity.com/get/' + place.address_components[2].long_name + '/' + place.address_components[0].long_name;
    });
  });
})(jQuery);