jQuery(function ($) {
  // When the element with ID "confirm-update" is clicked...
  $("#confirm-update").on("click", function () {
    $.post(
      supplementUpdater.ajaxUrl,
      {
        action: "get_supplement_update_data",
        nonce: supplementUpdater.nonce,
      },
      function (response) {
        if (response.success) {
          const { flavor_updates, supplement_updates } = response.data;
          // Get the keys (IDs) from both flavor and supplement update objects
          const flavorUpdates = flavor_updates
            ? Object.keys(flavor_updates)
            : [];

          const supplementUpdates = supplement_updates
            ? Object.keys(supplement_updates)
            : [];

          // Total number of updates to calculate progress
          const totalUpdates = flavorUpdates.length + supplementUpdates.length;
          let completed = 0;

          console.log("flavorUpdates" + flavorUpdates);
          console.log("supplementUpdates" + supplementUpdates);
          console.log("total: " + totalUpdates);

          // Show the progress bar UI
          $("#progress-wrapper").show();

          // Start the update chain
          processFlavorUpdates();

          // Function to update the progress bar UI
          function updateProgress() {
            const percent = Math.round((completed / totalUpdates) * 100);
            $("#progress-fill").css("width", percent + "%");
            $("#progress-text").text(percent + "%");
          }

          // Function to process flavor updates first
          function processFlavorUpdates() {
            if (flavorUpdates.length === 0) {
              processSupplementUpdates(); // Move on to supplement updates
              return;
            }

            const flavor_id = flavorUpdates.shift();

            $.post(
              supplementUpdater.ajaxUrl,
              {
                action: "flavor_updater_update",
                nonce: supplementUpdater.nonce,
                flavor_id: flavor_id,
              },
              function (response) {
                console.log("updated flavor: " + flavor_id);

                completed++;
                updateProgress();
                processFlavorUpdates(); // Continue with next flavor
              }
            );
          }

          // Function to process supplement updates after flavors
          function processSupplementUpdates() {
            if (supplementUpdates.length === 0) {
              $("#progress-text").text("Completed");
              return;
            }

            const supplement_id = supplementUpdates.shift();

            $.post(
              supplementUpdater.ajaxUrl,
              {
                action: "supplement_updater_update",
                nonce: supplementUpdater.nonce,
                supplement_id: supplement_id,
              },
              function (response) {
                console.log("updated supplement: " + supplement_id);

                completed++;
                updateProgress();
                processSupplementUpdates(); // Continue with next supplement
              }
            );
          }
        } else {
          console.error(response.data.message);
        }
      }
    );
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
            '<table class="widefat"><thead><tr><th>Title</th><th>Last updated</th></tr></thead><tbody>';
          response.data.forEach(function (post) {
            html += `<tr><td><a href="${post.edit_link}" target="_blank">${post.title}</a></td><td>${post.last_update_date}</td></tr>`;
          });
          html += "</tbody></table>";
          $("#missing-supplements-table").html(html);
        }
      }
    );
  });
});
