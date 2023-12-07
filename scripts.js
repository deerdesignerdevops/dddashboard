document.addEventListener("DOMContentLoaded", function () {
  const closeNoticesPopupBtn = Array.from(
    document.querySelectorAll(
      ".dd__notices_popup_wrapper .dd__subscription_cancel_btn"
    )
  );

  const noticesPopupWrapper = Array.from(
    document.querySelector(".dd__notices_popup_wrapper")
  );

  if (closeNoticesPopupBtn) {
    document.body.addEventListener("click", function () {
      document.querySelector(".dd__notices_popup_wrapper").style.display =
        "none";
    });

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
