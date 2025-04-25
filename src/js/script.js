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
