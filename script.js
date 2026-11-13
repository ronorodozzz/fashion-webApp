/**
 * TREND & FASHION BOUTIQUE SHOP - FRONTEND INTERACTION LOGIC
 * Features:
 *   1. Interactive AVIF Image Swipe Carousel & Gallery
 *   2. Dynamic Size-Based Pricing Calculation & Switcher
 *   3. Quantity Steppers
 *   4. Payment Method Selection Switcher (COD vs Credit Card)
 *   5. Auth Tab Switching
 */

document.addEventListener('DOMContentLoaded', function () {
  initGalleryCarousel();
  initSizePricingSwitcher();
  initQuantitySteppers();
  initPaymentToggle();
  initAuthTabs();
});

/* ==========================================================
   1. INTERACTIVE AVIF IMAGE SWIPE CAROUSEL & GALLERY
   ========================================================== */
function initGalleryCarousel() {
  const stage = document.querySelector('.gallery-stage');
  const slider = document.querySelector('.gallery-slider');
  const slides = document.querySelectorAll('.gallery-slide');
  const prevBtn = document.querySelector('.gallery-prev');
  const nextBtn = document.querySelector('.gallery-next');
  const thumbs = document.querySelectorAll('.gallery-thumb-item');
  const dots = document.querySelectorAll('.gallery-dot');
  const metaTag = document.querySelector('.gallery-meta-tag');

  if (!stage || !slider || slides.length === 0) return;

  let currentIndex = 0;
  const totalSlides = slides.length;

  function updateGallery(index) {
    // Wrap around for continuous loop
    if (index < 0) {
      currentIndex = totalSlides - 1;
    } else if (index >= totalSlides) {
      currentIndex = 0;
    } else {
      currentIndex = index;
    }

    // Move slider track
    slider.style.transform = `translateX(-${currentIndex * 100}%)`;

    // Update active thumbnail
    thumbs.forEach((thumb, i) => {
      thumb.classList.toggle('active', i === currentIndex);
    });

    // Update active dot
    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === currentIndex);
    });

    // Update version indicator tag
    if (metaTag) {
      metaTag.textContent = `Version ${currentIndex + 1} of ${totalSlides}`;
    }
  }

  // Next / Prev button events
  if (nextBtn) {
    nextBtn.addEventListener('click', (e) => {
      e.preventDefault();
      updateGallery(currentIndex + 1);
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', (e) => {
      e.preventDefault();
      updateGallery(currentIndex - 1);
    });
  }

  // Thumbnail click events
  thumbs.forEach((thumb, i) => {
    thumb.addEventListener('click', () => {
      updateGallery(i);
    });
  });

  // Indicator dot click events
  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      updateGallery(i);
    });
  });

  // Touch Swipe Gesture Detection
  let touchStartX = 0;
  let touchEndX = 0;
  const minSwipeDistance = 40; // minimum pixels to count as swipe

  stage.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
  }, { passive: true });

  stage.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipeGesture();
  }, { passive: true });

  // Mouse Drag / Swipe for desktop testing
  let isMouseDown = false;
  let mouseStartX = 0;

  stage.addEventListener('mousedown', (e) => {
    isMouseDown = true;
    mouseStartX = e.clientX;
  });

  window.addEventListener('mouseup', (e) => {
    if (!isMouseDown) return;
    isMouseDown = false;
    const diff = e.clientX - mouseStartX;
    if (Math.abs(diff) > minSwipeDistance) {
      if (diff < 0) {
        updateGallery(currentIndex + 1);
      } else {
        updateGallery(currentIndex - 1);
      }
    }
  });

  function handleSwipeGesture() {
    const diff = touchEndX - touchStartX;
    if (Math.abs(diff) > minSwipeDistance) {
      if (diff < 0) {
        // Swiped Left -> Next Image
        updateGallery(currentIndex + 1);
      } else {
        // Swiped Right -> Previous Image
        updateGallery(currentIndex - 1);
      }
    }
  }

  // Keyboard Navigation (Left / Right Arrow)
  window.addEventListener('keydown', (e) => {
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;
    if (e.key === 'ArrowRight') {
      updateGallery(currentIndex + 1);
    } else if (e.key === 'ArrowLeft') {
      updateGallery(currentIndex - 1);
    }
  });

  // Initialize
  updateGallery(0);
}

/* ==========================================================
   2. DYNAMIC SIZE-BASED PRICING SWITCHER
   ========================================================== */
function initSizePricingSwitcher() {
  const sizeInputs = document.querySelectorAll('.size-radio-input');
  const priceDisplay = document.getElementById('active-product-price');
  const hiddenPriceInput = document.getElementById('selected-unit-price');

  if (!sizeInputs.length || !priceDisplay) return;

  sizeInputs.forEach(input => {
    input.addEventListener('change', function () {
      const newPrice = parseFloat(this.getAttribute('data-price'));
      if (!isNaN(newPrice)) {
        // Format as $XX.XX
        priceDisplay.textContent = `$${newPrice.toFixed(2)}`;

        // Update hidden form inputs if present
        if (hiddenPriceInput) {
          hiddenPriceInput.value = newPrice.toFixed(2);
        }

        // Visual flash highlight on price change
        priceDisplay.style.color = '#FC75B4';
        setTimeout(() => {
          priceDisplay.style.color = '#474329';
        }, 300);
      }
    });
  });
}

/* ==========================================================
   3. QUANTITY CONTROLS STEPPER
   ========================================================== */
function initQuantitySteppers() {
  document.querySelectorAll('.qty-control').forEach(control => {
    const decBtn = control.querySelector('.qty-btn-dec');
    const incBtn = control.querySelector('.qty-btn-inc');
    const input = control.querySelector('.qty-input');

    if (!input) return;

    if (decBtn) {
      decBtn.addEventListener('click', (e) => {
        e.preventDefault();
        let val = parseInt(input.value, 10) || 1;
        if (val > 1) {
          input.value = val - 1;
        }
      });
    }

    if (incBtn) {
      incBtn.addEventListener('click', (e) => {
        e.preventDefault();
        let val = parseInt(input.value, 10) || 1;
        input.value = val + 1;
      });
    }
  });
}

/* ==========================================================
   4. CHECKOUT PAYMENT METHOD SELECTOR (COD VS CREDIT CARD)
   ========================================================== */
function initPaymentToggle() {
  const paymentCards = document.querySelectorAll('.payment-option-card');
  const cardDetailsBox = document.getElementById('credit-card-details-box');
  const cardInputs = cardDetailsBox ? cardDetailsBox.querySelectorAll('input') : [];

  if (!paymentCards.length) return;

  function updatePaymentState() {
    paymentCards.forEach(card => {
      const radio = card.querySelector('input[type="radio"]');
      if (radio && radio.checked) {
        card.classList.add('selected');
        if (radio.value === 'credit_card') {
          if (cardDetailsBox) cardDetailsBox.style.display = 'block';
          cardInputs.forEach(inp => inp.setAttribute('required', 'required'));
        } else {
          if (cardDetailsBox) cardDetailsBox.style.display = 'none';
          cardInputs.forEach(inp => inp.removeAttribute('required'));
        }
      } else {
        card.classList.remove('selected');
      }
    });
  }

  paymentCards.forEach(card => {
    card.addEventListener('click', () => {
      const radio = card.querySelector('input[type="radio"]');
      if (radio) {
        radio.checked = true;
        updatePaymentState();
      }
    });
  });

  updatePaymentState();
}

/* ==========================================================
   5. AUTH TABS SWITCHER (index.php)
   ========================================================== */
function initAuthTabs() {
  const tabs = document.querySelectorAll('.auth-tab-btn');
  const forms = document.querySelectorAll('.auth-form-panel');

  if (!tabs.length || !forms.length) return;

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const targetId = tab.getAttribute('data-target');

      tabs.forEach(t => t.classList.remove('active'));
      forms.forEach(f => f.style.display = 'none');

      tab.classList.add('active');
      const targetForm = document.getElementById(targetId);
      if (targetForm) {
        targetForm.style.display = 'block';
      }
    });
  });
}

/* Global helper to fill demo credentials */
function fillDemoCredentials() {
  const userInput = document.getElementById('login-username');
  const passInput = document.getElementById('login-password');
  if (userInput && passInput) {
    userInput.value = 'fashionista';
    passInput.value = 'password123';
  }
}
