<?php 
$isEdit = !empty($lead); 
$actionUrl = admin_url('admin-post.php');
?>
<div class="wrap">
    <h1><?php echo $isEdit ? 'Edit Prospek' : 'Input Prospek Baru'; ?></h1>
    <form method="post" action="<?php echo esc_url($actionUrl); ?>">
        <input type="hidden" name="action" value="umh_save_lead">
        <?php wp_nonce_field('save_lead', 'umh_lead_nonce'); ?>
        <?php if($isEdit): ?><input type="hidden" name="id" value="<?php echo $lead->id; ?>"><?php endif; ?>

        <div class="postbox" style="max-width: 800px; padding: 20px;">
            <table class="form-table">
                <tr>
                    <th>Nama Lengkap <span style="color:red">*</span></th>
                    <td><input type="text" name="name" class="regular-text" value="<?php echo $isEdit ? esc_attr($lead->name) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th>No. WhatsApp <span style="color:red">*</span></th>
                    <td><input type="text" name="phone" class="regular-text" value="<?php echo $isEdit ? esc_attr($lead->phone) : ''; ?>" required placeholder="0812xxx"></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><input type="email" name="email" class="regular-text" value="<?php echo $isEdit ? esc_attr($lead->email) : ''; ?>"></td>
                </tr>
                <tr>
                    <th>Sumber Info</th>
                    <td>
                        <select name="source">
                            <option value="walk_in" <?php selected($isEdit ? $lead->source : '', 'walk_in'); ?>>Datang Langsung</option>
                            <option value="whatsapp" <?php selected($isEdit ? $lead->source : '', 'whatsapp'); ?>>WhatsApp</option>
                            <option value="facebook" <?php selected($isEdit ? $lead->source : '', 'facebook'); ?>>Facebook/IG</option>
                            <option value="referral" <?php selected($isEdit ? $lead->source : '', 'referral'); ?>>Rekomendasi Teman</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Status Prospek</th>
                    <td>
                        <select name="status">
                            <option value="new" <?php selected($isEdit ? $lead->status : '', 'new'); ?>>Baru (New)</option>
                            <option value="follow_up" <?php selected($isEdit ? $lead->status : '', 'follow_up'); ?>>Sedang Follow Up</option>
                            <option value="closing" <?php selected($isEdit ? $lead->status : '', 'closing'); ?>>Closing (Deal)</option>
                            <option value="lost" <?php selected($isEdit ? $lead->status : '', 'lost'); ?>>Gagal/Batal</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Minat Paket</th>
                    <td><input type="text" name="interested_in" class="regular-text" value="<?php echo $isEdit ? esc_attr($lead->interested_in) : ''; ?>" placeholder="Misal: Paket Ramadhan"></td>
                </tr>
                <tr>
                    <th>Tanggal Follow Up</th>
                    <td><input type="date" name="follow_up_date" value="<?php echo $isEdit ? esc_attr($lead->follow_up_date) : ''; ?>"></td>
                </tr>
                <tr>
                    <th>Catatan Sales</th>
                    <td><textarea name="notes" rows="4" class="large-text"><?php echo $isEdit ? esc_textarea($lead->notes) : ''; ?></textarea></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button button-primary" value="Simpan Data">
                <a href="admin.php?page=umroh-leads" class="button">Kembali</a>
                
                <?php if($isEdit): ?>
                <a href="<?php echo wp_nonce_url('admin-post.php?action=umh_delete_lead&id='.$lead->id, 'delete_lead'); ?>" class="button button-link-delete" onclick="return confirm('Hapus permanen?')">Hapus</a>
                <?php endif; ?>
            </p>
        </div>
    </form>
</div>