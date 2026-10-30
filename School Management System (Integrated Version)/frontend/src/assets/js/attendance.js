(() => {
  "use strict";

  /* ------------------------------------------------------------------------
     Live Manila clock in the top bar.
     ---------------------------------------------------------------------- */

  const clockDate = document.getElementById("attn-clock-date");
  const clockTime = document.getElementById("attn-clock-time");

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
     Dropdown menus: one open at a time, close on outside click or Escape.
     ---------------------------------------------------------------------- */

  const menus = Array.from(document.querySelectorAll("[data-attn-menu]"));

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
    if (!(event.target instanceof Element)) {
      return;
    }
    if (!event.target.closest("[data-attn-menu]")) {
      closeMenus();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeMenus();
    }
  });

  /* ------------------------------------------------------------------------
     Take Attendance: status chips, live counters, and save-state reminder.
     ---------------------------------------------------------------------- */

  const attendanceForm = document.getElementById("attendance-form");

  if (attendanceForm) {
    const rows = Array.from(attendanceForm.querySelectorAll("[data-attn-row]"));
    const counters = {
      Present: document.getElementById("attn-present"),
      Absent: document.getElementById("attn-absent"),
      Late: document.getElementById("attn-late"),
      total: document.getElementById("attn-total"),
    };

    const chipTone = (value) => String(value || "Unmarked").toLowerCase();

    const recalculate = () => {
      const totals = { Present: 0, Absent: 0, Late: 0, Excused: 0 };

      rows.forEach((row) => {
        const checked = row.querySelector('input[type="radio"]:checked');
        const value = checked ? checked.value : "Unmarked";
        const chip = row.querySelector("[data-attn-chip]");

        if (chip) {
          chip.textContent = checked ? value : "Unmarked";
          chip.className = `attn-pill is-${chipTone(value)}`;
        }

        if (Object.prototype.hasOwnProperty.call(totals, value)) {
          totals[value] += 1;
        }
      });

      if (counters.Present) counters.Present.textContent = String(totals.Present);
      if (counters.Absent) counters.Absent.textContent = String(totals.Absent);
      if (counters.Late) counters.Late.textContent = String(totals.Late);
      if (counters.total) counters.total.textContent = String(rows.length);
    };

    attendanceForm.addEventListener("change", (event) => {
      if (event.target instanceof HTMLInputElement && event.target.type === "radio") {
        recalculate();
      }
    });

    recalculate();

    const saveButton = document.querySelector('button[form="attendance-form"]');
    if (saveButton) {
      attendanceForm.addEventListener("submit", () => {
        saveButton.disabled = true;
        const label = saveButton.querySelector("span");
        if (label) {
          label.textContent = "Saving…";
        }
      });
    }
  }

  /* ------------------------------------------------------------------------
     Filters: submit on change and keep section/year dropdowns in sync.
     ---------------------------------------------------------------------- */

  document.querySelectorAll("[data-attn-auto-submit]").forEach((control) => {
    control.addEventListener("change", () => {
      const form = control.form;
      if (form) {
        form.submit();
      }
    });
  });

  const filterForm = document.querySelector("[data-attn-filters]");

  if (filterForm) {
    const subjectSelect = filterForm.querySelector("[data-attn-subject]");
    const sectionSelect = filterForm.querySelector("[data-attn-section]");
    const yearSelect = filterForm.querySelector("[data-attn-year]");
    const dateInput = filterForm.querySelector("[data-attn-date]");

    const syncSections = () => {
      if (!sectionSelect || !yearSelect) {
        return;
      }
      const year = yearSelect.value;
      let firstVisible = null;
      let selectedVisible = false;

      Array.from(sectionSelect.options).forEach((option) => {
        const matches = !year || option.dataset.year === year;
        option.hidden = !matches;
        option.disabled = !matches;
        if (matches) {
          if (firstVisible === null) {
            firstVisible = option.value;
          }
          if (option.selected) {
            selectedVisible = true;
          }
        }
      });

      if (!selectedVisible && firstVisible !== null) {
        sectionSelect.value = firstVisible;
      }
    };

    if (subjectSelect && yearSelect) {
      subjectSelect.addEventListener("change", () => {
        const option = subjectSelect.selectedOptions[0];
        if (option && option.dataset.year) {
          yearSelect.value = option.dataset.year;
          syncSections();
        }
      });
    }

    if (yearSelect) {
      yearSelect.addEventListener("change", syncSections);
    }

    if (dateInput) {
      dateInput.addEventListener("change", () => {
        if (dateInput.value) {
          filterForm.submit();
        }
      });
    }

    syncSections();
  }

  /* ------------------------------------------------------------------------
     Room monitoring: instant room search.
     ---------------------------------------------------------------------- */

  const roomSearch = document.querySelector("[data-attn-room-search]");

  if (roomSearch) {
    const roomCards = Array.from(document.querySelectorAll("[data-attn-room-card]"));
    const roomEmpty = document.querySelector("[data-attn-room-empty]");
    const roomTotal = document.querySelector("[data-attn-room-total]");
    const roomHall = document.querySelector("[data-attn-room-hall]");
    const searchForm = roomSearch.form;

    const filterRooms = () => {
      const query = roomSearch.value.trim().toLowerCase();
      let shown = 0;

      roomCards.forEach((card) => {
        const key = (card.dataset.roomKey || "").toLowerCase();
        const matches = query === "" || key.includes(query);
        card.hidden = !matches;
        if (matches) {
          shown += 1;
        }
      });

      if (roomEmpty) {
        roomEmpty.hidden = shown !== 0;
      }
      if (roomHall) {
        roomHall.hidden = shown === 0;
      }
      if (roomTotal) {
        roomTotal.textContent = String(shown);
      }
    };

    roomSearch.addEventListener("input", filterRooms);
    roomSearch.addEventListener("search", filterRooms);

    if (searchForm) {
      searchForm.addEventListener("submit", (event) => {
        event.preventDefault();
        filterRooms();
      });
    }

    filterRooms();
  }

  /* ------------------------------------------------------------------------
     Dialog controls and print actions.
     ---------------------------------------------------------------------- */

  document.querySelectorAll("[data-attn-open-dialog]").forEach((button) => {
    button.addEventListener("click", () => {
      const dialog = document.getElementById(button.dataset.attnOpenDialog || "");
      if (!dialog) {
        return;
      }
      if (typeof dialog.showModal === "function") {
        dialog.showModal();
      } else {
        dialog.setAttribute("open", "");
      }
    });
  });

  document.querySelectorAll("[data-attn-close-dialog]").forEach((button) => {
    button.addEventListener("click", () => {
      const dialog = button.closest("dialog");
      if (dialog) {
        dialog.close();
      }
    });
  });

  document.querySelectorAll("dialog").forEach((dialog) => {
    dialog.addEventListener("click", (event) => {
      if (event.target === dialog) {
        dialog.close();
      }
    });
  });

  document.querySelectorAll("[data-attn-print]").forEach((button) => {
    button.addEventListener("click", () => {
      window.print();
    });
  });
})();
