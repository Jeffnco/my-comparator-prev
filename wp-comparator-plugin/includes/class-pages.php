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
            // RankMath
            $meta_input['rank_math_title'] = $meta_title;
        }
        
        if (!empty($meta_description)) {
            // Yoast SEO
            $meta_input['_yoast_wpseo_metadesc'] = $meta_description;
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
            
            // Gérer AIOSEO séparément car il utilise un format spécial
            if (!empty($meta_title) || !empty($meta_description)) {
                $this->handle_aioseo_meta($page_id, $meta_title, $meta_description);
            }
            
            // VÉRIFICATION FINALE : Que contient réellement la page ?
            $this->debug_final_check($page_id);
            
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
     * Vérification finale de ce qui est stocké
     */
    private function debug_final_check($page_id) {
        // Afficher le debug directement sur la page temporairement
        echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border: 2px solid #333; font-family: monospace;'>";
        echo "<h3>🔍 DEBUG AIOSEO - Page ID: $page_id</h3>";
        
        // Vérifier tous les meta de la page
        $all_meta = get_post_meta($page_id);
        
        echo "<h4>📋 TOUS LES META DE LA PAGE :</h4>";
        echo "<pre>" . print_r($all_meta, true) . "</pre>";
        
        // Chercher les meta SEO
        $seo_meta = array();
        foreach ($all_meta as $key => $value) {
            if (strpos($key, 'yoast') !== false || 
                strpos($key, 'rank_math') !== false || 
                strpos($key, 'aioseo') !== false ||
                strpos($key, 'aioseop') !== false) {
                $seo_meta[$key] = $value[0];
            }
        }
        
        echo "<h4>🎯 META SEO TROUVÉS :</h4>";
        if (!empty($seo_meta)) {
            echo "<pre>" . print_r($seo_meta, true) . "</pre>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ AUCUN META SEO TROUVÉ !</p>";
        }
        
        // Test spécifique AIOSEO
        $aioseo_settings = get_post_meta($page_id, '_aioseo_posts_settings', true);
        echo "<h4>🔧 AIOSEO V4+ SETTINGS :</h4>";
        if ($aioseo_settings) {
            echo "<pre>" . print_r($aioseo_settings, true) . "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠️ Pas de settings AIOSEO v4+</p>";
        }
        
        $aioseop_title = get_post_meta($page_id, '_aioseop_title', true);
        $aioseop_desc = get_post_meta($page_id, '_aioseop_description', true);
        echo "<h4>🔧 AIOSEO V3 SETTINGS :</h4>";
        echo "<p><strong>Title:</strong> " . ($aioseop_title ? $aioseop_title : "❌ Vide") . "</p>";
        echo "<p><strong>Description:</strong> " . ($aioseop_desc ? $aioseop_desc : "❌ Vide") . "</p>";
        
        // Vérifier la version d'AIOSEO
        echo "<h4>🔍 DÉTECTION AIOSEO :</h4>";
        echo "<p><strong>AIOSEO actif:</strong> " . ($this->is_aioseo_active() ? "✅ OUI" : "❌ NON") . "</p>";
        echo "<p><strong>Version v4+:</strong> " . ($this->is_aioseo_v4_or_higher() ? "✅ OUI" : "❌ NON") . "</p>";
        
        if (defined('AIOSEO_VERSION')) {
            echo "<p><strong>Version AIOSEO:</strong> " . AIOSEO_VERSION . "</p>";
        } else {
            echo "<p><strong>Version AIOSEO:</strong> ❌ Constante non définie</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Gérer les meta SEO pour AIOSEO (format spécial)
     */
    private function handle_aioseo_meta($page_id, $meta_title, $meta_description) {
        // Debug visible sur la page
        echo "<div style='background: #e8f4f8; padding: 15px; margin: 10px; border-left: 4px solid #0073aa;'>";
        echo "<h4>🔧 TRAITEMENT AIOSEO</h4>";
        echo "<p><strong>Page ID:</strong> $page_id</p>";
        echo "<p><strong>Meta title reçu:</strong> '$meta_title'</p>";
        echo "<p><strong>Meta description reçue:</strong> '$meta_description'</p>";
        
        // Vérifier si AIOSEO est actif
        if (!$this->is_aioseo_active()) {
            echo "<p style='color: red;'>❌ AIOSEO non actif - abandon</p>";
            echo "</div>";
            return;
        }
        
        echo "<p style='color: green;'>✅ AIOSEO détecté comme actif</p>";
        
        // Détecter la version d'AIOSEO
        if ($this->is_aioseo_v4_or_higher()) {
            echo "<p>🔧 AIOSEO v4+ détecté - utilisation format tableau</p>";
            // AIOSEO v4+ utilise '_aioseo_posts_settings'
            $this->set_aioseo_v4_meta($page_id, $meta_title, $meta_description);
        } else {
            echo "<p>🔧 AIOSEO v3 détecté - utilisation champs séparés</p>";
            // AIOSEO v3 utilise '_aioseop_*'
            $this->set_aioseo_v3_meta($page_id, $meta_title, $meta_description);
        }
        
        echo "</div>";
    }
    
    /**
     * Vérifier si AIOSEO est actif
     */
    private function is_aioseo_active() {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        return (
            is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || // v3
            is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack_pro.php') || // v3 Pro
            is_plugin_active('all-in-one-seo/all-in-one-seo.php') || // v4
            is_plugin_active('all-in-one-seo-pro/all-in-one-seo-pro.php') // v4 Pro
        );
    }
    
    /**
     * Détecter si AIOSEO v4 ou supérieur
     */
    private function is_aioseo_v4_or_higher() {
        // AIOSEO v4+ définit la constante AIOSEO_VERSION
        if (defined('AIOSEO_VERSION')) {
            return version_compare(AIOSEO_VERSION, '4.0', '>=');
        }
        
        // Fallback : vérifier le nom du plugin actif
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        return (
            is_plugin_active('all-in-one-seo/all-in-one-seo.php') ||
            is_plugin_active('all-in-one-seo-pro/all-in-one-seo-pro.php')
        );
    }
    
    /**
     * Définir les meta pour AIOSEO v4+
     */
    private function set_aioseo_v4_meta($page_id, $meta_title, $meta_description) {
        echo "<div style='background: #f0f8e8; padding: 10px; margin: 5px; border-left: 3px solid #28a745;'>";
        echo "<h5>💾 STOCKAGE AIOSEO V4+</h5>";
        
        // Récupérer les settings existants
        $settings = get_post_meta($page_id, '_aioseo_posts_settings', true);
        
        // Si pas de settings existants, créer un tableau vide
        if (!is_array($settings)) {
            $settings = array();
        }
        
        echo "<p><strong>Settings existants:</strong></p>";
        echo "<pre>" . print_r($settings, true) . "</pre>";
        
        // Ajouter nos meta
        if (!empty($meta_title)) {
            $settings['title'] = $meta_title;
        }
        
        if (!empty($meta_description)) {
            $settings['description'] = $meta_description;
        }
        
        echo "<p><strong>Settings après modification:</strong></p>";
        echo "<pre>" . print_r($settings, true) . "</pre>";
        
        // Sauvegarder
        $result = update_post_meta($page_id, '_aioseo_posts_settings', $settings);
        
        echo "<p><strong>Résultat update_post_meta:</strong> " . ($result ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        
        // Vérification immédiate
        $verification = get_post_meta($page_id, '_aioseo_posts_settings', true);
        echo "<p><strong>Vérification immédiate:</strong></p>";
        echo "<pre>" . print_r($verification, true) . "</pre>";
        
        echo "</div>";
    }
    
    /**
     * Définir les meta pour AIOSEO v3
     */
    private function set_aioseo_v3_meta($page_id, $meta_title, $meta_description) {
        echo "<div style='background: #f8f0e8; padding: 10px; margin: 5px; border-left: 3px solid #ff8c00;'>";
        echo "<h5>💾 STOCKAGE AIOSEO V3</h5>";
        
        if (!empty($meta_title)) {
            $result1 = update_post_meta($page_id, '_aioseop_title', $meta_title);
            echo "<p>Title stocké: '$meta_title' - Résultat: " . ($result1 ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        }
        
        if (!empty($meta_description)) {
            $result2 = update_post_meta($page_id, '_aioseop_description', $meta_description);
            echo "<p>Description stockée: '$meta_description' - Résultat: " . ($result2 ? '✅ SUCCESS' : '✅ FAILED') . "</p>";
        }
        
        echo "</div>";
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