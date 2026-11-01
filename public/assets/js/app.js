(function () {
  document.querySelectorAll("[data-print]").forEach(function (button) {
    button.addEventListener("click", function () { window.print(); });
  });

  document.querySelectorAll("[data-jump-value]").forEach(function (select) {
    select.addEventListener("change", function () {
      if (select.value === select.getAttribute("data-jump-value")) {
        window.location.href = select.getAttribute("data-jump-href") || "/junk-pickup";
      }
    });
  });

  var calc = document.querySelector("[data-calculator]");
  if (calc) {
    var units = calc.querySelector("[name=units]");
    var rate = calc.querySelector("[name=opt_in]");
    var share = calc.querySelector("[name=share]");
    var outResidents = calc.querySelector("[data-out=residents]");
    var outDoors = calc.querySelector("[data-out=doors]");
    var outKeep = calc.querySelector("[data-out=keep]");
    var outRemit = calc.querySelector("[data-out=remit]");
    var price = Number(calc.getAttribute("data-price") || "20");

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
  }

  var junk = document.querySelector("[data-junk-calculator]");
  if (!junk) return;
  var book = {};
  try {
    book = JSON.parse(junk.getAttribute("data-rates") || "{}");
  } catch (err) {
    book = {};
  }
  var out = junk.querySelector("[data-junk-estimate]");
  if (!out) return;

  function pairFor(group, code) {
    var section = book[group] || {};
    var pair = section[code];
    if (!pair || typeof pair.low !== "number" || typeof pair.high !== "number") return null;
    return pair;
  }

  function cents(amount) {
    return (amount / 100).toLocaleString("en-US", { style: "currency", currency: "USD" });
  }

  function selectedMode() {
    var picked = junk.querySelector("[name=mode]:checked");
    return picked ? picked.value : "items";
  }

  function renderJunk() {
    var mode = selectedMode();
    var low = 0;
    var high = 0;
    var any = false;
    var missing = false;
    if (mode === "load") {
      var load = junk.querySelector("[name=load_size]:checked");
      if (!load) {
        out.textContent = "Choose items or a load size. The team will follow up with an estimate.";
        return;
      }
      any = true;
      var loadPair = pairFor("loads", load.value);
      if (!loadPair) missing = true;
      else {
        low = loadPair.low;
        high = loadPair.high;
      }
    } else {
      junk.querySelectorAll("[data-junk-qty]").forEach(function (input) {
        var qty = Math.max(0, Number(input.value || 0));
        if (qty < 1) return;
        any = true;
        var itemPair = pairFor("items", input.getAttribute("data-junk-qty"));
        if (!itemPair) missing = true;
        else {
          low += itemPair.low * qty;
          high += itemPair.high * qty;
        }
      });
    }
    if (!any || missing) {
      out.textContent = any
        ? "The team will follow up with an estimate."
        : "Choose items or a load size. The team will follow up with an estimate.";
      return;
    }
    var shown = low === high ? cents(low) : cents(low) + " to " + cents(high);
    out.textContent = "Estimate only, final price confirmed on site. " + shown + ".";
  }

  junk.addEventListener("input", renderJunk);
  junk.addEventListener("change", renderJunk);
  renderJunk();
})();
