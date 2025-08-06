<?php

class WP_Comparator_Pages {
    
    public function __construct() {
        add_action('init', array($this, 'add_rewrite_rules'), 10, 0);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_comparison_page'));
        
        // Forcer le flush des règles de réécriture lors de l'activation
        add_action('wp_loaded', array($this, 'maybe_flush_rewrite_rules'));
    }
    
    /**
     * Forcer le flush des règles de réécriture si nécessaire
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('wp_comparator_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            delete_option('wp_comparator_flush_rewrite_rules');
        }
    }
    
    /**
     * Ajouter les règles de réécriture d'URL
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            'comparez-([^/]+)-([^/]+)-et-([^/]+)\.html$',
            'index.php?wp_comparator_compare=1&type_slug=$matches[1]&item1_slug=$matches[2]&item2_slug=$matches[3]',
            'top'
        );
    }
    
    /**
     * Ajouter les variables de requête
     */
    public function add_query_vars($vars) {
        $vars[] = 'wp_comparator_compare';
        $vars[] = 'type_slug';
        $vars[] = 'item1_slug';
        $vars[] = 'item2_slug';
        return $vars;
    }
    
    /**
     * Gérer l'affichage de la page de comparaison
     */
    public function handle_comparison_page() {
        if (get_query_var('wp_comparator_compare')) {
            $this->display_comparison_page();
            exit;
        }
    }
    
    /**
     * Créer une page WordPress pour la comparaison
     */
    public function create_wordpress_page($type_slug, $item1_slug, $item2_slug) {
        global $wpdb;
        
        error_log("create_wordpress_page appelée - type: $type_slug, item1: $item1_slug, item2: $item2_slug");
        
        // Récupérer les données des contrats
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $type_slug
        ));
        
        if (!$type) {
            error_log("Type non trouvé: $type_slug");
            return array('error' => 'Type non trouvé');
        }
        
        $item1 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE slug = %s AND type_id = %d",
            $item1_slug, $type->id
        ));
        
        $item2 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE slug = %s AND type_id = %d",
            $item2_slug, $type->id
        ));
        
        if (!$item1 || !$item2) {
            error_log("Items non trouvés - item1: " . ($item1 ? 'OK' : 'NON') . ", item2: " . ($item2 ? 'OK' : 'NON'));
            return array('error' => 'Contrats non trouvés');
        }
        
        // Générer le titre et le slug de la page
        $page_title = "Prévoyance : Comparaison du contrat {$item1->name} et {$item2->name}";
        $page_slug = "comparez-les-prevoyances-{$item1_slug}-et-{$item2_slug}";
        
        error_log("Slug de page généré: $page_slug");
        
        // Vérifier si la page existe déjà
        $existing_page = get_page_by_path($page_slug);
        if ($existing_page) {
            error_log("Page existante trouvée - ID: {$existing_page->ID}");
            return array(
                'page_id' => $existing_page->ID,
                'existing' => true
            );
        }
        
        // Générer le contenu de la page
        $page_content = $this->generate_page_content($type, $item1, $item2);
        
        error_log("Contenu généré: " . substr($page_content, 0, 100) . "...");
        
        // Créer la page
        $page_data = array(
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_name' => $page_slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'meta_input' => array(
                '_wp_comparator_page' => 1,
                '_wp_comparator_type' => $type_slug,
                '_wp_comparator_item1' => $item1_slug,
                '_wp_comparator_item2' => $item2_slug
            )
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            error_log("Page créée avec succès - ID: $page_id");
            return array(
                'page_id' => $page_id,
                'existing' => false
            );
        } else {
            error_log("Erreur création page: " . (is_wp_error($page_id) ? $page_id->get_error_message() : 'Erreur inconnue'));
            return array('error' => 'Erreur lors de la création');
        }
    }
    
    /**
     * Générer le contenu de la page de comparaison
     */
    private function generate_page_content($type, $item1, $item2) {
        // Générer le shortcode de comparaison
        $shortcode = "[wp_comparator_compare type=\"{$type->slug}\" items=\"{$item1->slug},{$item2->slug}\"]";
        
        // Ajouter le texte d'introduction si défini
        $intro_text = '';
        if (!empty($type->intro_text)) {
            $intro_text = $this->replace_intro_variables($type->intro_text, $item1, $item2);
            $intro_text = "<div class=\"comparison-intro\">" . wpautop($intro_text) . "</div>\n\n";
        }
        
        return $intro_text . $shortcode;
    }
    
    /**
     * Remplacer les variables dans le texte d'introduction
     */
    private function replace_intro_variables($intro_text, $item1, $item2) {
        $replacements = array(
            '{contrat1}' => $item1->contrat ?: $item1->name,
            '{assureur1}' => $item1->assureur ?: 'N/A',
            '{contrat2}' => $item2->contrat ?: $item2->name,
            '{assureur2}' => $item2->assureur ?: 'N/A'
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $intro_text);
    }
    
    /**
     * Afficher la page de comparaison directement
     */
    private function display_comparison_page() {
        $type_slug = get_query_var('type_slug');
        $item1_slug = get_query_var('item1_slug');
        $item2_slug = get_query_var('item2_slug');
        
        global $wpdb;
        
        // Récupérer les données
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $type_slug
        ));
        
        if (!$type) {
            wp_die('Type de comparateur non trouvé');
        }
        
        $item1 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE slug = %s AND type_id = %d",
            $item1_slug, $type->id
        ));
        
        $item2 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE slug = %s AND type_id = %d",
            $item2_slug, $type->id
        ));
        
        if (!$item1 || !$item2) {
            wp_die('Contrats non trouvés');
        }
        
        // Récupérer les données de comparaison
        $comparison_data = $this->get_comparison_data($type->id, array($item1->id, $item2->id));
        
        // Charger le header WordPress
        get_header();
        
        // Afficher le template de comparaison
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/frontend/compare-page.php';
        
        // Charger le footer WordPress
        get_footer();
    }
    
    /**
     * Récupérer les données structurées pour la comparaison
     */
    private function get_comparison_data($type_id, $item_ids) {
        global $wpdb;
        
        $table_categories = $wpdb->prefix . 'comparator_fields';
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
}