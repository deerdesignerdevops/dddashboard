document.addEventListener("DOMContentLoaded", function () {
  const closeNoticesPopupBtn = Array.from(
    document.querySelectorAll(
      ".dd__notices_popup_wrapper .dd__subscription_cancel_btn"
    )
  );

  const noticesPopupWrapper = Array.from(
    document.querySelectorAll(".dd__notices_popup_wrapper")
  );

  if (closeNoticesPopupBtn) {
    closeNoticesPopupBtn.map((btn) => {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        noticesPopupWrapper.map((popup) => {
          popup.style.display = "none";
        });
      });
    });
  }
});

$(document).ready(function ($) {
  $("form.woocommerce-checkout").on("checkout_place_order", function () {
    $("body").append('<div class="loading__spinner_wrapper"></div>');
    $(".loading__spinner_wrapper").html('<div class="loading-spinner"></div>');

    $(":submit", this).attr("disabled", "disabled");
  });

  $(document).ajaxComplete(function (event, xhr, settings) {
    if (settings.url.indexOf("wc-ajax=checkout") !== -1) {
      $(".loading__spinner_wrapper").remove();
      $(":submit").removeAttr("disabled");
    }
  });
});
