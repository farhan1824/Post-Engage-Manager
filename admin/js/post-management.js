jQuery(document).ready(function ($) {
  // Copy shortcode functionality
  $(".pem-copy-shortcode").on("click", function () {
    const postId = $(this).data("post-id");
    const shortcode = `[pem_post id="${postId}"]`;

    // Create temporary textarea to copy text
    const $temp = $("<textarea>");
    $("body").append($temp);
    $temp.val(shortcode).select();
    document.execCommand("copy");
    $temp.remove();

    // Show success message
    const $button = $(this);
    const originalText = $button.html();
    $button.html('<span class="dashicons dashicons-yes"></span> Copied!');

    setTimeout(function () {
      $button.html(originalText);
    }, 2000);
  });

  // Initialize Chart.js with modern styling
  if (typeof Chart !== "undefined" && $("#engagement-chart").length) {
    Chart.defaults.font.family =
      '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif';
    Chart.defaults.font.size = 13;
    Chart.defaults.color = "#6b7280";

    const gradientFill = (context, color) => {
      const chartArea = context.chart.chartArea;
      if (!chartArea) return null;

      const gradient = context.chart.ctx.createLinearGradient(
        0,
        chartArea.bottom,
        0,
        chartArea.top
      );
      gradient.addColorStop(0, "rgba(255,255,255,0)");
      gradient.addColorStop(1, color);
      return gradient;
    };

    const updateCharts = () => {
      const ctx = document.getElementById("engagement-chart").getContext("2d");
      new Chart(ctx, {
        type: "line",
        data: {
          labels: pemAdmin.stats.map((item) => item.date),
          datasets: [
            {
              label: "Views",
              data: pemAdmin.stats.map((item) => item.views),
              borderColor: "#7c3aed",
              backgroundColor: function (context) {
                return gradientFill(context, "rgba(124, 58, 237, 0.1)");
              },
              tension: 0.4,
              fill: true,
            },
            {
              label: "Likes",
              data: pemAdmin.stats.map((item) => item.likes),
              borderColor: "#10b981",
              backgroundColor: function (context) {
                return gradientFill(context, "rgba(16, 185, 129, 0.1)");
              },
              tension: 0.4,
              fill: true,
            },
            {
              label: "Dislikes",
              data: pemAdmin.stats.map((item) => item.dislikes),
              borderColor: "#ef4444",
              backgroundColor: function (context) {
                return gradientFill(context, "rgba(239, 68, 68, 0.1)");
              },
              tension: 0.4,
              fill: true,
            },
          ],
        },
        options: {
          responsive: true,
          interaction: {
            mode: "index",
            intersect: false,
          },
          plugins: {
            legend: {
              position: "top",
              labels: {
                usePointStyle: true,
                padding: 20,
                font: {
                  size: 13,
                  weight: "500",
                },
              },
            },
            tooltip: {
              backgroundColor: "#1f2937",
              titleColor: "#fff",
              bodyColor: "#fff",
              padding: 12,
              displayColors: true,
              usePointStyle: true,
              borderColor: "rgba(255,255,255,0.1)",
              borderWidth: 1,
            },
          },
          scales: {
            x: {
              grid: {
                display: false,
              },
              ticks: {
                padding: 10,
              },
            },
            y: {
              beginAtZero: true,
              grid: {
                color: "rgba(0,0,0,0.05)",
              },
              ticks: {
                padding: 10,
              },
            },
          },
        },
      });
    };

    // Initialize charts
    updateCharts();

    // Handle refresh
    $("#pem-refresh-data").on("click", function () {
      const $button = $(this);
      $button.addClass("is-busy");

      // Here you would typically make an AJAX call to refresh data
      // For now, we'll just simulate a refresh
      setTimeout(function () {
        updateCharts();
        $button.removeClass("is-busy");
      }, 1000);
    });
  }
});
