jQuery(document).ready(function ($) {
  let typingTimer;
  const doneTypingInterval = 300; // ms after user stops typing
  const $input = $("#supplement-search-input");
  const $results = $("#supplement-search-results");

  $input.on("keyup", function () {
    clearTimeout(typingTimer);

    const query = $(this).val();

    if (query.length >= 3) {
      typingTimer = setTimeout(function () {
        $.ajax({
          url: supplement_ajax_search_params.ajax_url,
          method: "POST",
          data: {
            action: "supplement_ajax_search",
            keyword: query,
            category_filter: supplement_ajax_search_params.category_filter,
          },
          success: function (response) {
            $results.html(response);
          },
        });
      }, doneTypingInterval);
    } else {
      $results.empty();
    }
  });
});
