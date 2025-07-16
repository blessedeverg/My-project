// Mobile Navigation Toggle
const hamburger = document.querySelector(".hamburger");
const navMenu = document.querySelector(".nav-menu");

if (hamburger && navMenu) {
  hamburger.addEventListener("click", () => {
    hamburger.classList.toggle("active");
    navMenu.classList.toggle("active");
  });

  // Close mobile menu when clicking on a link
  document.querySelectorAll(".nav-link").forEach((n) =>
    n.addEventListener("click", () => {
      hamburger.classList.remove("active");
      navMenu.classList.remove("active");
    }),
  );
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  });
});

// Product card animations
const observeElements = () => {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0)";
        }
      });
    },
    {
      threshold: 0.1,
      rootMargin: "50px",
    },
  );

  document.querySelectorAll(".product-card").forEach((card) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(20px)";
    card.style.transition = "opacity 0.6s ease, transform 0.6s ease";
    observer.observe(card);
  });
};

// Initialize animations when DOM is loaded
document.addEventListener("DOMContentLoaded", observeElements);

// Search functionality
const searchForm = document.querySelector(".nav-search form");
if (searchForm) {
  searchForm.addEventListener("submit", function (e) {
    const searchInput = this.querySelector('input[name="q"]');
    if (!searchInput.value.trim()) {
      e.preventDefault();
      searchInput.focus();
    }
  });
}

// Add loading states for forms
const addLoadingState = (form) => {
  form.addEventListener("submit", function () {
    const submitBtn = this.querySelector(
      'button[type="submit"], input[type="submit"]',
    );
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    }
  });
};

// Apply loading states to all forms
document.querySelectorAll("form").forEach(addLoadingState);

// Image lazy loading
const lazyImages = document.querySelectorAll("img[data-src]");
const imageObserver = new IntersectionObserver((entries, observer) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      const img = entry.target;
      img.src = img.dataset.src;
      img.removeAttribute("data-src");
      imageObserver.unobserve(img);
    }
  });
});

lazyImages.forEach((img) => imageObserver.observe(img));

// Add to cart functionality (placeholder)
document.addEventListener("click", function (e) {
  if (e.target.classList.contains("add-to-cart")) {
    e.preventDefault();
    const productId = e.target.dataset.productId;

    // Show success message
    const message = document.createElement("div");
    message.className = "alert alert-success";
    message.innerHTML = '<i class="fas fa-check"></i> Product added to cart!';
    message.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 1rem;
            border-radius: 5px;
            z-index: 1001;
            animation: slideIn 0.3s ease;
        `;

    document.body.appendChild(message);

    setTimeout(() => {
      message.remove();
    }, 3000);
  }
});

// Add CSS for alert animation
const style = document.createElement("style");
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
