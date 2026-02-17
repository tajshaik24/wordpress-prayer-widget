<?php 
if(!is_user_logged_in()) {
    return;
}

$Save = isset($_POST['Save']);

if($Save) { 
    // Get POST data
    $masjid_id_1 = sanitize_text_field($_POST['masjid_id_1'] ?? '');
    $masjid_id_2 = sanitize_text_field($_POST['masjid_id_2'] ?? '');
    $masjid_name_1 = sanitize_text_field($_POST['masjid_name_1'] ?? '');
    $masjid_name_2 = sanitize_text_field($_POST['masjid_name_2'] ?? '');
    $api_key = sanitize_text_field($_POST['masjidi_api_key'] ?? '');
    $highlighted_color = sanitize_text_field($_POST['highlighted_color'] ?? '');
    $highlighted_text_color = sanitize_text_field($_POST['highlighted_text_color'] ?? '');
    $ramadan_timetable_url = esc_url_raw($_POST['ramadan_timetable_url'] ?? '');

    // Save settings
    update_option('masjid_id', $masjid_id_1);
    update_option('masjid_id_1', $masjid_id_1);
    update_option('masjid_id_2', $masjid_id_2);
    update_option('masjid_name_1', $masjid_name_1);
    update_option('masjid_name_2', $masjid_name_2);
    update_option('masjidi_api_key', $api_key);
    update_option('highlighted_color', $highlighted_color);
    update_option('highlighted_text_color', $highlighted_text_color);
    update_option('ramadan_timetable_url', $ramadan_timetable_url);
?>
<div class="alert alert-success alert-dismissible" style="margin-top:18px; padding: 12px 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px; color: #155724;">
    <a href="#" class="close newclose" data-dismiss="alert" aria-label="close" title="close" style="float: right; font-size: 20px; text-decoration: none; color: #155724;">×</a>
    <?php echo esc_html('Settings saved successfully!');?>
</div>
<?php } ?>

<div class="masjid_details" style="max-width: 800px;">
<?php

// Get saved options
$masjid_id_1 = get_option('masjid_id_1', get_option('masjid_id', '3443'));
$masjid_id_2 = get_option('masjid_id_2', '');
$masjid_name_1 = get_option('masjid_name_1', '');
$masjid_name_2 = get_option('masjid_name_2', '');
$api_key = get_option('masjidi_api_key', '123-test-key');
$highlighted_color = get_option('highlighted_color', '#1e7b34');
$highlighted_text_color = get_option('highlighted_text_color', '#ffffff');
$ramadan_timetable_url = get_option('ramadan_timetable_url', '');

if(empty($highlighted_color)){
    $highlighted_color = '#1e7b34';
}
if(empty($highlighted_text_color)){
    $highlighted_text_color = '#ffffff';
}
?>

<h2 style="margin-bottom: 5px;"><?php echo esc_html('Prayer Times Widget Settings');?></h2>
<p style="color: #666; margin-bottom: 25px; margin-top: 0;">Configure your masjids. Data is pulled from the <a href="https://www.masjidiapp.com" target="_blank">Masjidi API</a>.</p>

<form method="post">

<div style="display: flex; gap: 25px; flex-wrap: wrap; margin-bottom: 25px;">
    <div style="flex: 1; min-width: 280px; background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0;">
        <h4 style="margin-top: 0; margin-bottom: 15px; color: #1e7b34; font-size: 16px;">🕌 Primary Masjid</h4>
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;"><?php echo esc_html('Masjid ID');?></label>
            <input style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;" type="text" maxlength="50" name="masjid_id_1" id="masjid_id_1" value="<?php echo esc_attr($masjid_id_1); ?>" required placeholder="e.g., 3443">
        </div>
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;"><?php echo esc_html('Display Name');?></label>
            <input style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;" type="text" maxlength="30" name="masjid_name_1" id="masjid_name_1" value="<?php echo esc_attr($masjid_name_1); ?>" placeholder="e.g., ICF">
        </div>
    </div>
    
    <div style="flex: 1; min-width: 280px; background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0;">
        <h4 style="margin-top: 0; margin-bottom: 15px; color: #0066cc; font-size: 16px;">🕌 Secondary Masjid <span style="font-weight: normal; color: #888; font-size: 12px;">(Optional)</span></h4>
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;"><?php echo esc_html('Masjid ID');?></label>
            <input style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;" type="text" maxlength="50" name="masjid_id_2" id="masjid_id_2" value="<?php echo esc_attr($masjid_id_2); ?>" placeholder="e.g., 50059">
        </div>
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;"><?php echo esc_html('Display Name');?></label>
            <input style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;" type="text" maxlength="30" name="masjid_name_2" id="masjid_name_2" value="<?php echo esc_attr($masjid_name_2); ?>" placeholder="e.g., Masjid Zakariya">
        </div>
    </div>
</div>

<div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 25px;">
    <h4 style="margin-top: 0; margin-bottom: 15px; color: #333; font-size: 16px;">🔑 API Key</h4>
    <div style="margin-bottom: 10px;">
        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;"><?php echo esc_html('Masjidi API Key');?></label>
        <input style="width: 100%; max-width: 400px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;" type="text" name="masjidi_api_key" id="masjidi_api_key" value="<?php echo esc_attr($api_key); ?>" placeholder="123-test-key">
    </div>
    <p style="margin: 0; font-size: 12px; color: #666;">Default test key: <code>123-test-key</code>. For production, <a href="https://wa.me/15305086624" target="_blank">contact MasjidiApp</a> to get your own API key.</p>
</div>

<div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 25px;">
    <h4 style="margin-top: 0; margin-bottom: 15px; color: #333; font-size: 16px;">🎨 Colors</h4>
    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;"><?php echo esc_html('Active Prayer Highlight');?></label>
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="color" name="highlighted_color" value="<?php echo esc_attr($highlighted_color);?>" style="width: 50px; height: 35px; border: none; cursor: pointer;">
                <code style="background: #eee; padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?php echo esc_html($highlighted_color);?></code>
            </div>
        </div>
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;"><?php echo esc_html('Highlight Text Color');?></label>
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="color" name="highlighted_text_color" value="<?php echo esc_attr($highlighted_text_color);?>" style="width: 50px; height: 35px; border: none; cursor: pointer;">
                <code style="background: #eee; padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?php echo esc_html($highlighted_text_color);?></code>
            </div>
        </div>
    </div>
</div>

<div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 25px;">
    <h4 style="margin-top: 0; margin-bottom: 15px; color: #333; font-size: 16px;">🌙 Ramadan Timetable</h4>
    <div style="margin-bottom: 10px;">
        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;"><?php echo esc_html('Timetable URL');?></label>
        <input style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;" type="url" name="ramadan_timetable_url" id="ramadan_timetable_url" value="<?php echo esc_attr($ramadan_timetable_url); ?>" placeholder="https://example.com/ramadan-timetable.pdf">
    </div>
    <p style="margin: 0; font-size: 12px; color: #666;">When set, a "View Ramadan Timetable" link will appear at the bottom of the widget.</p>
</div>

<div style="margin-bottom: 25px;">
    <input type="submit" value="<?php esc_attr_e('Save Settings');?>" name="Save" class="button button-primary" style="padding: 8px 25px; font-size: 14px;">
</div>

</form>

<div style="background: #e8f4fd; padding: 20px; border-radius: 8px; border: 1px solid #b8daff;">
    <h4 style="margin-top: 0; margin-bottom: 10px; color: #004085;">📋 How to Use</h4>
    <p style="margin-bottom: 10px; color: #004085;">Add this shortcode to any page or post:</p>
    <code style="background: #fff; padding: 10px 15px; display: block; border-radius: 4px; font-size: 14px; color: #333; border: 1px solid #ccc;">[masjidi_prayer_times]</code>
    <p style="margin-bottom: 0; margin-top: 10px; color: #666; font-size: 13px;">Or use the <strong>"Prayer Times Widget"</strong> in Appearance → Widgets</p>
</div>

</div>

<script>
jQuery("a.newclose").click(function(e){
    e.preventDefault();
    jQuery(this).parent().hide();
});
</script>
<?php
