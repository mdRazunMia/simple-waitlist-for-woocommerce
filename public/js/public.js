/**
 * Simple Waitlist for WooCommerce - Frontend JavaScript
 *
 * Handles AJAX form submission for the waitlist signup form
 * with inline notification messages instead of browser alerts.
 */
(function ($) {
  "use strict";

  /**
   * Dismiss a message element with animation.
   */
  function dismissMessage($msg) {
    $msg.slideUp(200, function () {
      $(this).remove();
    });
  }

  /**
   * Display an inline message above/below the form.
   *
   * @param {jQuery} $form   The form element.
   * @param {string} type    One of: success, error, notice.
   * @param {string} message The message text.
   */
  function showMessage($form, type, message) {
    // Remove any existing messages.
    $form.find(".simple-waitlist-message").remove();

    var $msg = $(
      '<div class="simple-waitlist-message simple-waitlist-message--' +
        type +
        '">' +
        '<span class="simple-waitlist-message__text">' +
        $("<span>").text(message).html() +
        "</span>" +
        '<button class="simple-waitlist-message__dismiss" aria-label="Dismiss">&times;</button>' +
        "</div>"
    );

    $form.before($msg).slideDown(150);

    // Wire dismiss button.
    $msg.find(".simple-waitlist-message__dismiss").on("click", function () {
      dismissMessage($msg);
    });

    // Auto-dismiss success and notices after 6 seconds.
    if (type === "success" || type === "notice") {
      setTimeout(function () {
        dismissMessage($msg);
      }, 6000);
    }
  }

  /**
   * Determine whether a submit button should stay disabled after AJAX completes.
   *
   * Variable-product forms keep the button disabled until a variation is chosen.
   *
   * @param {jQuery} $form The form element.
   *
   * @return {boolean}
   */
  function shouldStayDisabled($form) {
    if (!$form.closest(".simple-waitlist-variable-wrap").length) {
      return false;
    }

    var variationId = $form.find('input[name="variation_id"]').val();
    return !variationId;
  }

  $(document).on("submit", ".simple-waitlist-form", function (e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $form.find("#simple-waitlist-submit");
    var $consent = $form.find('input[name="simple_waitlist_consent"]');

    // Disable button to prevent double submission.
    $submitBtn.prop("disabled", true);

    var data = {
      email: $form.find('input[name="email"]').val(),
      name: $form.find('input[name="name"]').val(),
      product_id: $form.find('input[name="product_id"]').val(),
      variation_id: $form.find('input[name="variation_id"]').val(),
      simple_waitlist_nonce: $form.find('input[name="simple_waitlist_nonce"]').val(),
    };

    if ($consent.length) {
      data.simple_waitlist_consent = $consent.is(":checked") ? 1 : 0;
    }

    $.ajax({
      url: simpleWaitlist.ajaxUrl,
      method: "POST",
      data: data,
      success: function (response) {
        showMessage($form, "success", response.message);
        $form[0].reset();
      },
      error: function (response) {
        var message = "An unexpected error occurred.";
        if (
          response.responseJSON &&
          response.responseJSON.message
        ) {
          message = response.responseJSON.message;
        }
        showMessage($form, "error", message);
      },
      complete: function () {
        if (!shouldStayDisabled($form)) {
          $submitBtn.prop("disabled", false);
        }
      },
    });
  });
})(jQuery);
