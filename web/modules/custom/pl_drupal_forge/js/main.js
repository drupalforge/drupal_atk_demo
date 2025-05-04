if (typeof window.jQuery !== 'undefined') {
  window.$ = window.jQuery;
  console.log('Drupal has loaded jQuery, halilujah');
} else {
  console.log('No goddamn jQuery, you are loser!');
}

$(document).ready(function () {
  $('.js-multiselect').select2({ theme: 'pl' });
});
