/* ═══════════════════════════════════════════════════════════════
   Niloo Web Pro — Unified Theme JavaScript (Zero dependencies)
   Combines new design interactions + WordPress LMS/Panel/Blog UX
   ═══════════════════════════════════════════════════════════════ */
(function () {
  "use strict";

  var d = document;
  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function toEnDigits(s) {
    return String(s)
      .replace(/[۰-۹]/g, function (c) { return String("۰۱۲۳۴۵۶۷۸۹".indexOf(c)); })
      .replace(/[٠-٩]/g, function (c) { return String("٠١٢٣٤٥٦٧٨٩".indexOf(c)); });
  }
  function isEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v); }
  function isPhone(v) { return /^09\d{9}$/.test(toEnDigits(v).replace(/\s/g, "")); }

  /* ---------- Toast (bottom-center, ~4s) ---------- */
  var toastTimer = null;
  window.toast = function (msg, type) {
    var box = d.getElementById("toast");
    if (!box) {
      box = d.createElement("div");
      box.id = "toast";
      box.className = "toast";
      box.setAttribute("role", "status");
      d.body.appendChild(box);
    }
    box.textContent = "";
    var dot = d.createElement("span");
    dot.className = "toast-dot " + (type === "error" ? "toast-dot-error" : "toast-dot-ok");
    box.appendChild(dot);
    box.appendChild(d.createTextNode(msg));
    box.classList.add("is-show");
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { box.classList.remove("is-show"); }, 4000);
  };

  /* ---------- Sticky Header Scroll State ---------- */
  var header = d.getElementById("site-header");
  if (header) {
    var onScroll = function () {
      header.classList.toggle("is-scrolled", window.scrollY > 12);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  /* ---------- Header Search Toggle ---------- */
  var searchToggle = d.getElementById("searchToggle");
  var searchPanel = d.getElementById("searchPanel");
  var searchClose = d.getElementById("searchClose");
  if (searchToggle && searchPanel) {
    searchToggle.addEventListener("click", function () {
      var nextHidden = !searchPanel.hidden;
      searchPanel.hidden = nextHidden;
      searchToggle.setAttribute("aria-expanded", String(!nextHidden));
      if (!nextHidden) {
        var inp = searchPanel.querySelector("input");
        if (inp) inp.focus();
      }
    });
    if (searchClose) {
      searchClose.addEventListener("click", function () {
        searchPanel.hidden = true;
        searchToggle.setAttribute("aria-expanded", "false");
        searchToggle.focus();
      });
    }
    d.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && !searchPanel.hidden) {
        searchPanel.hidden = true;
        searchToggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  /* ---------- Mobile Menu Drawer ---------- */
  var mobileBtn = d.getElementById("mobile-toggle");
  var mobileMenu = d.getElementById("mobile-menu");
  function closeMobile() {
    if (!mobileMenu || !mobileBtn) return;
    mobileMenu.classList.remove("is-open");
    mobileBtn.setAttribute("aria-expanded", "false");
    mobileBtn.setAttribute("aria-label", "باز کردن منو");
    d.body.style.overflow = "";
    var icOpen = mobileBtn.querySelector("[data-icon-open]");
    var icClose = mobileBtn.querySelector("[data-icon-close]");
    if (icOpen) icOpen.hidden = false;
    if (icClose) icClose.hidden = true;
  }
  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener("click", function () {
      var open = mobileMenu.classList.toggle("is-open");
      mobileBtn.setAttribute("aria-expanded", String(open));
      mobileBtn.setAttribute("aria-label", open ? "بستن منو" : "باز کردن منو");
      d.body.style.overflow = open ? "hidden" : "";
      var icOpen = mobileBtn.querySelector("[data-icon-open]");
      var icClose = mobileBtn.querySelector("[data-icon-close]");
      if (icOpen) icOpen.hidden = open;
      if (icClose) icClose.hidden = !open;
    });
    mobileMenu.addEventListener("click", function (e) {
      if (e.target.closest("a")) closeMobile();
    });
    d.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && mobileMenu.classList.contains("is-open")) {
        closeMobile();
        mobileBtn.focus();
      }
    });
  }

  /* ---------- Desktop Mega Menu (hover + click + keyboard) ---------- */
  var megaWrap = d.getElementById("mega-wrap");
  if (megaWrap) {
    var megaBtn = d.getElementById("mega-toggle");
    var megaPanel = d.getElementById("courses-mega");
    var megaTimer = null;
    function megaOpen() {
      if (!megaPanel || !megaBtn) return;
      if (megaTimer) { clearTimeout(megaTimer); megaTimer = null; }
      megaPanel.classList.add("is-open");
      megaBtn.setAttribute("aria-expanded", "true");
    }
    function megaScheduleClose(delay) {
      if (!megaPanel || !megaBtn) return;
      if (megaTimer) clearTimeout(megaTimer);
      megaTimer = setTimeout(function () {
        megaPanel.classList.remove("is-open");
        megaBtn.setAttribute("aria-expanded", "false");
      }, delay || 150);
    }
    megaWrap.addEventListener("mouseenter", megaOpen);
    megaWrap.addEventListener("mouseleave", function () { megaScheduleClose(150); });
    if (megaBtn && megaPanel) {
      megaBtn.addEventListener("click", function () {
        var open = megaPanel.classList.toggle("is-open");
        megaBtn.setAttribute("aria-expanded", String(open));
      });
      megaBtn.addEventListener("keydown", function (e) {
        if (e.key === "ArrowDown") { e.preventDefault(); megaOpen(); }
      });
      d.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && megaPanel.classList.contains("is-open")) {
          megaPanel.classList.remove("is-open");
          megaBtn.setAttribute("aria-expanded", "false");
          megaBtn.focus();
        }
      });
      d.addEventListener("click", function (e) {
        if (!megaWrap.contains(e.target)) {
          megaPanel.classList.remove("is-open");
          megaBtn.setAttribute("aria-expanded", "false");
        }
      });
      megaPanel.addEventListener("mouseenter", megaOpen);
      megaPanel.addEventListener("mouseleave", function () { megaScheduleClose(150); });
    }
  }

  /* ---------- ARIA Tabs (RTL-aware) ---------- */
  d.querySelectorAll("[data-tabs]").forEach(function (root) {
    var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
    var panels = tabs.map(function (t) { return d.getElementById(t.getAttribute("aria-controls")); });

    function select(i, focus) {
      tabs.forEach(function (t, j) {
        var on = i === j;
        t.setAttribute("aria-selected", String(on));
        t.tabIndex = on ? 0 : -1;
        if (t.dataset.activeClass && t.dataset.inactiveClass) {
          var add = (on ? t.dataset.activeClass : t.dataset.inactiveClass).split(" ").filter(Boolean);
          var rem = (on ? t.dataset.inactiveClass : t.dataset.activeClass).split(" ").filter(Boolean);
          rem.forEach(function (c) { t.classList.remove(c); });
          add.forEach(function (c) { t.classList.add(c); });
        }
        if (panels[j]) {
          panels[j].hidden = !on;
          if (on && !reduceMotion) {
            panels[j].classList.remove("tabpanel-anim");
            void panels[j].offsetWidth;
            panels[j].classList.add("tabpanel-anim");
          }
        }
      });
      if (focus && tabs[i]) tabs[i].focus();
    }

    tabs.forEach(function (t, i) {
      t.addEventListener("click", function () { select(i, false); });
      t.addEventListener("keydown", function (e) {
        var next = null;
        if (e.key === "ArrowLeft") next = (i + 1) % tabs.length;
        else if (e.key === "ArrowRight") next = (i - 1 + tabs.length) % tabs.length;
        else if (e.key === "Home") next = 0;
        else if (e.key === "End") next = tabs.length - 1;
        if (next !== null) { e.preventDefault(); select(next, true); }
      });
    });
  });

  /* ---------- Interactive Course Guide (courses archive) ---------- */
  d.querySelectorAll("[data-course-guide]").forEach(function (guide) {
    var buttons = Array.prototype.slice.call(guide.querySelectorAll("[data-guide-target]"));
    var cards = Array.prototype.slice.call(guide.querySelectorAll("[data-guide-card]"));
    buttons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var target = btn.getAttribute("data-guide-target");
        buttons.forEach(function (b) {
          var active = b === btn;
          b.setAttribute("aria-pressed", String(active));
          b.classList.toggle("bg-violet-400", active);
          b.classList.toggle("text-ink-950", active);
          b.classList.toggle("border-violet-400", active);
          b.classList.toggle("border-ink-600", !active);
          b.classList.toggle("text-cream", !active);
        });
        cards.forEach(function (card) {
          var match = card.getAttribute("data-guide-card") === target;
          card.hidden = !match;
        });
      });
    });
  });

  /* ---------- Interactive Courses Filter (category + level) ---------- */
  var catalogRoot = d.querySelector("[data-courses-catalog]");
  if (catalogRoot) {
    var activeCat = "all";
    var activeLevel = "all";
    var catBtns = Array.prototype.slice.call(catalogRoot.querySelectorAll("[data-filter-cat]"));
    var levelBtns = Array.prototype.slice.call(catalogRoot.querySelectorAll("[data-filter-level]"));
    var courseCards = Array.prototype.slice.call(catalogRoot.querySelectorAll("[data-course-card]"));
    var countEl = catalogRoot.querySelector("[data-course-count]");
    var emptyEl = catalogRoot.querySelector("[data-course-empty]");

    function toFaDigits(n) {
      return String(n).replace(/\d/g, function (dgt) { return "۰۱۲۳۴۵۶۷۸۹"[Number(dgt)]; });
    }

    function applyFilter() {
      var shown = 0;
      courseCards.forEach(function (card) {
        var cats = (card.getAttribute("data-cats") || "").split(",").filter(Boolean);
        var lvl = (card.getAttribute("data-level") || "").trim();
        var isFree = card.getAttribute("data-free") === "1";
        var catMatch = activeCat === "all"
          || (activeCat === "free" && isFree)
          || cats.indexOf(activeCat) !== -1;
        var levelMatch = activeLevel === "all" || lvl === activeLevel;
        var ok = catMatch && levelMatch;
        card.hidden = !ok;
        if (ok) shown++;
      });
      if (countEl) {
        countEl.textContent = toFaDigits(shown) + " دوره نمایش داده شده";
      }
      if (emptyEl) {
        emptyEl.hidden = shown > 0;
      }
    }

    catBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        activeCat = btn.getAttribute("data-filter-cat") || "all";
        catBtns.forEach(function (b) {
          var on = b === btn;
          b.setAttribute("aria-pressed", String(on));
          b.classList.toggle("bg-ink-900", on);
          b.classList.toggle("text-cream", on);
          b.classList.toggle("bg-sand", !on);
          b.classList.toggle("text-foreground/70", !on);
        });
        applyFilter();
      });
    });

    levelBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        activeLevel = btn.getAttribute("data-filter-level") || "all";
        levelBtns.forEach(function (b) {
          var on = b === btn;
          b.setAttribute("aria-pressed", String(on));
          b.classList.toggle("bg-violet-400", on);
          b.classList.toggle("text-ink-950", on);
          b.classList.toggle("bg-sand", !on);
          b.classList.toggle("text-foreground/70", !on);
        });
        applyFilter();
      });
    });
  }

  /* ---------- Accordions ---------- */
  d.querySelectorAll(".acc-toggle").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var item = btn.closest(".acc");
      if (!item) return;
      var open = item.classList.toggle("is-open");
      btn.setAttribute("aria-expanded", String(open));
    });
  });

  /* ---------- Scroll Reveal ---------- */
  var revealEls = d.querySelectorAll(".reveal");
  if (reduceMotion || !("IntersectionObserver" in window)) {
    revealEls.forEach(function (el) { el.classList.add("is-in"); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add("is-in");
          io.unobserve(en.target);
        }
      });
    }, { rootMargin: "-40px 0px", threshold: 0.04 });
    revealEls.forEach(function (el) { io.observe(el); });
  }

  /* ---------- Inline Persian Form Validation (non-blocking for valid WP forms) ---------- */
  var MESSAGES = {
    required: "این فیلد الزامی است.",
    email: "یک ایمیل معتبر وارد کن.",
    phone: "شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.",
    min: "متن کوتاه است؛ حداقل {n} کاراکتر وارد کن.",
    select: "یک گزینه انتخاب کن."
  };

  function fieldError(field, key, n) {
    var msg = (MESSAGES[key] || "").replace("{n}", n);
    var wrap = field.closest("[data-field]") || field.parentElement;
    var err = wrap ? wrap.querySelector(".field-error") : null;
    field.setAttribute("aria-invalid", "true");
    if (err) { err.textContent = msg; err.hidden = false; }
  }
  function clearError(field) {
    var wrap = field.closest("[data-field]") || field.parentElement;
    var err = wrap ? wrap.querySelector(".field-error") : null;
    field.removeAttribute("aria-invalid");
    if (err) { err.hidden = true; }
  }
  function validateField(field) {
    var rules = (field.getAttribute("data-rules") || "").split(" ").filter(Boolean);
    var v = (field.value || "").trim();
    for (var i = 0; i < rules.length; i++) {
      var r = rules[i];
      if (r === "required" && !v) { fieldError(field, "required"); return false; }
      if (r === "email" && v && !isEmail(v)) { fieldError(field, "email"); return false; }
      if (r === "phone" && v && !isPhone(v)) { fieldError(field, "phone"); return false; }
      if (r === "phone-strict" && !isPhone(v)) { fieldError(field, "phone"); return false; }
      if (r.indexOf("min:") === 0 && v && v.length < parseInt(r.slice(4), 10)) {
        fieldError(field, "min", parseInt(r.slice(4), 10)); return false;
      }
      if (r === "select" && (!v || v === "")) { fieldError(field, "select"); return false; }
      if (r === "email-or-phone" && v && !isEmail(v) && !isPhone(v)) {
        fieldError(field, "email"); return false;
      }
    }
    clearError(field);
    return true;
  }

  d.querySelectorAll("form[data-validate]").forEach(function (form) {
    var fields = Array.prototype.slice.call(form.querySelectorAll("[data-rules]"));
    fields.forEach(function (f) {
      f.addEventListener("input", function () { if (f.getAttribute("aria-invalid")) validateField(f); });
      f.addEventListener("change", function () { if (f.getAttribute("aria-invalid")) validateField(f); });
      f.addEventListener("blur", function () { validateField(f); });
    });
    form.addEventListener("submit", function (e) {
      var ok = true;
      fields.forEach(function (f) { if (!validateField(f)) ok = false; });
      if (!ok) {
        e.preventDefault();
        var firstBad = form.querySelector('[aria-invalid="true"]');
        if (firstBad) firstBad.focus();
      }
    });
  });

  /* ---------- Copy SpotPlayer / Product License Key ---------- */
  d.querySelectorAll("[data-copy-license]").forEach(function (button) {
    button.addEventListener("click", function () {
      var row = button.closest("[data-license-row]") || button.closest(".license-key-row") || button.parentElement;
      var code = row ? row.querySelector("[data-license-key]") : null;
      var text = code ? code.textContent.trim() : (button.getAttribute("data-copy-license") || "");
      if (!text) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
          var original = button.textContent;
          button.textContent = "کپی شد ✓";
          window.toast("کلید لایسنس کپی شد.", "ok");
          setTimeout(function () { button.textContent = original; }, 1800);
        });
      }
    });
  });

  /* ---------- Ticket File Picker & Submit Guard ---------- */
  d.querySelectorAll("[data-ticket-file]").forEach(function (picker) {
    var input = picker.querySelector('input[type="file"]');
    var label = picker.querySelector(".ticket-file-text");
    var max = parseInt(picker.dataset.max || "0", 10);
    if (!input) return;
    input.addEventListener("change", function () {
      var file = input.files && input.files[0];
      if (file && max && file.size > max) {
        window.toast("حجم فایل بیشتر از حد مجاز است.", "error");
        input.value = "";
      }
      var current = input.files && input.files[0];
      picker.classList.toggle("has-file", !!current);
      if (label) label.textContent = current ? current.name : (label.dataset.empty || "انتخاب فایل");
    });
  });

  d.querySelectorAll("[data-ticket-form]").forEach(function (form) {
    form.addEventListener("submit", function () {
      var button = form.querySelector('button[type="submit"]');
      if (!button) return;
      setTimeout(function () { button.disabled = true; button.textContent = "در حال ارسال…"; }, 0);
    });
  });

  /* ---------- Article Reading Progress & TOC ---------- */
  var progressBar = d.querySelector("#readingProgress > span");
  var articleBody = d.querySelector(".article-body");
  if (progressBar && articleBody) {
    var updateProgress = function () {
      var rect = articleBody.getBoundingClientRect();
      var total = Math.max(1, articleBody.offsetHeight - window.innerHeight * 0.6);
      var pct = Math.min(100, Math.max(0, (-rect.top / total) * 100));
      progressBar.style.width = pct.toFixed(1) + "%";
    };
    updateProgress();
    window.addEventListener("scroll", updateProgress, { passive: true });
  }

  /* ---------- Expandable Long Course Prose ---------- */
  d.querySelectorAll("[data-prose-toggle]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var box = btn.previousElementSibling;
      if (!box || !box.hasAttribute("data-prose-clamp")) return;
      var expanded = box.classList.toggle("is-expanded");
      btn.setAttribute("aria-expanded", String(expanded));
      btn.textContent = expanded ? "بستن توضیحات اضافه" : "مشاهده توضیحات کامل دوره";
    });
  });

  /* ---------- Flash URL Toast (newsletter / contact) ---------- */
  var params = new URLSearchParams(window.location.search);
  var nl = params.get("newsletter");
  if (nl === "ok") window.toast("عضویتت در خبرنامه با موفقیت ثبت شد!", "ok");
  else if (nl === "invalid") window.toast("ایمیل واردشده معتبر نیست.", "error");
  else if (nl === "limit") window.toast("تعداد تلاش‌ها زیاد است؛ کمی بعد دوباره امتحان کن.", "error");
  var ct = params.get("contact");
  if (ct === "ok") window.toast("پیامت با موفقیت ثبت شد؛ خیلی زود پاسخ می‌دم.", "ok");
  else if (ct === "error") window.toast("لطفاً همه فیلدهای الزامی را به درستی پر کن.", "error");
})();
