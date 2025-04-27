jQuery(function ($) {
  $("#confirm-update").on("click", function () {
    const updates = supplementUpdater.updates
      ? Object.keys(supplementUpdater.updates)
      : [];
    let completed = 0;

    $("#progress-wrapper").show();

    function updateNext() {
      if (updates.length === 0) {
        $("#progress-text").text("Completed");
        return;
      }

      const asin = updates.shift();

      $.post(
        supplementUpdater.ajaxUrl,
        {
          action: "supplement_updater_update",
          nonce: supplementUpdater.nonce,
          asin: asin,
        },
        function (response) {
          completed++;
          const percent = Math.round(
            (completed / supplementUpdater.total) * 100
          );
          $("#progress-fill").css("width", percent + "%");
          $("#progress-text").text(percent + "%");

          updateNext();
        }
      );
    }

    updateNext();
  });

  $("#supplement-category-dropdown").on("change", function () {
    const categoryId = $(this).val();
    if (!categoryId) return;

    $.post(
      supplementUpdater.ajaxUrl,
      {
        action: "get_missing_supplements",
        nonce: supplementUpdater.nonce,
        category_id: categoryId,
      },
      function (response) {
        if (response.success) {
          let html =
            '<table class="widefat"><thead><tr><th>Title</th><th>ASIN</th></tr></thead><tbody>';
          response.data.forEach(function (post) {
            html += `<tr><td><a href="${post.edit_link}" target="_blank">${post.title}</a></td><td>${post.asin}</td></tr>`;
          });
          html += "</tbody></table>";
          $("#missing-supplements-table").html(html);
        }
      }
    );
  });
});
