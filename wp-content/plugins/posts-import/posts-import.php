<?php
/*
Plugin Name: Import Custom Posts
Description: Import data from csv files
Version: 1.0
Author: Applaudo Studios
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CustomPostImporter {
    public function __construct() {
        add_action('admin_menu', array($this, 'cpi_create_menu'));
        add_action('admin_post_cpi_import_csv', array($this, 'cpi_import_csv'));
    }

    public function cpi_create_menu() {
        add_menu_page(
            'Custom Posts Importer',
            'Import Custom Posts',
            'manage_options',
            'custom-post-importer',
            array($this, 'cpi_import_page')
        );
    }

    public function cpi_import_page() {
        ?>
        <div class="wrap">
            <h1>Import Custom Posts from CSV</h1>
            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="cpi_import_csv">
                <input type="file" name="cpi_csv_file" accept=".csv" required>
                <input type="submit" value="Import CSV" class="button button-primary">
            </form>
        </div>
        <?php if (isset($_GET['imported']) && $_GET['imported'] == 'true') : ?>
            <div class="notice notice-success is-dismissible">
                <p>CSV data imported successfully!</p>
            </div>
        <?php endif; ?>
        <?php
    }

    public function cpi_import_csv() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_FILES['cpi_csv_file']) && $_FILES['cpi_csv_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cpi_csv_file']['tmp_name'];
            $this->cpi_process_csv($file);
        }

        wp_redirect(admin_url('admin.php?page=custom-post-importer&imported=true'));
        exit;
    }

    private function cpi_process_csv($file) {
        $csv_data = array_map('str_getcsv', file($file));
        $headers = array_map('sanitize_key', array_shift($csv_data));

        foreach ($csv_data as $row) {
            $row_data = array_combine($headers, $row);
            $this->cpi_import_post($row_data);
        }
    }

    private function cpi_import_post($data) {
        $post_type = $data['post_type'];
        $post_data = array(
            'post_title'    => sanitize_text_field($data['title']),
            'post_content'  => sanitize_textarea_field($data['content']),
            'post_status'   => 'publish',
            'post_type'     => $post_type,
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            // Assign ACFs and taxonomies specific to the post type.
            $this->cpi_assign_acfs_and_taxonomies($post_id, $post_type, $data);
        }
    }

    private function cpi_assign_acfs_and_taxonomies($post_id, $post_type, $data) {
        switch ($post_type) {
            case 'bios':
                update_field('position', sanitize_text_field($data['position']), $post_id);
    
                // Process repeater links
                if (isset($data['link_1_title']) && isset($data['link_1_url'])) {
                    $links = array();
                    for ($i = 1; isset($data["link_{$i}_title"]) && isset($data["link_{$i}_url"]); $i++) {
                        $links[] = array(
                            'link_title' => sanitize_text_field($data["link_{$i}_title"]),
                            'link_url'   => esc_url_raw($data["link_{$i}_url"]),
                        );
                    }
                    update_field('links', $links, $post_id);
                }
    
                update_field('linked_in_url', esc_url_raw($data['linked_in_url']), $post_id);
                update_field('instagram_profile_url', esc_url_raw($data['instagram_profile_url']), $post_id);
                update_field('youtube_channel_url', esc_url_raw($data['youtube_channel_url']), $post_id);
                break;
    
            case 'article':
                update_field('estimated_reading_time', sanitize_text_field($data['estimated_reading_time']), $post_id);
                break;
    
            case 'allie':
                update_field('url', esc_url_raw($data['url']), $post_id);
                break;
    
            case 'resource':
                update_field('resource_url', esc_url_raw($data['resource_url']), $post_id);
                
                // Set the target field
                update_field('target', sanitize_text_field($data['target']), $post_id);
    
                // Handle the radio button field for PDF
                $is_pdf = strtolower(sanitize_text_field($data['is_it_a_pdf_the_resource_url'])) === 'yes' ? 'Yes' : 'No';
                update_field('is_it_a_pdf_the_resource_url', $is_pdf, $post_id);
    
                // Handle the PDF upload
                if (!empty($data['pdf'])) {
                    $pdf_id = $this->cpi_upload_pdf($data['pdf'], $post_id);
                    if ($pdf_id) {
                        update_field('pdf', $pdf_id, $post_id);
                    }
                }
                break;
        }
    
        // Assign taxonomies correctly
        $this->cpi_assign_taxonomies($post_id, $data);
    }    
    

    private function cpi_assign_taxonomies($post_id, $data) {
        // Assign each taxonomy if it exists in the CSV
        $taxonomies = ['category', 'topic', 'post_tag', 'bio-category'];
        foreach ($taxonomies as $taxonomy) {
            if (isset($data[$taxonomy])) {
                $terms = array_map('sanitize_text_field', explode('|', $data[$taxonomy]));
                $term_ids = [];
                foreach ($terms as $term) {
                    $term_obj = term_exists($term, $taxonomy);
                    if ($term_obj === 0 || $term_obj === null) {
                        $term_obj = wp_insert_term($term, $taxonomy);
                    }
                    if (!is_wp_error($term_obj)) {
                        $term_ids[] = (int)$term_obj['term_id'];
                    }
                }
                wp_set_post_terms($post_id, $term_ids, $taxonomy);
            }
        }
    }

    private function cpi_upload_pdf($pdf_url, $post_id) {
        $tmp = download_url($pdf_url);
        if (is_wp_error($tmp)) {
            return false;
        }
    
        $file_array = array(
            'name'     => basename($pdf_url),
            'tmp_name' => $tmp,
        );
    
        $pdf_id = media_handle_sideload($file_array, $post_id);
    
        if (is_wp_error($pdf_id)) {
            @unlink($tmp); // clean up
            return false;
        }
    
        return $pdf_id;
    }
}

new CustomPostImporter();