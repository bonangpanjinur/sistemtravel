<?php
// File: rooming-manager.php
// Location: templates/admin/operations/rooming-manager.php

/** @var object $departure */
/** @var array $unassigned */
/** @var array $rooms */
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Kelola Kamar: <?php echo esc_html($departure->package_name); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=umh-rooming-list'); ?>" class="page-title-action">Kembali</a>
    <hr class="wp-header-end">

    <!-- Control Bar -->
    <div class="rooming-controls">
        <div style="display:flex; gap:10px; align-items:center;">
            <strong>Tambah Kamar Baru:</strong>
            <button type="button" class="button" onclick="addRoom('quad')">+ Quad (4)</button>
            <button type="button" class="button" onclick="addRoom('triple')">+ Triple (3)</button>
            <button type="button" class="button" onclick="addRoom('double')">+ Double (2)</button>
        </div>
        <div>
            <span id="save-status" style="margin-right:10px; color:#46b450; font-weight:bold; display:none;">Tersimpan!</span>
            <button type="button" class="button button-primary button-large" onclick="saveRooming()">Simpan Perubahan</button>
            <button type="button" class="button button-secondary" onclick="exportExcel()">Export Excel</button>
        </div>
    </div>

    <div class="rooming-container">
        <!-- Sidebar: Unassigned Passengers -->
        <div class="pax-sidebar">
            <h3>Jamaah Belum Dapat Kamar (<span id="unassigned-count"><?php echo count($unassigned); ?></span>)</h3>
            <div id="pool-unassigned" class="pax-pool" ondrop="drop(event)" ondragover="allowDrop(event)">
                <?php foreach ($unassigned as $pax): ?>
                    <div id="pax-<?php echo $pax->id; ?>" class="pax-card <?php echo ($pax->is_tour_leader ? 'is-tl' : ''); ?>" draggable="true" ondragstart="drag(event)" data-id="<?php echo $pax->id; ?>">
                        <strong><?php echo esc_html($pax->name); ?></strong><br>
                        <small><?php echo ($pax->name != strtoupper($pax->name) ? 'F' : 'M'); // Dummy Gender Logic ?> | <?php echo $pax->id; ?></small>
                        <?php if ($pax->is_tour_leader): ?><span class="badge-tl">TL</span><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Area: Rooms -->
        <div class="rooms-area" id="rooms-container">
            <?php foreach ($rooms as $room): ?>
                <div class="room-box room-<?php echo esc_attr($room['type']); ?>" data-number="<?php echo esc_attr($room['number']); ?>" data-type="<?php echo esc_attr($room['type']); ?>">
                    <div class="room-header">
                        <span class="room-title">No. <input type="text" value="<?php echo esc_attr($room['number']); ?>" class="room-num-input" onchange="updateRoomNumber(this)"></span>
                        <span class="room-type-badge"><?php echo strtoupper($room['type']); ?></span>
                        <span class="remove-room" onclick="removeRoom(this)">×</span>
                    </div>
                    <div class="room-slots" ondrop="drop(event)" ondragover="allowDrop(event)">
                        <?php foreach ($room['occupants'] as $occ): ?>
                             <div id="pax-<?php echo $occ->id; ?>" class="pax-card <?php echo ($occ->is_tour_leader ? 'is-tl' : ''); ?>" draggable="true" ondragstart="drag(event)" data-id="<?php echo $occ->id; ?>">
                                <strong><?php echo esc_html($occ->name); ?></strong><br>
                                <small>ID: <?php echo $occ->id; ?></small>
                                <?php if ($occ->is_tour_leader): ?><span class="badge-tl">TL</span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CSS Styles -->
    <style>
        .rooming-controls { background:#fff; padding:15px; border:1px solid #ccc; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:32px; z-index:100; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        .rooming-container { display: flex; gap: 20px; align-items: flex-start; }
        
        .pax-sidebar { width: 250px; background: #e5e7eb; padding: 15px; border-radius: 8px; min-height: 500px; }
        .pax-pool { min-height: 400px; border: 2px dashed #9ca3af; background: #f3f4f6; padding: 10px; border-radius: 6px; }
        
        .rooms-area { flex: 1; display: flex; flex-wrap: wrap; gap: 15px; align-content: flex-start; }
        
        .room-box { width: 220px; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .room-box:hover { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        .room-header { background: #f9fafb; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .room-num-input { width: 50px; padding: 2px 5px; font-size: 12px; font-weight: bold; border: 1px solid #ccc; }
        .room-type-badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; background: #dbeafe; color: #1e40af; font-weight: bold; }
        .remove-room { cursor: pointer; color: #ef4444; font-weight: bold; font-size: 16px; }
        
        .room-slots { min-height: 100px; padding: 10px; }
        
        /* Warna Border berdasarkan Tipe */
        .room-quad { border-top: 4px solid #3b82f6; }   /* Blue */
        .room-triple { border-top: 4px solid #10b981; } /* Green */
        .room-double { border-top: 4px solid #f59e0b; } /* Orange */

        .pax-card { background: #fff; padding: 8px; border: 1px solid #e5e7eb; border-radius: 4px; margin-bottom: 5px; cursor: move; font-size: 12px; position:relative; }
        .pax-card:hover { border-color: #2271b1; background: #f0f7ff; }
        .pax-card.is-tl { border-left: 3px solid #d63638; }
        .badge-tl { position: absolute; top: 2px; right: 2px; background: #d63638; color: #fff; font-size: 9px; padding: 1px 3px; border-radius: 2px; }
    </style>

    <!-- JS Logic -->
    <script>
        function allowDrop(ev) {
            ev.preventDefault();
        }

        function drag(ev) {
            ev.dataTransfer.setData("text", ev.target.id);
        }

        function drop(ev) {
            ev.preventDefault();
            var data = ev.dataTransfer.getData("text");
            var target = ev.target;

            // Pastikan drop di container, bukan di dalam kartu lain
            if (target.classList.contains("pax-card")) {
                target = target.parentNode;
            }
            
            // Limitasi jumlah pax per kamar
            if (target.classList.contains("room-slots")) {
                var roomBox = target.closest('.room-box');
                var type = roomBox.getAttribute('data-type');
                var limit = (type === 'quad' ? 4 : (type === 'triple' ? 3 : 2));
                
                if (target.children.length >= limit) {
                    alert('Kamar ' + type.toUpperCase() + ' maksimal ' + limit + ' orang!');
                    return;
                }
            }
            
            target.appendChild(document.getElementById(data));
            updateCounts();
        }

        function addRoom(type) {
            var container = document.getElementById('rooms-container');
            var existingRooms = container.querySelectorAll('.room-box').length;
            var newNum = 101 + existingRooms; // Auto number logic sederhana
            
            var div = document.createElement('div');
            div.className = 'room-box room-' + type;
            div.setAttribute('data-number', newNum);
            div.setAttribute('data-type', type);
            
            div.innerHTML = `
                <div class="room-header">
                    <span class="room-title">No. <input type="text" value="${newNum}" class="room-num-input" onchange="updateRoomNumber(this)"></span>
                    <span class="room-type-badge">${type.toUpperCase()}</span>
                    <span class="remove-room" onclick="removeRoom(this)">×</span>
                </div>
                <div class="room-slots" ondrop="drop(event)" ondragover="allowDrop(event)"></div>
            `;
            
            container.appendChild(div);
        }

        function removeRoom(el) {
            if(confirm('Hapus kamar ini? Penumpang di dalamnya akan kembali ke daftar belum dapat kamar.')) {
                var roomBox = el.closest('.room-box');
                var slots = roomBox.querySelector('.room-slots');
                var pool = document.getElementById('pool-unassigned');
                
                // Pindahkan penumpang kembali ke pool
                while (slots.childNodes.length > 0) {
                    pool.appendChild(slots.childNodes[0]);
                }
                
                roomBox.remove();
                updateCounts();
            }
        }
        
        function updateRoomNumber(input) {
            var roomBox = input.closest('.room-box');
            roomBox.setAttribute('data-number', input.value);
        }

        function updateCounts() {
            var count = document.getElementById('pool-unassigned').children.length;
            document.getElementById('unassigned-count').innerText = count;
        }

        function saveRooming() {
            var assignments = [];
            var rooms = document.querySelectorAll('.room-box');
            
            rooms.forEach(function(room) {
                var number = room.getAttribute('data-number');
                var type = room.getAttribute('data-type');
                var slots = room.querySelector('.room-slots').children;
                
                for (var i = 0; i < slots.length; i++) {
                    var paxId = slots[i].getAttribute('data-id');
                    assignments.push({
                        pax_id: paxId,
                        room_number: number,
                        room_type: type
                    });
                }
            });

            // Juga kirim yang unassigned (untuk reset jika dipindahkan kembali)
            var pool = document.getElementById('pool-unassigned').children;
            for (var i = 0; i < pool.length; i++) {
                assignments.push({
                    pax_id: pool[i].getAttribute('data-id'),
                    room_number: '', // Kosong = Unassigned
                    room_type: ''
                });
            }

            // AJAX Save
            var data = {
                action: 'umh_save_rooming',
                assignments: assignments
            };

            jQuery.post(ajaxurl, data, function(response) {
                if(response.success) {
                    var status = document.getElementById('save-status');
                    status.style.display = 'inline';
                    setTimeout(function() { status.style.display = 'none'; }, 3000);
                } else {
                    alert('Gagal menyimpan: ' + response.data);
                }
            });
        }
        
        function exportExcel() {
             // Logic export sederhana (bisa pakai window.open ke endpoint manifest dengan parameter khusus)
             alert('Fitur Export sedang dikembangkan. Gunakan fitur Manifest Generator untuk saat ini.');
        }
    </script>
</div>