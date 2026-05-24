// FurryNeeds shared placeholder. Keeps pages from failing when main.js is loaded.
(function () {
  if (typeof window.showToast !== 'function') {
    window.showToast = function (message) { alert(message); };
  }
})();
