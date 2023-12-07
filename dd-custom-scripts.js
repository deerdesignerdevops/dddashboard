console.log("dd-custom-scripts.js");
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
        console.log("closeNoticesPopupBtn");
        e.preventDefault();
        noticesPopupWrapper.map((popup) => {
          popup.style.display = "none";
        });
      });
    });
  }
});
