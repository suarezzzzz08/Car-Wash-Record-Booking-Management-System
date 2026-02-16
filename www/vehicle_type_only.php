<?php
// Minimal page that renders ONLY the vehicle-type selector (no header/nav)
// Useful for embedding or kiosk displays.
$inputName = 'vehicle_type'; $selected = ''; $variant = 'large';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vehicle Type (selector only)</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body style="background:var(--bg);color:var(--muted);margin:0;padding:20px;">
  <main style="max-width:1200px;margin:0 auto;">
    <section style="background:transparent;">
      <h2 style="color:#eaf6f8;margin:0 0 12px 0">Choose vehicle type</h2>
      <?php include __DIR__ . '/includes/vehicle_selector.php'; ?>
      <div style="margin-top:16px;display:flex;gap:8px;">
        <button id="vtOnlyConfirm" class="btn primary">Confirm</button>
        <button id="vtOnlyClear" class="btn ghost">Clear</button>
        <div id="vtOnlyStatus" style="margin-left:12px;color:var(--muted);align-self:center"></div>
      </div>
    </section>
  </main>

  <script src="carw.js"></script>
  <script>
    (function(){
      const container = document.querySelector('.vehicle-options.large');
      const hidden = document.getElementById('vehicle_type_input');
      const confirmBtn = document.getElementById('vtOnlyConfirm');
      const clearBtn = document.getElementById('vtOnlyClear');
      const status = document.getElementById('vtOnlyStatus');

      if (window.initVehicleSelectors) {
        window.initVehicleSelectors({ restoreFromStorage: true, persistOnSelect: false });
      }

      if (container) {
        container.addEventListener('vehicle:selected', function(e){
          const val = (e && e.detail && e.detail.value) || '';
          status.textContent = val ? ('Selected: ' + val) : 'No selection';
        });
      }

      confirmBtn.addEventListener('click', function(){
        if (!hidden || !hidden.value) { alert('Please select a vehicle type first.'); return; }
        // simple behavior: store in localStorage and show confirmation
        localStorage.setItem('selected_vehicle_type', hidden.value);
        status.textContent = 'Saved: ' + hidden.value;
      });

      clearBtn.addEventListener('click', function(){
        document.querySelectorAll('.vehicle-option').forEach(function(button){
          button.classList.remove('active');
          button.classList.remove('selected');
          button.setAttribute('aria-pressed', 'false');
        });
        if (hidden) hidden.value = '';
        const customWrap = document.getElementById('vehicle_type_custom_wrap'); if (customWrap) customWrap.style.display = 'none';
        const customInput = document.getElementById('vehicle_type_custom_text'); if (customInput) customInput.value = '';
        container && container.dispatchEvent(new CustomEvent('vehicle:selected', { detail: { value: '', image: null } }));
        status.textContent = 'Cleared';
      });

      if (hidden && hidden.value) {
        status.textContent = 'Selected: ' + hidden.value;
      }
    })();
  </script>
</body>
</html>