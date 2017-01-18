(function($) {
  var options = {
    componentRestrictions: { country: "US" },
    types: ["(cities)"]
  };   
  google.maps.event.addDomListener(window, 'load', function () {
    var places = new google.maps.places.Autocomplete(document.getElementById('form-proudsettings-1-city_input'), options);
    google.maps.event.addListener(places, 'place_changed', function () {
      $('#city-input-wrapper-header').addClass('active');
      var place = places.getPlace();
      //console.log(place.photos[0].getUrl({'maxWidth': 2000, 'maxHeight': 1200}));
      $('#' + places_fields.city_id).val(place.address_components[0].long_name);
      $('#' + places_fields.state_id).val(place.address_components[2].types[0] == 'administrative_area_level_1' ? place.address_components[2].long_name : place.address_components[3].long_name);
      $('#' + places_fields.lat_id).val(place.geometry.location.lat());
      $('#' + places_fields.lng_id).val(place.geometry.location.lng());
      $('#' + places_fields.bounds_id).val(JSON.stringify(place.geometry.viewport));
      //window.location = 'https://demo.proudcity.com/get/' + place.address_components[2].long_name + '/' + place.address_components[0].long_name;
    });
  });
})(jQuery);
