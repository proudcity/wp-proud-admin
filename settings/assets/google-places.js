(function($) {
  var options = {
    componentRestrictions: { country: "US" },
    types: ["(cities)"]
  };   
  google.maps.event.addDomListener(window, 'load', function () {
    var places = new google.maps.places.Autocomplete(document.getElementById('city_input'), options);
    google.maps.event.addListener(places, 'place_changed', function () {
      $('#city-input-wrapper-header').addClass('active');
      var place = places.getPlace();
      //console.log(place.photos[0].getUrl({'maxWidth': 2000, 'maxHeight': 1200}));
      $('#city').val(place.address_components[0].long_name);
      $('#state').val(place.address_components[2].types[0] == 'administrative_area_level_1' ? place.address_components[2].long_name : place.address_components[3].long_name);
      $('#lat').val(place.geometry.location.lat());
      $('#lng').val(place.geometry.location.lng());
      //window.location = 'https://demo.proudcity.com/get/' + place.address_components[2].long_name + '/' + place.address_components[0].long_name;
    });
  });
})(jQuery);
