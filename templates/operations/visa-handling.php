<?php
// File: visa-handling.php
// Location: templates/admin/operations/visa-handling.php

/** @var array $departures */
/** @var int $current_departure_id */
/** @var array $passengers */
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Visa & Document Handling</h1>
    <hr class="wp-header-end">

    <!-- Filter Bar -->
    <div style="background:#fff; padding:20px; margin-bottom:20px; border:1px solid #ccd0d4; display:flex; align-items:center; gap:15px;">
        <label style="font-weight:bold;">Pilih Keberangkatan:</label>
        <select onchange="window.location.href='admin.php?page=umh-visa-handling&departure_id='+this.value">
            <option value="">-- Pilih Jadwal --</option>
            <?php foreach ($departures as $dep): ?>
                <option value="<?php echo $dep->id; ?>" <?php selected($current_departure_id, $dep->id); ?>>
                    <?php echo date('d M Y', strtotime($dep->departure_date)); ?> - <?php echo esc_html($dep->package_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($current_departure_id && !empty($passengers)): ?>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="umh_update_visa_status">
            <?php wp_nonce_field('umh_visa_nonce'); ?>

            <!-- Bulk Actions -->
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="new_status" required>
                        <option value="">- Ubah Status Terpilih -</option>
                        <option value="pending">Document Collected (Di Kantor)</option>
                        <option value="submitted_provider">Submitted to Provider</option>
                        <option value="submitted_mofa">Proses MOFA/Kedutaan</option>
                        <option value="visa_issued">Visa Issued (Selesai)</option>
                        <option value="rejected">Rejected (Ditolak)</option>
                    </select>
                    <input type="submit" class="button action" value="Update Status">
                </div>
                <div class="alignleft actions">
                    <span style="line-height:28px; margin-left:10px;">Total: <strong><?php echo count($passengers); ?></strong> Pax</span>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th>Nama Jemaah</th>
                        <th>No. Paspor</th>
                        <th>File Paspor</th>
                        <th>Status Saat Ini</th>
                        <th>Booking Ref</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($passengers as $pax): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="pax_ids[]" value="<?php echo $pax->id; ?>">
                            </th>
                            <td>
                                <strong><?php echo esc_html($pax->name); ?></strong>
                                <?php if ($pax->is_tour_leader): ?><span style="color:red; font-size:10px;">(TL)</span><?php endif; ?>
                            </td>
                            <td><?php echo esc_html($pax->passport_number); ?></td>
                            <td>
                                <?php if (!empty($pax->passport_file_url)): ?>
                                    <a href="<?php echo esc_url($pax->passport_file_url); ?>" target="_blank" class="button button-small">Lihat</a>
                                <?php else: ?>
                                    <span style="color:red;">Belum Upload</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $status_labels = [
                                        'pending' => 'Di Kantor (Collected)',
                                        'submitted_provider' => 'Di Provider',
                                        'submitted_mofa' => 'Proses MOFA',
                                        'visa_issued' => '✅ Visa Issued',
                                        'rejected' => '❌ Rejected',
                                        'verified' => 'Dokumen OK' // Default dari upload
                                    ];
                                    $status_key = $pax->doc_verification_status ?: 'pending';
                                    
                                    $color = '#666';
                                    if ($status_key == 'visa_issued') $color = 'green';
                                    if ($status_key == 'submitted_mofa') $color = 'orange';
                                ?>
                                <span style="font-weight:bold; color:<?php echo $color; ?>;">
                                    <?php echo isset($status_labels[$status_key]) ? $status_labels[$status_key] : ucfirst($status_key); ?>
                                </span>
                            </td>
                            <td>#<?php echo $pax->booking_ref; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    <?php elseif ($current_departure_id): ?>
        <div style="background:#fff; padding:30px; text-align:center; border:1px solid #ddd;">
            <p>Belum ada penumpang yang lunas/terkonfirmasi untuk keberangkatan ini.</p>
        </div>
    <?php endif; ?>
    
    <script>
        document.getElementById('cb-select-all-1').addEventListener('click', function(e) {
            var checkboxes = document.querySelectorAll('input[name="pax_ids[]"]');
            checkboxes.forEach(function(cb) {
                cb.checked = e.target.checked;
            });
        });
    </script>
</div>