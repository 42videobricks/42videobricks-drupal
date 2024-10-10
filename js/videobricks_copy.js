(function ($) {
  jQuery(document).ready( function() {
    // Copy shortcode functionality.
    let elements = document.querySelectorAll('a');
    let span = document.createElement('span');
    elements.forEach(function (item, idx) {
      item.addEventListener('click', function () {
        if (item.getAttribute('data-shortcode')) {
          span.innerText = 'Copied!';
          span.classList.add('copy-shortcode-text');
          item.appendChild(span);
          navigator.clipboard.writeText(item.getAttribute('data-shortcode')).then(() => {
          });
          setTimeout(() => {
            span.innerText = '';
          }, 1000);
        }
      });
    });
  });
})(jQuery);
