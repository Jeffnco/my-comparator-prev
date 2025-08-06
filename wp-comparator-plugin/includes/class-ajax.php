<?php

class WP_Comparator_Ajax {
    
    public function __construct() {
        // Actions AJAX pour les utilisateurs connectés
        add_action('wp_ajax_wp_comparator_delete_type', array($this, 'delete_type'));
        add_action('wp_ajax_wp_comparator_delete_category', array($this, 'delete_category'));
        add_action('wp_ajax_wp_comparator_delete_field', array($this, 'delete_field'));
        add_action('wp_ajax_wp_comparator_delete_item', array($this, 'delete_item'));
        add_action('wp_ajax_wp_comparator_update_field_order', array($this, 'update_field_order'));
        add_action('wp_ajax_wp_comparator_get_categories', array($this, 'get_categories'));
        add_action('wp_ajax_wp_comparator_get_fields', array($this, 'get_fields'));
        add_action('wp_ajax_wp_comparator_create_comparison_page', array($this, 'create_comparison_page'));
        add_action('wp_ajax_nopriv_wp_comparator_create_comparison_page', array($this, 'create_comparison_page'));
        
        // Actions AJAX pour le frontend (utilisateurs non connectés aussi)
        add_action('wp_ajax_wp_comparator_filter_items', array($this, 'filter_items'));
        add_action('wp_ajax_nopriv_wp_comparator_filter_items', array($this, 'filter_items'));
    }
    
    public function delete_type() {
        check_ajax_referer('wp_comparator_nonce', 'nonce');
        
        if (!current_user_can('manage_comparator')) {
            wp_die('Permissions insuffisantes');
        }
        
        $type_id = intval($_POST['type_id']);
        
        global $wpdb;
        $table_types = $wpdb->prefix . 'comparator_types';
        
        $result = $wpdb->delete($table_types, array('id' => $type_id), array('%d'));
        
        if ($result) {
            wp_send_json_success('Type supprimé avec succès');
        } else {
            wp_send_json_error('Erreur lors de la suppression');
        }
    }
    
    public function delete_category() {
        check_ajax_referer('wp_comparator_nonce', 'nonce');
        
        if (!current_user_can('manage_comparator')) {
            wp_die('Permissions insuffisantes');
        }
        
        $category_id = intval($_POST['category_id']);
        
        global $wpdb;
        $table_categories = $wpdb->prefix . 'comparator_categories';
        
        $result = $wpdb->delete($table_categories, array('id' => $category_id), array('%d'));
        
        if ($result) {
            wp_send_json_success('Catégorie supprimée avec succès');
        } else {
            wp_send_json_error('Erreur lors de la suppression');
        }
    }
    
    public function delete_field() {
        check_ajax_referer('wp_comparator_nonce', 'nonce');
        
        if (!current_user_can('manage_comparator')) {
            wp_die('Permissions insuffisantes');
        }
        
        $field_id = intval($_POST['field_id']);
        
        global $wpdb;
        $table_fields = $wpdb->prefix . 'comparator_fields';
        
        $result = $wpdb->delete($table_fields, array('id' => $field_id), array('%d'));
        
        if ($result) {
            wp_send_json_success('Champ supprimé avec succès');
        } else {
            wp_send_json_error('Erreur lors de la suppression');
        }
    }
    
    public function delete_item() {
        check_ajax_referer('wp_comparator_nonce', 'nonce');
        
        if (!current_user_can('manage_comparator')) {
            wp_die('Permissions insuffisantes');
        }
        
        $item_id = intval($_POST['item_id']);
        
        global $wpdb;
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $result = $wpdb->delete($table_items, array('id' => $item_id), array('%d'));
        
        if ($result) {
            wp_send_json_success('Élément supprimé avec succès');
        } else {
            wp_send_json_error('Erreur lors de la suppression');
        }
    }
    
    public function update_field_order() {
        check_ajax_referer('wp_comparator_nonce', 'nonce');
        
        if (!current_user_can('manage_comparator')) {
            wp_die('Permissions insuffisantes');
        }
        
        $field_orders = $_POST['field_orders'];
        
        global $wpdb;
        $table_fields = $wpdb->prefix . 'comparator_fields';
        
        foreach ($field_orders as $field_id => $order) {
            $wpdb->update(
                $table_fields,
                array('sort_order' => intval($order)),
                array('id' => intval($field_id)),
                array('%d'),
                array('%d')
            );
        }
        
        wp_send_json_success('Ordre mis à jour');
    }
    
    public function get_categories() {
        check_ajax_referer('wp_comparator_nonce', 'nonce');
        
        $type_id = intval($_POST['type_id']);
        
        global $wpdb;
        $table_categories = $wpdb->prefix . 'comparator_categories';
        
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_categories WHERE type_id = %d ORDER BY sort_order",
            $type_id
        ));
        
        wp_send_json_success($categories);
    }
    
    public function get_fields() {
        check_ajax_referer('wp_comparator_nonce', 'nonce');
        
        $category_id = intval($_POST['category_id']);
        
        global $wpdb;
        $table_fields = $wpdb->prefix . 'comparator_fields';
        
        $fields = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_fields WHERE category_id = %d ORDER BY sort_order",
            $category_id
        ));
        
        wp_send_json_success($fields);
    }
    
    public function filter_items() {
        check_ajax_referer('wp_comparator_frontend_nonce', 'nonce');
        
        $type_slug = sanitize_text_field($_POST['type_slug']);
        $filters = $_POST['filters'];
        
        global $wpdb;
        
        // Récupérer le type
        $table_types = $wpdb->prefix . 'comparator_types';
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $type_slug
        ));
        
        if (!$type) {
            wp_send_json_error('Type non trouvé');
        }
        
        // Construire la requête avec filtres
        $table_items = $wpdb->prefix . 'comparator_items';
        $table_values = $wpdb->prefix . 'comparator_values';
        $table_fields = $wpdb->prefix . 'comparator_fields';
        
        $where_conditions = array("i.type_id = %d", "i.is_active = 1");
        $where_values = array($type->id);
        
        if (!empty($filters)) {
            foreach ($filters as $field_id => $filter_value) {
                if (!empty($filter_value)) {
                    $field_id = intval($field_id);
                    
                    if (is_array($filter_value)) {
                        // Filtres multiples (checkbox)
                        $placeholders = implode(',', array_fill(0, count($filter_value), '%s'));
                        $where_conditions[] = "EXISTS (
                            SELECT 1 FROM $table_values v 
                            WHERE v.item_id = i.id 
                            AND v.field_id = %d 
                            AND v.value IN ($placeholders)
                        )";
                        $where_values[] = $field_id;
                        $where_values = array_merge($where_values, $filter_value);
                    } else {
                        // Filtre simple
                        $where_conditions[] = "EXISTS (
                            SELECT 1 FROM $table_values v 
                            WHERE v.item_id = i.id 
                            AND v.field_id = %d 
                            AND v.value = %s
                        )";
                        $where_values[] = $field_id;
                        $where_values[] = $filter_value;
                    }
                }
            }
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "SELECT DISTINCT i.* FROM $table_items i WHERE $where_clause ORDER BY i.sort_order, i.name";
        
        $items = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        // Générer le HTML des éléments filtrés
        ob_start();
        foreach ($items as $item) {
            include WP_COMPARATOR_PLUGIN_DIR . 'templates/frontend/item-card.php';
        }
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'count' => count($items)
        ));
    }
    
    /**
     * Créer une page de comparaison via AJAX
     */
    public function create_comparison_page() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wp_comparator_frontend_nonce')) {
            wp_send_json_error('Erreur de sécurité');
        }
        
        $type_slug = sanitize_text_field($_POST['type_slug']);
        $item1_slug = sanitize_text_field($_POST['item1_slug']);
        $item2_slug = sanitize_text_field($_POST['item2_slug']);
        
        // Debug - log des données reçues
        error_log("AJAX create_comparison_page - type: $type_slug, item1: $item1_slug, item2: $item2_slug");
        
        // Créer une nouvelle page WordPress via la classe Pages
        $pages_class = new WP_Comparator_Pages();
        $result = $pages_class->create_wordpress_page($type_slug, $item1_slug, $item2_slug);
        
        if ($result && isset($result['page_id'])) {
            $page_url = get_permalink($result['page_id']);
            error_log("Page créée/trouvée - ID: {$result['page_id']}, URL: $page_url");
            
            wp_send_json_success(array(
                'url' => $page_url,
                'page_id' => $result['page_id'],
                'existing' => $result['existing']
            ));
        } else {
            error_log("Erreur création page - result: " . print_r($result, true));
            wp_send_json_error('Erreur lors de la création de la page');
        }
    }
}