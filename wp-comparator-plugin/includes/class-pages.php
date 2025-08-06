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
        // Règles multiples pour gérer tous les cas possibles
        add_rewrite_rule(
            '^comparez-([^/\-]+)-([^/\-]+)-et-([^/\-\.]+)/?$',
            'index.php?wp_comparator_compare=1&type_slug=$matches[1]&item1_slug=$matches[2]&item2_slug=$matches[3]',
            'top'
        );
        
        // Règle spécifique pour les URLs avec .html
        add_rewrite_rule(
            '^comparez-([^/\-]+)-([^/\-]+)-et-([^/\-\.]+)\.html/?$',
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
            $this->debug_log('handle_comparison_page called');
            $this->debug_log('Type slug: ' . get_query_var('type_slug'));
            $this->debug_log('Item1 slug: ' . get_query_var('item1_slug'));
            $this->debug_log('Item2 slug: ' . get_query_var('item2_slug'));
            
            // IMPORTANT: Ajouter les hooks SEO AVANT l'affichage
            $this->setup_seo_hooks();
            
            $this->display_comparison_page();
            exit;
        }
    }
    
    /**
     * Créer une page WordPress pour la comparaison
     */
    public function create_wordpress_page($type_slug, $item1_slug, $item2_slug) {
        global $wpdb;
        
        $this->debug_log("create_wordpress_page appelée - type: $type_slug, item1: $item1_slug, item2: $item2_slug");
        
        // Nettoyer les slugs
        $type_slug = sanitize_title($type_slug);
        $item1_slug = sanitize_title($item1_slug);
        $item2_slug = sanitize_title($item2_slug);
        
        // Récupérer les données des contrats
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $type_slug
        ));
        
        if (!$type) {
            $this->debug_log("Type non trouvé: $type_slug");
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
            $this->debug_log("Items non trouvés - item1: " . ($item1 ? 'OK' : 'NON') . ", item2: " . ($item2 ? 'OK' : 'NON'));
            return array('error' => 'Contrats non trouvés');
        }
        
        // Générer le titre et le slug de la page
        $page_title = $this->generate_page_title($type, $item1, $item2);
        $page_slug = "comparez-{$type_slug}-{$item1_slug}-et-{$item2_slug}";
        
        $this->debug_log("Slug de page généré: $page_slug");
        
        // Vérifier si la page existe déjà
        $existing_page = get_page_by_path($page_slug);
        if ($existing_page) {
            $this->debug_log("Page existante trouvée - ID: {$existing_page->ID}");
            return array(
                'page_id' => $existing_page->ID,
                'existing' => true
            );
        }
        
        // Générer le contenu de la page
        $page_content = $this->generate_page_content($type, $item1, $item2);
        
        // Générer les meta SEO personnalisés
        $meta_title = '';
        $meta_description = '';
        
        if (!empty($type->meta_title)) {
            $meta_title = $this->replace_title_variables($type->meta_title, $item1, $item2);
        }
        
        if (!empty($type->meta_description)) {
            $meta_description = $this->replace_title_variables($type->meta_description, $item1, $item2);
        }
        
        // Préparer les meta_input avec les meta SEO
        $meta_input = array(
            '_wp_comparator_page' => 1,
            '_wp_comparator_type' => $type_slug,
            '_wp_comparator_item1' => $item1_slug,
            '_wp_comparator_item2' => $item2_slug
        );
        
        // Ajouter les meta SEO pour chaque plugin si définis
        if (!empty($meta_title)) {
            // Yoast SEO
            $meta_input['_yoast_wpseo_title'] = $meta_title;
            // All in One SEO
            $meta_input['_aioseo_title'] = $meta_title;
            // RankMath
            $meta_input['rank_math_title'] = $meta_title;
        }
        
        if (!empty($meta_description)) {
            // Yoast SEO
            $meta_input['_yoast_wpseo_metadesc'] = $meta_description;
            // All in One SEO
            $meta_input['_aioseo_description'] = $meta_description;
            // RankMath
            $meta_input['rank_math_description'] = $meta_description;
        }
        
        // Générer un excerpt propre pour les meta descriptions automatiques
        $this->debug_log("Contenu généré: " . substr($page_content, 0, 100) . "...");
        
        // Créer la page
        $page_data = array(
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_name' => $page_slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'meta_input' => $meta_input
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            $this->debug_log("Page créée avec succès - ID: $page_id");
            
            // Debug des meta SEO stockés
            if (!empty($meta_title)) {
                $this->debug_log("Meta title stocké: $meta_title");
            }
            if (!empty($meta_description)) {
                $this->debug_log("Meta description stockée: $meta_description");
            }
            
            return array(
                'page_id' => $page_id,
                'existing' => false
            );
        } else {
            $this->debug_log("Erreur création page: " . (is_wp_error($page_id) ? $page_id->get_error_message() : 'Erreur inconnue'));
            return array('error' => 'Erreur lors de la création');
        }
    }
    
    /**
     * Générer le contenu de la page de comparaison
     */
    private function generate_page_content($type, $item1, $item2) {
        // Générer le shortcode de comparaison
        $shortcode = "[wp_comparator_compare type=\"{$type->slug}\" items=\"{$item1->slug},{$item2->slug}\"]";
        
        return $shortcode;
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
     * Générer le titre de la page de comparaison
     */
    private function generate_page_title($type, $item1, $item2) {
        // Si un titre personnalisé est défini, l'utiliser
        if (!empty($type->custom_title)) {
            return $this->replace_title_variables($type->custom_title, $item1, $item2);
        }
        
        // Sinon, utiliser le titre par défaut
        return "Prévoyance : Comparaison du contrat {$item1->name} et {$item2->name}";
    }
    
    /**
     * Remplacer les variables dans les titres et meta
     */
    private function replace_title_variables($text, $item1, $item2) {
        $replacements = array(
            '{contrat1}' => $item1->contrat ?: $item1->name,
            '{assureur1}' => $item1->assureur ?: 'N/A',
            '{name1}' => $item1->name,
            '{version1}' => $item1->version ?: '',
            '{territorialite1}' => $item1->territorialite ?: '',
            '{contrat2}' => $item2->contrat ?: $item2->name,
            '{assureur2}' => $item2->assureur ?: 'N/A',
            '{name2}' => $item2->name,
            '{version2}' => $item2->version ?: '',
            '{territorialite2}' => $item2->territorialite ?: ''
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    /**
     * Afficher la page de comparaison directement
     */
    private function display_comparison_page() {
        $type_slug = get_query_var('type_slug');
        $item1_slug = get_query_var('item1_slug');
        $item2_slug = get_query_var('item2_slug');
        
        // Nettoyer les slugs de tout caractère indésirable
        $type_slug = sanitize_title($type_slug);
        $item1_slug = sanitize_title($item1_slug);
        $item2_slug = sanitize_title($item2_slug);
        
        // Debug
        $this->debug_log("Slugs nettoyés: type=$type_slug, item1=$item1_slug, item2=$item2_slug");
        
        // Vérifier que tous les paramètres sont présents
        if (empty($type_slug) || empty($item1_slug) || empty($item2_slug)) {
            $this->debug_log('Paramètres manquants');
            wp_die('Paramètres de comparaison manquants');
        }
        
        global $wpdb;
        
        // Récupérer les données
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_items = $wpdb->prefix . 'comparator_items';
        
        // Debug de la requête
        $this->debug_log("Recherche du type avec slug: $type_slug");
        
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $type_slug
        ));
        
        if (!$type) {
            $this->debug_log("Type non trouvé pour slug: $type_slug");
            // Lister tous les types disponibles
            $all_types = $wpdb->get_results("SELECT id, name, slug FROM $table_types");
            $this->debug_log("Types disponibles: " . print_r($all_types, true));
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
    
    /**
     * Configurer les hooks SEO AVANT l'affichage
     */
    private function setup_seo_hooks() {
        $type_slug = get_query_var('type_slug');
        $item1_slug = get_query_var('item1_slug');
        $item2_slug = get_query_var('item2_slug');
        
        // Nettoyer les slugs
        $type_slug = sanitize_title($type_slug);
        $item1_slug = sanitize_title($item1_slug);
        $item2_slug = sanitize_title($item2_slug);
        
        global $wpdb;
        
        // Récupérer les données
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $type = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_types WHERE slug = %s",
            $type_slug
        ));
        
        if (!$type) return;
        
        $item1 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE slug = %s AND type_id = %d",
            $item1_slug, $type->id
        ));
        
        $item2 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE slug = %s AND type_id = %d",
            $item2_slug, $type->id
        ));
        
        if (!$item1 || !$item2) return;
        
        // Appliquer les meta tags SEO MAINTENANT
        wp_comparator_set_seo_meta($type, $item1, $item2);
    }
    
    /**
     * Fonction de debug centralisée
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('WP Comparator - ' . $message);
        }
    }
}