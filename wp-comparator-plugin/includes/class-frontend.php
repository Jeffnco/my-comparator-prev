<?php

class WP_Comparator_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('wp_comparator', array($this, 'shortcode_comparator'));
        add_shortcode('wp_comparator_compare', array($this, 'shortcode_comparator_compare'));
        add_shortcode('wp_comparator_single', array($this, 'shortcode_comparator_single'));
        
        // Gérer les paramètres d'URL pour les pages de comparaison et single
        add_action('template_redirect', array($this, 'handle_url_params'));
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('wp-comparator-frontend', WP_COMPARATOR_PLUGIN_URL . 'assets/css/frontend.css', array(), WP_COMPARATOR_VERSION);
        wp_enqueue_script('wp-comparator-frontend', WP_COMPARATOR_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WP_COMPARATOR_VERSION, true);
        
        wp_localize_script('wp-comparator-frontend', 'wpComparatorFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_comparator_frontend_nonce'),
            'maxSelection' => get_option('wp_comparator_max_comparison', 2),
            'currentTypeSlug' => isset($_GET['type']) ? sanitize_text_field($_GET['type']) : ''
        ));
    }
    
    /**
     * Gérer les paramètres d'URL pour afficher les bonnes vues
     */
    public function handle_url_params() {
        // Gérer le paramètre ?compare=item1,item2&type=type_slug
        if (isset($_GET['compare']) && isset($_GET['type'])) {
            $compare_items = sanitize_text_field($_GET['compare']);
            $type_slug = sanitize_text_field($_GET['type']);
            
            $items = explode(',', $compare_items);
            if (count($items) === 2) {
                // Rediriger vers une page de comparaison ou afficher directement
                $this->display_comparison_page($type_slug, $items[0], $items[1]);
                exit;
            }
        }
        
        // Gérer le paramètre ?single=item_slug&type=type_slug
        if (isset($_GET['single']) && isset($_GET['type'])) {
            $item_slug = sanitize_text_field($_GET['single']);
            $type_slug = sanitize_text_field($_GET['type']);
            
            $this->display_single_page($type_slug, $item_slug);
            exit;
        }
    }
    
    /**
     * Afficher la page de comparaison directement
     */
    private function display_comparison_page($type_slug, $item1_slug, $item2_slug) {
        // Utiliser le shortcode de comparaison
        echo do_shortcode("[wp_comparator_compare type=\"$type_slug\" items=\"$item1_slug,$item2_slug\"]");
    }
    
    /**
     * Afficher la page single directement
     */
    private function display_single_page($type_slug, $item_slug) {
        // Utiliser le shortcode single
        echo do_shortcode("[wp_comparator_single type=\"$type_slug\" item=\"$item_slug\"]");
    }
    
    /**
     * Shortcode principal pour afficher la grille de sélection
     */
    public function shortcode_comparator($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'columns' => '3',
            'show_filters' => 'true'
        ), $atts);
        
        if (empty($atts['type'])) {
            return '<p>Erreur: Type de comparateur non spécifié.</p>';
        }
        
        global $wpdb;
        
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_items = $wpdb->prefix . 'comparator_items';
        $table_fields = $wpdb->prefix . 'comparator_fields';
        
        // Récupérer le type
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $atts['type']
        ));
        
        if (!$type) {
            return '<p>Erreur: Type de comparateur non trouvé.</p>';
        }
        
        // Récupérer les éléments actifs
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_items WHERE type_id = %d AND is_active = 1 ORDER BY sort_order, name",
            $type->id
        ));
        
        // Récupérer les champs filtrables
        $filterable_fields = array();
        if ($atts['show_filters'] === 'true') {
            $filterable_fields = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_fields WHERE type_id = %d AND is_filterable = 1 ORDER BY sort_order",
                $type->id
            ));
        }
        
        // Générer le HTML
        ob_start();
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/frontend/grid.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour comparer deux éléments
     */
    public function shortcode_comparator_compare($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'items' => ''
        ), $atts);
        
        // DEBUG: Afficher les informations de debug
        echo '<div style="background: #ff0000; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; font-family: monospace;">';
        echo '<strong>🔍 DEBUG SHORTCODE COMPARE:</strong><br>';
        echo 'Type recherché: "' . $atts['type'] . '"<br>';
        echo 'Items: "' . $atts['items'] . '"<br>';
        echo 'Préfixe BDD: "' . $GLOBALS['wpdb']->prefix . '"<br>';
        echo '</div>';
        
        // DEBUG: Afficher le slug recherché
        echo '<div style="background: red; color: white; padding: 10px; margin: 10px 0; border-radius: 5px;">';
        echo '<strong>DEBUG:</strong><br>';
        echo 'Slug recherché: "' . $atts['type'] . '"<br>';
        echo 'Slug en BDD: "assurance-prevoyance"<br>';
        echo 'Match: ' . ($atts['type'] === 'assurance-prevoyance' ? 'OUI' : 'NON');
        echo '</div>';
        
        if (empty($atts['type']) || empty($atts['items'])) {
            echo '<div style="background: #ff6600; color: white; padding: 10px; margin: 10px 0;">ERREUR: Paramètres manquants</div>';
            return '<p>Erreur: Paramètres manquants pour la comparaison.</p>';
        }
        
        global $wpdb;
        
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_items = $wpdb->prefix . 'comparator_items';
        
        // DEBUG: Afficher la requête SQL
        echo '<div style="background: #0066cc; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; font-family: monospace;">';
        echo '<strong>🔍 DEBUG SQL:</strong><br>';
        echo 'Table utilisée: "' . $table_types . '"<br>';
        $sql_debug = $wpdb->prepare("SELECT * FROM $table_types WHERE slug = %s", $atts['type']);
        echo 'Requête SQL: ' . $sql_debug . '<br>';
        echo '</div>';
        
        // Récupérer le type
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $atts['type']
        ));
        
        // DEBUG: Afficher le résultat
        echo '<div style="background: #009900; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; font-family: monospace;">';
        echo '<strong>🔍 DEBUG RÉSULTAT:</strong><br>';
        if ($type) {
            echo 'Type trouvé: ID=' . $type->id . ', Name="' . $type->name . '", Slug="' . $type->slug . '"<br>';
        } else {
            echo 'AUCUN TYPE TROUVÉ !<br>';
            echo 'Erreur SQL: ' . $wpdb->last_error . '<br>';
        }
        echo '</div>';
        
        if (!$type) {
            return '<p>Erreur: Type de comparateur non trouvé.</p>';
        }
        
        // Parser les éléments à comparer
        $item_slugs = explode(',', $atts['items']);
        if (count($item_slugs) !== 2) {
            return '<p>Erreur: Exactement 2 éléments requis pour la comparaison.</p>';
        }
        
        // Récupérer les éléments
        $item1 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE slug = %s AND type_id = %d",
            trim($item_slugs[0]), $type->id
        ));
        
        $item2 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE slug = %s AND type_id = %d",
            trim($item_slugs[1]), $type->id
        ));
        
        if (!$item1 || !$item2) {
            return '<p>Erreur: Un ou plusieurs éléments non trouvés.</p>';
        }
        
        // Récupérer les données de comparaison
        $comparison_data = $this->get_comparison_data($type->id, array($item1->id, $item2->id));
        
        // Générer le HTML
        ob_start();
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/frontend/compare-page.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour afficher un seul élément
     */
    public function shortcode_comparator_single($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'item' => ''
        ), $atts);
        
        if (empty($atts['type']) || empty($atts['item'])) {
            return '<p>Erreur: Paramètres manquants.</p>';
        }
        
        global $wpdb;
        
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_items = $wpdb->prefix . 'comparator_items';
        
        // Récupérer le type
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $atts['type']
        ));
        
        if (!$type) {
            return '<p>Erreur: Type de comparateur non trouvé.</p>';
        }
        
        // Récupérer l'élément
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE slug = %s AND type_id = %d",
            $atts['item'], $type->id
        ));
        
        if (!$item) {
            return '<p>Erreur: Élément non trouvé.</p>';
        }
        
        // Récupérer les données de l'élément
        $item_data = $this->get_single_item_data($type->id, $item->id);
        
        // Générer le HTML
        ob_start();
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/frontend/single.php';
        return ob_get_clean();
    }
    
    /**
     * Récupérer les données structurées pour la comparaison
     */
    private function get_comparison_data($type_id, $item_ids) {
        global $wpdb;
        
        $table_fields = $wpdb->prefix . 'comparator_fields';
        $table_values = $wpdb->prefix . 'comparator_values';
        $table_field_descriptions = $wpdb->prefix . 'comparator_field_descriptions';
        
        // Récupérer les catégories
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_fields WHERE type_id = %d AND field_type = 'category' ORDER BY sort_order",
            $type_id
        ));
        
        $data = array();
        
        foreach ($categories as $category) {
            // Récupérer les champs description de cette catégorie
            $fields = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_fields WHERE parent_category_id = %d AND field_type = 'description' ORDER BY sort_order",
                $category->id
            ));
            
            $category_data = array(
                'category' => $category,
                'fields' => array()
            );
            
            foreach ($fields as $field) {
                $field_data = array(
                    'field' => $field,
                    'values' => array(),
                    'long_descriptions' => array()
                );
                
                // Récupérer les valeurs pour chaque élément
                foreach ($item_ids as $item_id) {
                    $value = $wpdb->get_var($wpdb->prepare(
                        "SELECT value FROM $table_values WHERE item_id = %d AND field_id = %d",
                        $item_id, $field->id
                    ));
                    
                    $long_description = $wpdb->get_var($wpdb->prepare(
                        "SELECT long_description FROM $table_field_descriptions WHERE item_id = %d AND field_id = %d",
                        $item_id, $field->id
                    ));
                    
                    $field_data['values'][$item_id] = $value;
                    $field_data['long_descriptions'][$item_id] = $long_description;
                }
                
                $category_data['fields'][] = $field_data;
            }
            
            // N'ajouter la catégorie que si elle a des champs
            if (!empty($category_data['fields'])) {
                $data[] = $category_data;
            }
        }
        
        return $data;
    }
    
    /**
     * Récupérer les données pour un seul élément
     */
    private function get_single_item_data($type_id, $item_id) {
        global $wpdb;
        
        $table_fields = $wpdb->prefix . 'comparator_fields';
        $table_values = $wpdb->prefix . 'comparator_values';
        $table_field_descriptions = $wpdb->prefix . 'comparator_field_descriptions';
        
        // Récupérer les catégories
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_fields WHERE type_id = %d AND field_type = 'category' ORDER BY sort_order",
            $type_id
        ));
        
        $data = array();
        
        foreach ($categories as $category) {
            // Récupérer les champs description de cette catégorie
            $fields = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_fields WHERE parent_category_id = %d AND field_type = 'description' ORDER BY sort_order",
                $category->id
            ));
            
            $category_data = array(
                'category' => $category,
                'fields' => array()
            );
            
            foreach ($fields as $field) {
                $value = $wpdb->get_var($wpdb->prepare(
                    "SELECT value FROM $table_values WHERE item_id = %d AND field_id = %d",
                    $item_id, $field->id
                ));
                
                $long_description = $wpdb->get_var($wpdb->prepare(
                    "SELECT long_description FROM $table_field_descriptions WHERE item_id = %d AND field_id = %d",
                    $item_id, $field->id
                ));
                
                $field_data = array(
                    'field' => $field,
                    'values' => array($item_id => $value),
                    'long_descriptions' => array($item_id => $long_description)
                );
                
                $category_data['fields'][] = $field_data;
            }
            
            // N'ajouter la catégorie que si elle a des champs
            if (!empty($category_data['fields'])) {
                $data[] = $category_data;
            }
        }
        
        return $data;
    }
}