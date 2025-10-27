// Widget refresh functionality
jQuery(document).ready(function ($) {
  $(".pem-refresh-widget").on("click", function (e) {
    e.preventDefault();
    const button = $(this);
    const nonce = button.data("nonce");
    const widget = button.closest(".pem-widget-content");

    // Add loading state
    button.addClass("is-busy");

    $.post(
      ajaxurl,
      {
        action: "pem_refresh_widget",
        nonce: nonce,
      },
      function (response) {
        if (response.success) {
          // Replace widget content
          widget.replaceWith($(response.data.html));
        } else {
          alert("Failed to refresh widget data");
        }
      }
    ).always(function () {
      button.removeClass("is-busy");
    });
  });
});
