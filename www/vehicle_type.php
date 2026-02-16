<?php
// Standalone vehicle-type selector page (not linked from the dashboard)
include 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vehicle Type — The Crew Car Wash</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <?php include 'navigation.php'; ?>
  <main class="main-content" style="padding-top:1.5rem;">
    <div class="container" style="max-width:1100px;">
      <section class="panel" style="grid-column:1 / -1;">
        <div style="display:flex;align-items:center;gap:18px;">
          <div style="width:56px;height:56px;border-radius:999px;background:var(--accent-2);display:flex;align-items:center;justify-content:center;color:#042426;font-weight:800;font-size:1.1rem;flex-shrink:0">1/5</div>
          <div>
            <h1 style="margin:0 0 6px 0">Vehicle type</h1>
            <p class="muted" style="margin:0">Select vehicle type below.</p>
          </div>
        </div>

        <div style="margin-top:22px;display:flex;gap:28px;align-items:flex-start;">
          <div style="flex:1">
            <?php $inputName = 'vehicle_type'; $selected = ''; $variant = 'large'; include __DIR__ . '/includes/vehicle_selector.php'; ?>
            <div style="margin-top:18px;display:flex;gap:8px;align-items:center;">
              <button id="vtConfirm" class="btn primary">Confirm selection</button>
              <button id="vtClear" class="btn ghost">Clear</button>
            </div>
          </div>

          <aside style="width:320px;min-width:240px;padding:12px;border-radius:8px;background:rgba(255,255,255,0.02);border:1px solid var(--border)">
            <div id="vehiclePreview" style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:8px;min-height:220px;">
              <svg id="previewSvg" width="160" height="100" viewBox="0 0 24 24" fill="none" style="opacity:0.85"><path d="M3 13h1l1-3h12l1 3h1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 13v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 13v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <div style="text-align:center">
                <div id="previewName" style="font-weight:700;font-size:1.05rem;color:var(--muted)">No selection</div>
                <div id="previewDesc" class="muted" style="font-size:0.9rem;margin-top:6px">Choose a vehicle to see details here.</div>
              </div>
            </div>
          </aside>
        </div>
      </section>
    </div>
  </main>

  <script src="carw.js"></script>
  <script>
    (function(){
      const container = document.querySelector('.vehicle-options.large');
      const previewName = document.getElementById('previewName');
      const previewDesc = document.getElementById('previewDesc');
      const confirmBtn = document.getElementById('vtConfirm');
      const clearBtn = document.getElementById('vtClear');
      const hidden = document.getElementById('vehicle_type_input');

      if (window.initVehicleSelectors) {
        window.initVehicleSelectors({ restoreFromStorage: true, persistOnSelect: false });
      }

      // map image ids to larger SVG paths or labels (simple reuse)
      const previewMap = {
        regular: { name: 'Regular Size Car', desc: 'Compact / Sedan' },
        medium: { name: 'Medium Size Car', desc: 'Hatch / Wagon' },
        suv: { name: 'Compact SUV', desc: 'Small crossover' },
        minivan: { name: 'Minivan', desc: 'Family van' },
        pickup: { name: 'Pickup Truck', desc: 'Light-duty' },
        cargo: { name: 'Cargo Truck', desc: 'Heavy load' },
        __custom__: { name: 'Custom', desc: 'Enter description' }
      };

      function onSelect(e){
        const detail = e.detail || {};
        const v = detail.value || '';
        const img = detail.image || null;
        if (img && previewMap[img]){
          previewName.textContent = previewMap[img].name;
          previewDesc.textContent = previewMap[img].desc;
        } else if (v && v !== '__custom__'){
          previewName.textContent = v;
          previewDesc.textContent = '';
        } else if (v === '__custom__'){
          previewName.textContent = 'Custom (type below)';
          previewDesc.textContent = 'Enter a description in the box that appears beneath the cards.';
        } else {
          previewName.textContent = 'No selection';
          previewDesc.textContent = 'Choose a vehicle to see details here.';
        }
      }

      if (container) container.addEventListener('vehicle:selected', onSelect);

      // Confirm: save to localStorage so other pages can use it (non-destructive)
      confirmBtn && confirmBtn.addEventListener('click', function(){
        if (!hidden || !hidden.value){ alert('Please select a vehicle type first.'); return; }
        localStorage.setItem('selected_vehicle_type', hidden.value);
        alert('Vehicle type saved: ' + hidden.value + '. You can now use this selection when adding clients.');
      });

      clearBtn && clearBtn.addEventListener('click', function(){
        if (container) {
          container.querySelectorAll('.vehicle-option').forEach(function(button){
            button.classList.remove('active');
            button.classList.remove('selected');
            button.setAttribute('aria-pressed', 'false');
          });
        }
        if (hidden) hidden.value = '';
        const customWrap = document.getElementById('vehicle_type_custom_wrap');
        const customInput = document.getElementById('vehicle_type_custom_text');
        if (customWrap) customWrap.style.display = 'none';
        if (customInput) customInput.value = '';
        if (container) container.dispatchEvent(new CustomEvent('vehicle:selected', { detail: { value: '', image: null } }));
      });

      if (hidden && hidden.value) {
        const active = container ? container.querySelector('.vehicle-option[aria-pressed="true"]') : null;
        onSelect({ detail: { value: hidden.value, image: active ? active.getAttribute('data-image') : null } });
      }
    })();
  </script>
</body>
</html>