function initVehicleSelectors(options) {
  const settings = Object.assign({
    restoreFromStorage: true,
    storageKey: 'selected_vehicle_type',
    persistOnSelect: false
  }, options || {});

  function getHiddenInput(container) {
    if (container.classList.contains('bookings-vehicle-selector')) {
      return container.querySelector('input[type="hidden"]');
    }

    if (container.classList.contains('vehicle-options')) {
      const prev = container.previousElementSibling;
      if (prev && prev.tagName === 'INPUT' && prev.type === 'hidden') return prev;
      const byId = container.id ? document.getElementById(container.id.replace('_options', '_input')) : null;
      return byId && byId.type === 'hidden' ? byId : null;
    }

    return null;
  }

  function getElements(container, hiddenInput) {
    const isBvs = container.classList.contains('bookings-vehicle-selector');
    const buttons = isBvs ? container.querySelectorAll('.bvs-btn') : container.querySelectorAll('.vehicle-option');
    const customButton = isBvs
      ? container.querySelector('.bvs-other')
      : container.querySelector('.vehicle-option[data-value="__custom__"]');
    const customInput = isBvs
      ? container.querySelector('.bvs-custom-input')
      : document.getElementById((hiddenInput ? hiddenInput.name : 'vehicle_type') + '_custom_text');
    const customWrap = isBvs
      ? container.querySelector('.bvs-custom')
      : document.getElementById((hiddenInput ? hiddenInput.name : 'vehicle_type') + '_custom_wrap');

    return { isBvs, buttons, customButton, customInput, customWrap };
  }

  function getPlateValue(hiddenInput) {
    const form = hiddenInput && hiddenInput.closest('form') ? hiddenInput.closest('form') : document;
    const plateField = form.querySelector('input[name="plate_number"]') || document.querySelector('input[name="plate_number"]');
    return plateField && plateField.value ? plateField.value.trim() : '';
  }

  function getPreviewElement(hiddenInput) {
    const form = hiddenInput && hiddenInput.closest('form') ? hiddenInput.closest('form') : null;
    const inForm = form ? form.querySelector('#vehicle_preview') : null;
    return inForm || document.getElementById('vehicle_preview');
  }

  function showCustom(customWrap, isBvs, visible) {
    if (!customWrap) return;
    if (isBvs) {
      customWrap.setAttribute('aria-hidden', visible ? 'false' : 'true');
      return;
    }
    customWrap.style.display = visible ? '' : 'none';
  }

  function clearButtons(buttons) {
    buttons.forEach(function (button) {
      button.classList.remove('active');
      button.classList.remove('selected');
      button.setAttribute('aria-pressed', 'false');
    });
  }

  function updatePreview(hiddenInput, value) {
    const preview = getPreviewElement(hiddenInput);
    if (!preview) return;
    if (!value) {
      preview.textContent = 'No vehicle selected';
      return;
    }
    const plate = getPlateValue(hiddenInput);
    preview.textContent = plate ? (plate + ' (' + value + ')') : value;
  }

  function initContainer(container) {
    if (!container || container.dataset.vehicleSelectorInit === '1') return;

    const hiddenInput = getHiddenInput(container);
    if (!hiddenInput) return;

    const elements = getElements(container, hiddenInput);
    if (!elements.buttons || !elements.buttons.length) return;

    container.dataset.vehicleSelectorInit = '1';

    function setSelected(button, value, rawValue) {
      clearButtons(elements.buttons);

      if (button) {
        button.classList.add('active');
        button.classList.add('selected');
        button.setAttribute('aria-pressed', 'true');
      }

      hiddenInput.value = value || '';
      updatePreview(hiddenInput, hiddenInput.value);

      if (settings.persistOnSelect && hiddenInput.value) {
        try { localStorage.setItem(settings.storageKey, hiddenInput.value); } catch (err) {}
      }

      container.dispatchEvent(new CustomEvent('vehicle:selected', {
        detail: {
          value: rawValue || value || '',
          image: button ? (button.getAttribute('data-image') || null) : null
        }
      }));
    }

    function applyStoredValue(storedValue) {
      if (!storedValue) {
        showCustom(elements.customWrap, elements.isBvs, false);
        updatePreview(hiddenInput, '');
        return;
      }

      const matched = Array.from(elements.buttons).find(function (button) {
        return button.getAttribute('data-value') === storedValue;
      });

      if (matched) {
        showCustom(elements.customWrap, elements.isBvs, false);
        if (elements.customInput) elements.customInput.value = '';
        setSelected(matched, storedValue, storedValue);
        return;
      }

      if (elements.customButton) {
        if (elements.customInput) elements.customInput.value = storedValue;
        showCustom(elements.customWrap, elements.isBvs, true);
        setSelected(elements.customButton, storedValue, storedValue);
      }
    }

    elements.buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        const value = button.getAttribute('data-value') || '';
        if (value === '__custom__') {
          const customValue = elements.customInput ? elements.customInput.value : '';
          showCustom(elements.customWrap, elements.isBvs, true);
          setSelected(button, customValue || '', customValue || '');
          if (elements.customInput) elements.customInput.focus();
        } else {
          showCustom(elements.customWrap, elements.isBvs, false);
          setSelected(button, value, value);
        }
      });

      const forwardClick = button.querySelector('[data-action="forward-click"]');
      if (forwardClick) {
        forwardClick.addEventListener('click', function (event) {
          event.stopPropagation();
          button.click();
        });
      }

      button.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          button.click();
        }
      });
    });

    if (elements.customInput) {
      elements.customInput.addEventListener('input', function () {
        if (elements.customButton && elements.customButton.getAttribute('aria-pressed') === 'true') {
          setSelected(elements.customButton, elements.customInput.value || '', elements.customInput.value || '');
        }
      });
    }

    const form = hiddenInput.closest('form');
    const plateField = form ? form.querySelector('input[name="plate_number"]') : document.querySelector('input[name="plate_number"]');
    if (plateField) {
      plateField.addEventListener('input', function () {
        updatePreview(hiddenInput, hiddenInput.value || '');
      });
    }

    let initialValue = hiddenInput.value || '';
    if (!initialValue && settings.restoreFromStorage) {
      try { initialValue = localStorage.getItem(settings.storageKey) || ''; } catch (err) {}
    }
    applyStoredValue(initialValue);
  }

  document.querySelectorAll('.bookings-vehicle-selector, .vehicle-options').forEach(initContainer);
}

window.initVehicleSelectors = initVehicleSelectors;

document.addEventListener('DOMContentLoaded', function(){
  const search = document.getElementById('tableSearch');
  const table = document.getElementById('clientTable');

  if (search && table) {
    search.addEventListener('input', function(){
      const q = this.value.toLowerCase().trim();
      const rows = table.tBodies[0].rows;
      for (let r of rows) {
        const text = (r.textContent || r.innerText).toLowerCase();
        r.style.display = text.indexOf(q) !== -1 ? '' : 'none';
      }
    });
  }

  initVehicleSelectors({ restoreFromStorage: true, persistOnSelect: false });

  const form = document.querySelector('.client-form');
  if (form) {
    form.addEventListener('submit', function(e){
      const phoneInput = form.querySelector('input[name="phone_number"]');
      if (phoneInput) {
        const v = phoneInput.value.replace(/\D/g,'');
        if (!/^\d{7,13}$/.test(v)) {
          e.preventDefault();
          alert('Please enter a valid phone number (7-13 digits).');
          phoneInput.focus();
        }
      }
    });
  }
});

// Navigation highlighting
(function () {
  const html = document.documentElement;
  const toggle = document.getElementById('navToggle');
  const nav = document.getElementById('mainNav');

  if (!toggle || !nav) return;

  function setExpanded(val) {
    toggle.setAttribute('aria-expanded', String(val));
    if (val) html.classList.add('nav-open'); else html.classList.remove('nav-open');
  }

  toggle.addEventListener('click', function (e) {
    const expanded = toggle.getAttribute('aria-expanded') === 'true';
    setExpanded(!expanded);
  });

  // close on escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setExpanded(false);
  });

  // close when clicking outside nav on mobile
  document.addEventListener('click', function (e) {
    if (!nav.contains(e.target) && !toggle.contains(e.target)) setExpanded(false);
  });

})();

