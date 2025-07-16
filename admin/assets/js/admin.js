// Admin Panel JavaScript Functionality

// Mobile sidebar toggle
const sidebarToggle = document.querySelector(".sidebar-toggle");
const sidebar = document.querySelector(".sidebar");

if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener("click", () => {
    sidebar.classList.toggle("active");
  });
}

// Form validation helpers
const validateRequired = (input) => {
  const value = input.value.trim();
  if (!value) {
    showFieldError(input, "This field is required.");
    return false;
  }
  clearFieldError(input);
  return true;
};

const validateEmail = (input) => {
  const email = input.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (!emailRegex.test(email)) {
    showFieldError(input, "Please enter a valid email address.");
    return false;
  }
  clearFieldError(input);
  return true;
};

const validatePrice = (input) => {
  const price = parseFloat(input.value);
  if (isNaN(price) || price <= 0) {
    showFieldError(input, "Price must be a valid number greater than 0.");
    return false;
  }
  clearFieldError(input);
  return true;
};

const showFieldError = (input, message) => {
  clearFieldError(input);

  const errorDiv = document.createElement("div");
  errorDiv.className = "field-error";
  errorDiv.style.cssText =
    "color: #dc3545; font-size: 0.8rem; margin-top: 0.25rem;";
  errorDiv.textContent = message;

  input.style.borderColor = "#dc3545";
  input.parentNode.appendChild(errorDiv);
};

const clearFieldError = (input) => {
  const existingError = input.parentNode.querySelector(".field-error");
  if (existingError) {
    existingError.remove();
  }
  input.style.borderColor = "";
};

// Real-time form validation
document.addEventListener("DOMContentLoaded", () => {
  // Validate required fields on blur
  const requiredFields = document.querySelectorAll(
    "input[required], textarea[required], select[required]",
  );
  requiredFields.forEach((field) => {
    field.addEventListener("blur", () => validateRequired(field));
  });

  // Validate email fields
  const emailFields = document.querySelectorAll('input[type="email"]');
  emailFields.forEach((field) => {
    field.addEventListener("blur", () => validateEmail(field));
  });

  // Validate price fields
  const priceFields = document.querySelectorAll('input[name="price"]');
  priceFields.forEach((field) => {
    field.addEventListener("blur", () => validatePrice(field));
  });
});

// File upload preview
const handleFileUpload = (input, previewContainer) => {
  const file = input.files[0];
  if (!file) return;

  // Validate file type
  const allowedTypes = [
    "image/jpeg",
    "image/jpg",
    "image/png",
    "image/gif",
    "image/webp",
  ];
  if (!allowedTypes.includes(file.type)) {
    alert("Invalid file type. Please select a valid image file.");
    input.value = "";
    return;
  }

  // Validate file size (5MB max)
  const maxSize = 5 * 1024 * 1024; // 5MB
  if (file.size > maxSize) {
    alert("File size too large. Please select an image under 5MB.");
    input.value = "";
    return;
  }

  // Show preview
  const reader = new FileReader();
  reader.onload = (e) => {
    if (previewContainer) {
      previewContainer.innerHTML = `
                <img src="${e.target.result}" alt="Preview" 
                     style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
            `;
      previewContainer.style.display = "block";
    }
  };
  reader.readAsDataURL(file);
};

// Auto-save form data to localStorage
const autoSaveForm = (formId) => {
  const form = document.getElementById(formId);
  if (!form) return;

  const saveKey = `autosave_${formId}`;

  // Load saved data
  const savedData = localStorage.getItem(saveKey);
  if (savedData) {
    const data = JSON.parse(savedData);
    Object.keys(data).forEach((name) => {
      const field = form.querySelector(`[name="${name}"]`);
      if (field && field.type !== "file") {
        field.value = data[name];
      }
    });
  }

  // Save data on input
  form.addEventListener("input", () => {
    const formData = new FormData(form);
    const data = {};
    for (let [name, value] of formData.entries()) {
      if (form.querySelector(`[name="${name}"]`).type !== "file") {
        data[name] = value;
      }
    }
    localStorage.setItem(saveKey, JSON.stringify(data));
  });

  // Clear saved data on successful submit
  form.addEventListener("submit", () => {
    setTimeout(() => {
      if (!document.querySelector(".alert-error")) {
        localStorage.removeItem(saveKey);
      }
    }, 100);
  });
};

// Data tables functionality
const initDataTable = (tableId) => {
  const table = document.getElementById(tableId);
  if (!table) return;

  // Add search functionality
  const searchInput = document.createElement("input");
  searchInput.type = "text";
  searchInput.placeholder = "Search...";
  searchInput.className = "form-control";
  searchInput.style.cssText = "margin-bottom: 1rem; max-width: 300px;";

  table.parentNode.insertBefore(searchInput, table);

  searchInput.addEventListener("input", (e) => {
    const searchTerm = e.target.value.toLowerCase();
    const rows = table.querySelectorAll("tbody tr");

    rows.forEach((row) => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? "" : "none";
    });
  });

  // Add sorting to headers
  const headers = table.querySelectorAll("th");
  headers.forEach((header, index) => {
    if (header.textContent.trim()) {
      header.style.cursor = "pointer";
      header.style.userSelect = "none";
      header.innerHTML +=
        ' <i class="fas fa-sort" style="opacity: 0.5; margin-left: 0.5rem;"></i>';

      header.addEventListener("click", () => {
        sortTable(table, index);
      });
    }
  });
};

const sortTable = (table, columnIndex) => {
  const tbody = table.querySelector("tbody");
  const rows = Array.from(tbody.querySelectorAll("tr"));

  const isAscending = table.dataset.sortOrder !== "asc";
  table.dataset.sortOrder = isAscending ? "asc" : "desc";

  rows.sort((a, b) => {
    const aValue = a.cells[columnIndex]?.textContent.trim() || "";
    const bValue = b.cells[columnIndex]?.textContent.trim() || "";

    // Try to parse as numbers first
    const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ""));
    const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ""));

    if (!isNaN(aNum) && !isNaN(bNum)) {
      return isAscending ? aNum - bNum : bNum - aNum;
    }

    // String comparison
    return isAscending
      ? aValue.localeCompare(bValue)
      : bValue.localeCompare(aValue);
  });

  rows.forEach((row) => tbody.appendChild(row));

  // Update sort icons
  const headers = table.querySelectorAll("th i.fas");
  headers.forEach((icon) => {
    icon.className = "fas fa-sort";
    icon.style.opacity = "0.5";
  });

  const currentIcon = table
    .querySelectorAll("th")
    [columnIndex].querySelector("i.fas");
  if (currentIcon) {
    currentIcon.className = `fas fa-sort-${isAscending ? "up" : "down"}`;
    currentIcon.style.opacity = "1";
  }
};

// Notification system
const showNotification = (message, type = "info", duration = 5000) => {
  const notification = document.createElement("div");
  notification.className = `alert alert-${type}`;
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease;
    `;

  notification.innerHTML = `
        <i class="fas fa-${type === "success" ? "check" : type === "error" ? "exclamation-triangle" : "info-circle"}"></i>
        ${message}
        <button type="button" style="float: right; background: none; border: none; font-size: 1.2rem; cursor: pointer; margin-left: 1rem;">&times;</button>
    `;

  document.body.appendChild(notification);

  // Auto remove
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, duration);

  // Manual close
  notification.querySelector("button").addEventListener("click", () => {
    notification.remove();
  });
};

// Add CSS animations
const style = document.createElement("style");
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .form-control:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2) !important;
    }
    
    .table th {
        transition: background-color 0.2s ease;
    }
    
    .table th:hover {
        background-color: #e9ecef !important;
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1060;
    }
    
    .loading-spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Loading overlay
const showLoading = () => {
  const overlay = document.createElement("div");
  overlay.className = "loading-overlay";
  overlay.innerHTML = '<div class="loading-spinner"></div>';
  document.body.appendChild(overlay);
  return overlay;
};

const hideLoading = (overlay) => {
  if (overlay && overlay.parentNode) {
    overlay.remove();
  }
};

// Initialize features when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  // Initialize data tables
  const tables = document.querySelectorAll(".table");
  tables.forEach((table, index) => {
    if (!table.id) table.id = `table_${index}`;
    initDataTable(table.id);
  });

  // Auto-save forms
  const forms = document.querySelectorAll("form[id]");
  forms.forEach((form) => {
    if (form.method.toLowerCase() === "post") {
      autoSaveForm(form.id);
    }
  });

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
        });
      }
    });
  });
});

// Export functions for global use
window.AdminJS = {
  showNotification,
  showLoading,
  hideLoading,
  validateRequired,
  validateEmail,
  validatePrice,
  handleFileUpload,
};
