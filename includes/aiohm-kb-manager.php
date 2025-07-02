<?php
/**
 * Class AIOHM_KB_Manager
 *
 * Handles display and processing of the knowledge base admin page.
 */
class AIOHM_KB_Manager {

    /**
     * Render the knowledge base admin page.
     *
     * @return void
     */
    public function display_knowledge_base_page() {
        // Example: retrieving data from DB, POST, or API
        $data = $this->get_kb_data(); // adjust to your data source

        // Safely extract expected values
        $content_type = isset($data['content_type']) ? sanitize_text_field($data['content_type']) : '';
        $chunks = isset($data['chunks']) && is_array($data['chunks']) ? $data['chunks'] : [];

        // Now process the chunks (if any)
        if (!empty($chunks)) {
            echo '<div class="kb-chunks">';
            foreach ($chunks as $chunk_index => $chunk) {
                // Double-check chunk structure
                $title = isset($chunk['title']) ? esc_html($chunk['title']) : '';
                $body  = isset($chunk['body']) ? esc_textarea($chunk['body']) : '';

                echo '<div class="kb-chunk">';
                echo '<h3>Chunk #' . intval($chunk_index + 1) . '</h3>';
                echo '<p><strong>Title:</strong> ' . $title . '</p>';
                echo '<div><strong>Body:</strong><br>' . nl2br($body) . '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>No content chunks found in your knowledge base.</p>';
        }

        // Sample form to add a new chunk
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('aiohm_kb_add_chunk', 'aiohm_kb_nonce'); ?>
            <input type="hidden" name="content_type" value="<?php echo esc_attr($content_type); ?>">
            <h4>Add New Chunk</h4>
            <p>
                <label>Title:<br>
                    <input type="text" name="chunks[new][title]" style="width:100%;">
                </label>
            </p>
            <p>
                <label>Body:<br>
                    <textarea name="chunks[new][body]" rows="5" style="width:100%;"></textarea>
                </label>
            </p>
            <p>
                <button type="submit" name="submit_aiohm_kb" class="button button-primary">
                    Add Chunk
                </button>
            </p>
        </form>
        <?php
    }

    /**
     * Example method: fetch KB data (customize to your data source).
     *
     * @return array
     */
    private function get_kb_data() {
        // Replace with actual data retrieval (DB, option, API, etc.)
        return [
            'content_type' => get_option('aiohm_kb_content_type', ''),
            'chunks'       => get_option('aiohm_kb_chunks', []),
        ];
    }

    /**
     * Example method: handle post submission (you can hook into admin_init).
     *
     * @return void
     */
    public function handle_form_submission() {
        if (
            isset($_POST['submit_aiohm_kb']) &&
            wp_verify_nonce($_POST['aiohm_kb_nonce'], 'aiohm_kb_add_chunk')
        ) {
            $existing = $this->get_kb_data();
            $new_chunk = $_POST['chunks']['new'];

            $existing['chunks'][] = [
                'title' => sanitize_text_field($new_chunk['title']),
                'body'  => wp_kses_post($new_chunk['body']),
            ];

            // Update the options (or save however your plugin does)
            update_option('aiohm_kb_chunks', $existing['chunks']);
            update_option('aiohm_kb_content_type', $existing['content_type']);
        }
    }
}
