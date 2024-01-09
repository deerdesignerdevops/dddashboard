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

document.addEventListener("DOMContentLoaded", function () {
  var form = document.querySelector("form.woocommerce-checkout");

  if (form) {
    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var loadingSpinnerWrapper = document.createElement("div");
      loadingSpinnerWrapper.className = "loading__spinner_wrapper";
      document.body.appendChild(loadingSpinnerWrapper);

      loadingSpinnerWrapper.innerHTML = '<div class="loading-spinner"></div>';

      var submitButton = form.querySelector(":submit");
      submitButton.setAttribute("disabled", "disabled");
    });

    document.addEventListener("ajaxComplete", function (event) {
      var xhr = event.detail[0];
      var settings = event.detail[1];

      if (settings.url.indexOf("wc-ajax=checkout") !== -1) {
        var loadingSpinnerWrapper = document.querySelector(
          ".loading__spinner_wrapper"
        );
        if (loadingSpinnerWrapper) {
          loadingSpinnerWrapper.remove();
        }

        var submitButtons = document.querySelectorAll(":submit");
        submitButtons.forEach(function (button) {
          button.removeAttribute("disabled");
        });
      }
    });
  }
});
