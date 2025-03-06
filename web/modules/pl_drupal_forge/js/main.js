if (typeof window.jQuery !== "undefined") {
  window.$ = window.jQuery;
  console.log('Drupal has loaded jQuery, halilujah');
} else {
  console.log('No goddamn jQuery, you are loser!');
}
