jQuery(document).ready(function ($) {
  let engagementChart = null;
  let topPostsChart = null;

  /**
   * ✅ REFRESH CHART DATA
   */
  function refreshChartData() {
    const days = $("#pem-date-range").val();
    const sort = $("#pem-sort-by").val();

    $.ajax({
      url: pemAdmin.ajaxurl,
      method: "POST",
      data: {
        action: "pem_filter_data", // Make sure this matches your PHP handler
        nonce: pemAdmin.nonce,
        days: days === "all" ? 0 : days, // Handle "all time"
        sort: sort,
      },
      success: function (response) {
        if (response.success) {
          updateEngagementChart(response.data.stats);
          updateTopPostsChart(response.data.topPosts);
          updateOverviewCards(response.data.stats, response.data.topPosts);
        } else {
          console.error("Failed to fetch updated data");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", error);
      },
    });
  }

  // Add event listeners to filters
  $("#pem-date-range, #pem-sort-by").on("change", refreshChartData);
  $("#pem-refresh-data").on("click", refreshChartData);

  /**
   * ✅ COPY SHORTCODE FUNCTIONALITY
   */
  $(".pem-copy-shortcode").on("click", function () {
    const postId = $(this).data("post-id");
    const shortcode = `[pem_post id="${postId}"]`;

    const $temp = $("<textarea>").val(shortcode).appendTo("body").select();
    try {
      document.execCommand("copy");
      const $btn = $(this);
      const original = $btn.html();
      $btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
      setTimeout(() => $btn.html(original), 2000);
    } catch (err) {
      console.error("❌ Failed to copy shortcode:", err);
    }
    $temp.remove();
  });

  /**
   * ✅ SAFELY PARSE LOCALIZED DATA
   */
  const stats = Array.isArray(pemAdmin?.stats) ? pemAdmin.stats : [];
  const topPosts = Array.isArray(pemAdmin?.topPosts) ? pemAdmin.topPosts : [];

  /**
   * ✅ UPDATE ENGAGEMENT CHART
   */
  function updateEngagementChart(stats) {
    const ctx = document.getElementById("engagement-chart");
    if (!ctx) return;

    const labels = stats.map((s) => s.date);
    const views = stats.map((s) => parseInt(s.views) || 0);
    const likes = stats.map((s) => parseInt(s.likes) || 0);
    const dislikes = stats.map((s) => parseInt(s.dislikes) || 0);

    if (engagementChart) {
      engagementChart.destroy();
    }

    engagementChart = new Chart(ctx, {
      type: "line",
      data: {
        labels,
        datasets: [
          {
            label: "Views",
            data: views,
            borderColor: "#2271b1",
            backgroundColor: "rgba(34,113,177,0.15)",
            fill: true,
            tension: 0.3,
          },
          {
            label: "Likes",
            data: likes,
            borderColor: "#46b450",
            backgroundColor: "rgba(70,180,80,0.15)",
            fill: true,
            tension: 0.3,
          },
          {
            label: "Dislikes",
            data: dislikes,
            borderColor: "#dc3232",
            backgroundColor: "rgba(220,50,50,0.15)",
            fill: true,
            tension: 0.3,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "top" },
          tooltip: { mode: "index", intersect: false },
        },
        interaction: { mode: "nearest", intersect: false },
        scales: {
          x: {
            title: { display: true, text: "Date" },
            ticks: { maxRotation: 45, minRotation: 30 },
          },
          y: {
            title: { display: true, text: "Count" },
            beginAtZero: true,
          },
        },
      },
    });
  } // ✅ this bracket was missing earlier

  if (!stats.length) {
    console.warn("⚠️ No engagement stats found for chart.");
  } else {
    updateEngagementChart(stats);
  }

  /**
   * ✅ TOP POSTS CHART
   */
  function updateTopPostsChart(posts) {
    const ctx = document.getElementById("top-posts-chart");
    if (!ctx) return;

    if (topPostsChart) {
      topPostsChart.destroy();
    }

    topPostsChart = new Chart(ctx, {
      type: "bar",
      data: {
        labels: posts.map((p) => p.title),
        datasets: [
          {
            label: "Views",
            data: posts.map((p) => p.views),
            backgroundColor: "rgba(34,113,177,0.8)",
          },
          {
            label: "Likes",
            data: posts.map((p) => p.likes),
            backgroundColor: "rgba(70,180,80,0.8)",
          },
          {
            label: "Dislikes",
            data: posts.map((p) => p.dislikes),
            backgroundColor: "rgba(220,50,50,0.8)",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "top" },
          tooltip: { mode: "index", intersect: false },
        },
        scales: {
          x: { title: { display: false } },
          y: { beginAtZero: true, title: { display: true, text: "Count" } },
        },
      },
    });
  }

  if (!topPosts.length) {
    console.warn("⚠️ No top posts data found for chart.");
  } else {
    updateTopPostsChart(topPosts);
  }
});
