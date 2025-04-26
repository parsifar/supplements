// Rating Bars
document.querySelectorAll(".rating-bar").forEach((bar) => {
  const rating = parseFloat(bar.getAttribute("data-rating"));
  const barFill = bar.querySelector(".bar-fill");

  // Segment widths in percentage (total 100%)
  const segments = [
    { min: 0, max: 1, width: 14.2857 },
    { min: 1, max: 2, width: 14.2857 },
    { min: 2, max: 3, width: 14.2857 },
    { min: 3, max: 4, width: 14.2857 },
    { min: 4, max: 5, width: 42.8571 },
  ];

  let fillPercent = 0;
  for (let i = 0; i < segments.length; i++) {
    const seg = segments[i];
    if (rating >= seg.max) {
      fillPercent += seg.width;
    } else if (rating > seg.min) {
      const portion = (rating - seg.min) / (seg.max - seg.min);
      fillPercent += seg.width * portion;
      break;
    } else {
      break;
    }
  }

  barFill.style.width = `${fillPercent}%`;
});

// Compare Bar
(function () {
  const maxCompare = 4;
  const compareBar = document.getElementById("compare-bar");
  const compareLink = document.getElementById("compare-link");
  const compareMessage = document.getElementById("compare-message");
  const compareList = document.getElementById("compare-list");
  const compareWrapper = document.getElementById("compare-list-wrapper");
  const toggleListBtn = document.getElementById("toggle-compare-list");
  const removeAllBtn = document.getElementById("remove-all");

  let selectedIds = JSON.parse(localStorage.getItem("compareIds") || "[]");

  function saveState() {
    localStorage.setItem("compareIds", JSON.stringify(selectedIds));
  }

  function updateUI() {
    if (selectedIds.length > 0) {
      compareBar.style.display = "block";
      compareMessage.textContent = `${selectedIds.length} product${
        selectedIds.length > 1 ? "s" : ""
      } selected for comparison`;
      compareLink.href = `/compare/?ids=${selectedIds.join(",")}`;
    } else {
      compareBar.style.display = "none";
      compareWrapper.classList.remove("open");
      toggleListBtn.textContent = "Show selected products";
    }

    compareList.innerHTML = "";
    selectedIds.forEach((id) => {
      const li = document.createElement("li");
      const title =
        localStorage.getItem(`compareTitle-${id}`) || `Product ${id}`;
      li.textContent = title;
      const removeBtn = document.createElement("button");
      removeBtn.textContent = "×";
      removeBtn.setAttribute("data-id", id);
      removeBtn.addEventListener("click", () => {
        selectedIds = selectedIds.filter((i) => i !== id);
        saveState();
        updateUI();
        updateCheckboxes();
      });
      li.appendChild(removeBtn);
      compareList.appendChild(li);
    });
  }

  function updateCheckboxes() {
    document.querySelectorAll(".compare-checkbox").forEach((box) => {
      box.checked = selectedIds.includes(box.value);
    });
  }

  document.addEventListener("change", function (e) {
    if (!e.target.classList.contains("compare-checkbox")) return;
    const id = e.target.value;

    if (e.target.checked) {
      if (selectedIds.length < maxCompare) {
        selectedIds.push(id);
        localStorage.setItem(`compareTitle-${id}`, e.target.dataset.title);
      } else {
        e.target.checked = false;
        alert(`You can only compare up to ${maxCompare} products.`);
      }
    } else {
      selectedIds = selectedIds.filter((i) => i !== String(id));
      localStorage.removeItem(`compareTitle-${id}`);
    }
    saveState();
    updateUI();
  });

  toggleListBtn.addEventListener("click", () => {
    const isOpen = compareWrapper.classList.toggle("open");
    toggleListBtn.textContent = isOpen
      ? "Hide selected products"
      : "Show selected products";
  });

  removeAllBtn.addEventListener("click", () => {
    const oldIds = [...selectedIds]; // clone before clearing
    selectedIds = [];
    oldIds.forEach((id) => localStorage.removeItem(`compareTitle-${id}`));
    saveState();
    updateUI();
    updateCheckboxes();
  });

  // Init
  updateUI();
  updateCheckboxes();
})();
