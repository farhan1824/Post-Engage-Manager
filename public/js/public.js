// Public JS for Post Engage Manager
console.log("Post Engage Manager public loaded");

jQuery(document).ready(function ($) {
  // Helper function to set cookie
  function setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie =
      name + "=" + value + ";expires=" + expires.toUTCString() + ";path=/";
  }

  // Helper function to delete cookie
  function deleteCookie(name) {
    document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
  }

  // Helper function to get cookie
  function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(";");
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) === " ") c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  }

  // Initialize buttons on page load based on existing cookies
  function initializeButtons() {
    $(".pem-engage-buttons").each(function () {
      const container = $(this);
      const likeBtn = container.find(".pem-like");
      const dislikeBtn = container.find(".pem-dislike");
      const postId = likeBtn.data("post");

      if (postId) {
        const isLiked = getCookie("pem_like_" + postId);
        const isDisliked = getCookie("pem_dislike_" + postId);

        likeBtn.toggleClass("active", !!isLiked);
        dislikeBtn.toggleClass("active", !!isDisliked);
      }
    });
  }

  // Update counts in both button and stats area
  function updateAllCounts(container, likes, dislikes, votedType) {
    // Update button counts
    container.find(".pem-like-count").text(likes);
    container.find(".pem-dislike-count").text(dislikes);

    // Update stats area counts (if they exist)
    const statsArea = container
      .closest(".pem-single-post")
      .find(".pem-post-stats");
    if (statsArea.length) {
      statsArea.find(".pem-total-likes").text(likes);
      statsArea.find(".pem-total-dislikes").text(dislikes);
    }

    // Update active states
    const likeBtn = container.find(".pem-like");
    const dislikeBtn = container.find(".pem-dislike");

    if (votedType === "like") {
      likeBtn.addClass("active");
      dislikeBtn.removeClass("active");
    } else if (votedType === "dislike") {
      dislikeBtn.addClass("active");
      likeBtn.removeClass("active");
    }
  }

  // Initialize on page load
  initializeButtons();

  // Like button handler
  $(".pem-like").on("click", function (e) {
    e.preventDefault();
    const btn = $(this);
    const post_id = btn.data("post");
    const container = btn.closest(".pem-engage-buttons");

    // Check if already liked
    if (getCookie("pem_like_" + post_id)) {
      alert("You have already liked this post");
      return;
    }

    // Get current counts
    let currentLikes = parseInt(container.find(".pem-like-count").text()) || 0;
    let currentDislikes =
      parseInt(container.find(".pem-dislike-count").text()) || 0;

    // Check if previously disliked
    const wasDisliked = getCookie("pem_dislike_" + post_id);
    if (wasDisliked) {
      currentDislikes = Math.max(0, currentDislikes - 1);
      deleteCookie("pem_dislike_" + post_id);
    }

    // Optimistically update UI IMMEDIATELY
    currentLikes++;
    setCookie("pem_like_" + post_id, "1", 1);
    updateAllCounts(container, currentLikes, currentDislikes, "like");

    // Disable button temporarily
    btn.prop("disabled", true);

    // Send to server
    $.ajax({
      url: pemAjax.ajaxurl,
      type: "POST",
      data: {
        action: "pem_like_post",
        post_id: post_id,
        nonce: pemAjax.nonce,
      },
      success: function (res) {
        if (res.success) {
          // Update with actual server counts
          updateAllCounts(container, res.data.likes, res.data.dislikes, "like");
          console.log("Like successful:", res.data);
        } else {
          // Revert on error
          currentLikes--;
          if (wasDisliked) {
            currentDislikes++;
            setCookie("pem_dislike_" + post_id, "1", 1);
          }
          deleteCookie("pem_like_" + post_id);
          updateAllCounts(
            container,
            currentLikes,
            currentDislikes,
            wasDisliked ? "dislike" : null
          );
          alert(res.data || "Failed to like post");
        }
      },
      error: function (xhr, status, error) {
        // Revert on error
        currentLikes--;
        if (wasDisliked) {
          currentDislikes++;
          setCookie("pem_dislike_" + post_id, "1", 1);
        }
        deleteCookie("pem_like_" + post_id);
        updateAllCounts(
          container,
          currentLikes,
          currentDislikes,
          wasDisliked ? "dislike" : null
        );
        console.error("AJAX error:", status, error);
        alert("Request failed. Please try again.");
      },
      complete: function () {
        btn.prop("disabled", false);
      },
    });
  });

  // Dislike button handler
  $(".pem-dislike").on("click", function (e) {
    e.preventDefault();
    const btn = $(this);
    const post_id = btn.data("post");
    const container = btn.closest(".pem-engage-buttons");

    // Check if already disliked
    if (getCookie("pem_dislike_" + post_id)) {
      alert("You have already disliked this post");
      return;
    }

    // Get current counts
    let currentLikes = parseInt(container.find(".pem-like-count").text()) || 0;
    let currentDislikes =
      parseInt(container.find(".pem-dislike-count").text()) || 0;

    // Check if previously liked
    const wasLiked = getCookie("pem_like_" + post_id);
    if (wasLiked) {
      currentLikes = Math.max(0, currentLikes - 1);
      deleteCookie("pem_like_" + post_id);
    }

    // Optimistically update UI IMMEDIATELY
    currentDislikes++;
    setCookie("pem_dislike_" + post_id, "1", 1);
    updateAllCounts(container, currentLikes, currentDislikes, "dislike");

    // Disable button temporarily
    btn.prop("disabled", true);

    // Send to server
    $.ajax({
      url: pemAjax.ajaxurl,
      type: "POST",
      data: {
        action: "pem_dislike_post",
        post_id: post_id,
        nonce: pemAjax.nonce,
      },
      success: function (res) {
        if (res.success) {
          // Update with actual server counts
          updateAllCounts(
            container,
            res.data.likes,
            res.data.dislikes,
            "dislike"
          );
          console.log("Dislike successful:", res.data);
        } else {
          // Revert on error
          currentDislikes--;
          if (wasLiked) {
            currentLikes++;
            setCookie("pem_like_" + post_id, "1", 1);
          }
          deleteCookie("pem_dislike_" + post_id);
          updateAllCounts(
            container,
            currentLikes,
            currentDislikes,
            wasLiked ? "like" : null
          );
          alert(res.data || "Failed to dislike post");
        }
      },
      error: function (xhr, status, error) {
        // Revert on error
        currentDislikes--;
        if (wasLiked) {
          currentLikes++;
          setCookie("pem_like_" + post_id, "1", 1);
        }
        deleteCookie("pem_dislike_" + post_id);
        updateAllCounts(
          container,
          currentLikes,
          currentDislikes,
          wasLiked ? "like" : null
        );
        console.error("AJAX error:", status, error);
        alert("Request failed. Please try again.");
      },
      complete: function () {
        btn.prop("disabled", false);
      },
    });
  });
});
