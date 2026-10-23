(function () {
  document.querySelectorAll("form").forEach(function (form) {
    form.addEventListener("submit", function () {
      var button = form.querySelector("button[type='submit'], input[type='submit']");
      if (!button || button.getAttribute("aria-busy") === "true") {
        return;
      }
      button.setAttribute("aria-busy", "true");
      var label = button.getAttribute("data-busy-label");
      if (label) {
        button.textContent = label;
      }
    });
  });

  document.querySelectorAll("[data-print]").forEach(function (button) {
    button.addEventListener("click", function () { window.print(); });
  });

  var calc = document.querySelector("[data-calculator]");
  if (!calc) return;
  var units = calc.querySelector("[name=units]");
  var rate = calc.querySelector("[name=opt_in]");
  var share = calc.querySelector("[name=share]");
  var outResidents = calc.querySelector("[data-out=residents]");
  var outDoors = calc.querySelector("[data-out=doors]");
  var outKeep = calc.querySelector("[data-out=keep]");
  var outRemit = calc.querySelector("[data-out=remit]");
  var price = Number(calc.getAttribute("data-price") || "35");

  function money(n) {
    return n.toLocaleString("en-US", { style: "currency", currency: "USD" });
  }

  function render() {
    var u = Math.max(0, Number(units.value || 0));
    var pct = Math.min(100, Math.max(0, Number(rate.value || 0)));
    var cut = Math.min(100, Math.max(0, Number(share.value || 0)));
    var doors = u * (pct / 100);
    var gross = doors * price;
    var keep = gross * (cut / 100);
    outDoors.textContent = (Math.round(doors * 10) / 10).toString();
    outResidents.textContent = money(gross);
    outKeep.textContent = money(keep);
    outRemit.textContent = money(gross - keep);
  }

  ["input", "change"].forEach(function (evt) {
    units.addEventListener(evt, render);
    rate.addEventListener(evt, render);
    share.addEventListener(evt, render);
  });
  render();
})();
