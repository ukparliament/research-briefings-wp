var $ = window.jQuery;
$(function() {
  $('.research-briefings-sortable').sortable({
    connectWith: '.research-briefings-sortable',
    items: 'li',
    remove: function(event, ui) {
      var dropped = $(ui.item);
      var topicUrl = dropped.attr('data-topic-name');
      var topicTitle = dropped.text();
      if (dropped.parent().hasClass('research-briefings-unassigned')) {
        // if topic unassigned, remove input
        $('.research-briefings-form input[value="' + topicUrl + '"]').remove();
      } else {
        // else create input for $_POST
        var catId = dropped.parent().attr('data-category-id');
        $('.research-briefings-form input[value="' + topicUrl + '"]').remove();
        $('.research-briefings-form').append('<input class="hidden" type="text" name="crosstagged-category[' + catId + '][' + topicTitle + ']" value="' + topicUrl + '">');
      }
    }
  }).disableSelection();
});
