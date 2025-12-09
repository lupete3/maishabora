/**
 * Main.js adapté pour Livewire
 */
'use strict';

let menu, animate;

(function () {

  function initMain() {
    // Initialize menu
    //-----------------
    let layoutMenuEl = document.querySelectorAll('#layout-menu');
    layoutMenuEl.forEach(function (element) {
      menu = new Menu(element, {
        orientation: 'vertical',
        closeChildren: false
      });
      // Change parameter to true if you want scroll animation
      window.Helpers.scrollToActive((animate = false));
      window.Helpers.mainMenu = menu;
    });

    // Initialize menu togglers and bind click on each
    let menuToggler = document.querySelectorAll('.layout-menu-toggle');
    menuToggler.forEach(item => {
      item.addEventListener('click', event => {
        event.preventDefault();
        window.Helpers.toggleCollapsed();
      });
    });

    // Display menu toggle (layout-menu-toggle) on hover with delay
    let delay = function (elem, callback) {
      let timeout = null;
      elem.onmouseenter = function () {
        if (!Helpers.isSmallScreen()) {
          timeout = setTimeout(callback, 300);
        } else {
          timeout = setTimeout(callback, 0);
        }
      };

      elem.onmouseleave = function () {
        document.querySelector('.layout-menu-toggle').classList.remove('d-block');
        clearTimeout(timeout);
      };
    };

    if (document.getElementById('layout-menu')) {
      delay(document.getElementById('layout-menu'), function () {
        if (!Helpers.isSmallScreen()) {
          document.querySelector('.layout-menu-toggle').classList.add('d-block');
        }
      });
    }

    // Display in main menu when menu scrolls
    let menuInnerContainer = document.getElementsByClassName('menu-inner'),
      menuInnerShadow = document.getElementsByClassName('menu-inner-shadow')[0];
    if (menuInnerContainer.length > 0 && menuInnerShadow) {
      menuInnerContainer[0].addEventListener('ps-scroll-y', function () {
        if (this.querySelector('.ps__thumb-y') && this.querySelector('.ps__thumb-y').offsetTop) {
          menuInnerShadow.style.display = 'block';
        } else {
          menuInnerShadow.style.display = 'none';
        }
      });
    }

    // Init helpers & misc
    // --------------------

    // Init BS Tooltip
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Accordion active class
    const accordionActiveFunction = function (e) {
      if (e.type == 'show.bs.collapse') {
        e.target.closest('.accordion-item').classList.add('active');
      } else {
        e.target.closest('.accordion-item').classList.remove('active');
      }
    };

    const accordionTriggerList = [].slice.call(document.querySelectorAll('.accordion'));
    accordionTriggerList.map(function (accordionTriggerEl) {
      accordionTriggerEl.addEventListener('show.bs.collapse', accordionActiveFunction);
      accordionTriggerEl.addEventListener('hide.bs.collapse', accordionActiveFunction);
    });

    // Auto update layout based on screen size
    window.Helpers.setAutoUpdate(true);

    // Toggle Password Visibility
    window.Helpers.initPasswordToggle();

    // Speech To Text
    window.Helpers.initSpeechToText();

    // Manage menu expanded/collapsed with templateCustomizer & local storage
    //------------------------------------------------------------------
    if (!window.Helpers.isSmallScreen()) {
      window.Helpers.setCollapsed(true, false);
    }
  }

  // ========== EXÉCUTION NORMALE ==========
  document.addEventListener("DOMContentLoaded", initMain);

  // ========== ✅ COMPATIBILITÉ LIVEWIRE ==========
  document.addEventListener("livewire:load", initMain);
  document.addEventListener("livewire:update", initMain);
  document.addEventListener("livewire:navigated", initMain);redit

  // Réinitialiser les styles dynamiques Sneat (inputs, selects, etc.)
  if (typeof window.Helpers !== "undefined" && typeof window.Helpers.initFormElements === "function") {
    window.Helpers.initFormElements();
  }

})();
