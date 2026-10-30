(() => {
  "use strict";

  /* ------------------------------------------------------------------------
     Live Manila clock.
     ---------------------------------------------------------------------- */

  const clockDate = document.getElementById("enr-clock-date");
  const clockTime = document.getElementById("enr-clock-time");

  if (clockDate || clockTime) {
    const dateFormatter = new Intl.DateTimeFormat("en-PH", {
      weekday: "short",
      month: "short",
      day: "numeric",
      timeZone: "Asia/Manila",
    });
    const timeFormatter = new Intl.DateTimeFormat("en-PH", {
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
      timeZone: "Asia/Manila",
    });

    const tick = () => {
      const now = new Date();
      if (clockDate) {
        clockDate.textContent = dateFormatter.format(now);
      }
      if (clockTime) {
        clockTime.textContent = `${timeFormatter.format(now)} PHT`;
      }
    };

    tick();
    window.setInterval(tick, 15000);
  }

  /* ------------------------------------------------------------------------
     Dropdown menus.
     ---------------------------------------------------------------------- */

  const menus = Array.from(document.querySelectorAll("[data-enr-menu]"));

  const closeMenus = (except) => {
    menus.forEach((menu) => {
      if (menu !== except) {
        menu.removeAttribute("open");
      }
    });
  };

  menus.forEach((menu) => {
    menu.addEventListener("toggle", () => {
      if (menu.open) {
        closeMenus(menu);
      }
    });
  });

  document.addEventListener("click", (event) => {
    if (event.target instanceof Element && !event.target.closest("[data-enr-menu]")) {
      closeMenus();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeMenus();
    }
  });

  /* ------------------------------------------------------------------------
     "Before you begin" checklist, remembered per device.
     ---------------------------------------------------------------------- */

  const checklist = document.querySelector("[data-enr-checklist]");
  const checklistCount = document.querySelector("[data-enr-checklist-count]");
  const storageKey = "fbc.enrollment.checklist";

  if (checklist) {
    let saved = [];
    try {
      const raw = window.localStorage.getItem(storageKey);
      saved = raw ? JSON.parse(raw) : [];
    } catch (error) {
      saved = [];
    }

    if (!Array.isArray(saved)) {
      saved = [];
    }

    const boxes = Array.from(checklist.querySelectorAll('input[type="checkbox"]'));

    const updateCount = () => {
      const checked = boxes.filter((box) => box.checked).length;
      if (checklistCount) {
        checklistCount.textContent = `${checked}/${boxes.length}`;
      }
    };

    boxes.forEach((box) => {
      box.checked = saved.includes(box.value);
      box.addEventListener("change", () => {
        const values = boxes.filter((item) => item.checked).map((item) => item.value);
        try {
          window.localStorage.setItem(storageKey, JSON.stringify(values));
        } catch (error) {
          /* storage may be unavailable in private mode */
        }
        updateCount();
      });
    });

    updateCount();
  }

  /* ------------------------------------------------------------------------
     Application form helpers.
     ---------------------------------------------------------------------- */

  const form = document.querySelector(".enr-form");

  if (form) {
    const sameAddress = form.querySelector('input[name="same_address"]');
    const permanent = form.querySelector('input[name="permanent_address"]');
    const current = form.querySelector('input[name="current_address"]');

    const syncAddress = () => {
      if (!sameAddress || !permanent || !current) {
        return;
      }
      if (sameAddress.checked) {
        current.value = permanent.value;
      }
    };

    if (sameAddress) {
      sameAddress.addEventListener("change", syncAddress);
    }
    if (permanent) {
      permanent.addEventListener("input", syncAddress);
    }

    const validate = () => {
      const required = [
        ["first_name", "Enter your first name."],
        ["last_name", "Enter your last name."],
        ["email", "Enter your email address."],
        ["birthday", "Enter your birthday."],
        ["contact_no", "Enter your contact number."],
        ["emergency_contact_no", "Enter an emergency contact number."],
        ["program", "Choose a program."],
      ];

      const problems = [];

      required.forEach(([name, message]) => {
        const field = form.elements.namedItem(name);
        if (!(field instanceof HTMLElement)) {
          return;
        }
        const value = field.value.trim();
        field.removeAttribute("aria-invalid");
        if (value === "") {
          field.setAttribute("aria-invalid", "true");
          problems.push({ field, message });
        }
      });

      const email = form.elements.namedItem("email");
      if (email instanceof HTMLInputElement && email.value.trim() !== "" && !email.validity.valid) {
        email.setAttribute("aria-invalid", "true");
        problems.push({ field: email, message: "Enter a valid email address." });
      }

      let summary = form.querySelector("[data-enr-form-summary]");

      if (problems.length > 0) {
        if (!summary) {
          summary = document.createElement("div");
          summary.className = "enr-notice enr-notice--danger";
          summary.setAttribute("role", "alert");
          summary.setAttribute("data-enr-form-summary", "");
          form.prepend(summary);
        }

        summary.innerHTML = "";

        const icon = document.createElement("span");
        icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>';
        summary.appendChild(icon.firstChild);

        const copy = document.createElement("div");
        const title = document.createElement("strong");
        title.textContent = "Please review your details.";
        const list = document.createElement("p");
        list.textContent = problems.map((problem) => problem.message).join(" ");
        copy.append(title, list);
        summary.appendChild(copy);

        problems[0].field.focus();
        summary.scrollIntoView({ block: "center", behavior: "smooth" });

        return false;
      }

      if (summary) {
        summary.remove();
      }

      return true;
    };

    form.addEventListener("submit", (event) => {
      if (!validate()) {
        event.preventDefault();
        return;
      }

      const button = form.querySelector('button[type="submit"]');
      if (button) {
        button.disabled = true;
        const label = button.querySelector("span");
        if (label) {
          label.textContent = "Submitting…";
        }
      }
    });

    form.addEventListener("input", (event) => {
      if (event.target instanceof HTMLElement) {
        event.target.removeAttribute("aria-invalid");
      }
    });
  }
})();
