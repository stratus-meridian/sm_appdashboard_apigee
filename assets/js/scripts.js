(function($) {

  if ($('form#appdetails-search-form').length) {
    var formSearch = $('form#appdetails-search-form');
    var datetimeContainer = formSearch.find('.sm-datetime-container');
    var detachLabel = datetimeContainer.find('h4.label').detach();
    var containerInline = datetimeContainer.find('.container-inline');
  }

  $(detachLabel[0]).prependTo(containerInline[0]);
  $(detachLabel[1]).prependTo(containerInline[1]);

})(jQuery, Drupal, drupalSettings);
