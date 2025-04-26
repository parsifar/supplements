jQuery(document).ready(function ($) {
  var searchField = $("#supplement-search");
  var clearButton = $("#supplement-search-clear");
  var resultsContainer = $("#supplement-search-results");

  var selectedResultUrl = "";

  searchField.on("keyup", function (e) {
    var keyword = $(this).val().trim();

    // If user presses Enter and there's only one result, go to it
    if (e.key === "Enter" && selectedResultUrl) {
      window.location.href = selectedResultUrl;
      return;
    }

    // Show or hide clear button
    if (keyword.length > 0) {
      clearButton.show();
    } else {
      clearButton.hide();
      resultsContainer.hide();
      selectedResultUrl = "";
    }

    if (keyword.length >= 3) {
      $.ajax({
        type: "POST",
        url: supplement_ajax_search_params.ajax_url,
        data: {
          action: "supplement_ajax_search",
          keyword: keyword,
          category_filter: supplement_ajax_search_params.category_filter,
        },
        success: function (response) {
          resultsContainer.html(response).fadeIn();

          // If there is exactly one <li>, get its link
          var firstLink = resultsContainer.find("li a").first();
          if (
            resultsContainer.find("li").length === 1 &&
            firstLink.length > 0
          ) {
            selectedResultUrl = firstLink.attr("href");
          } else {
            selectedResultUrl = "";
          }
        },
      });
    } else {
      resultsContainer.hide();
      selectedResultUrl = "";
    }
  });

  clearButton.on("click", function () {
    searchField.val("");
    $(this).hide();
    resultsContainer.hide();
    selectedResultUrl = "";
  });

  // Optional: Hide results if user clicks outside
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".supplement-search-wrapper").length) {
      resultsContainer.hide();
    }
  });
});
