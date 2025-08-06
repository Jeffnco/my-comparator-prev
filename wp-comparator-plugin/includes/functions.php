<?php

/**
 * Fonctions utilitaires pour WP Comparator
 */

/**
 * Récupère un type de comparateur par son slug
 */
function wp_comparator_get_type($slug) {
    global $wpdb;
    
    $table_types = $wpdb->prefix . 'comparator_types';
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_types WHERE slug = %s",
        $slug
    ));
}

/**
 * Récupère les éléments d'un type de comparateur
 */
function wp_comparator_get_items($type_id, $active_only = true) {
    global $wpdb;
    
    $table_items = $wpdb->prefix . 'comparator_items';
    
    $where = "type_id = %d";
    $values = array($type_id);
    
    if ($active_only) {
        $where .= " AND is_active = 1";
    }
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_items WHERE $where ORDER BY sort_order, name",
        $values
    ));
}

/**
 * Récupère les catégories d'un type de comparateur
 */
function wp_comparator_get_categories($type_id) {
    global $wpdb;
    
    $table_categories = $wpdb->prefix . 'comparator_categories';
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_categories WHERE type_id = %d ORDER BY sort_order",
        $type_id
    ));
}

/**
 * Récupère les champs d'une catégorie
 */
function wp_comparator_get_fields($category_id) {
    global $wpdb;
    
    $table_fields = $wpdb->prefix . 'comparator_fields';
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_fields WHERE category_id = %d ORDER BY sort_order",
        $category_id
    ));
}

/**
 * Récupère la valeur d'un champ pour un élément
 */
function wp_comparator_get_field_value($item_id, $field_id) {
    global $wpdb;
    
    $table_values = $wpdb->prefix . 'comparator_values';
    return $wpdb->get_var($wpdb->prepare(
        "SELECT value FROM $table_values WHERE item_id = %d AND field_id = %d",
        $item_id, $field_id
    ));
}

/**
 * Met à jour ou insère la valeur d'un champ pour un élément
 */
function wp_comparator_set_field_value($item_id, $field_id, $value) {
    global $wpdb;
    
    $table_values = $wpdb->prefix . 'comparator_values';
    
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_values WHERE item_id = %d AND field_id = %d",
        $item_id, $field_id
    ));
    
    if ($existing) {
        return $wpdb->update(
            $table_values,
            array('value' => $value),
            array('item_id' => $item_id, 'field_id' => $field_id),
            array('%s'),
            array('%d', '%d')
        );
    } else {
        return $wpdb->insert(
            $table_values,
            array(
                'item_id' => $item_id,
                'field_id' => $field_id,
                'value' => $value
            ),
            array('%d', '%d', '%s')
        );
    }
}

/**
 * Génère un slug unique
 */
function wp_comparator_generate_unique_slug($title, $table, $id = 0) {
    global $wpdb;
    
    $slug = sanitize_title($title);
    $original_slug = $slug;
    $counter = 1;
    
    while (true) {
        $query = "SELECT COUNT(*) FROM $table WHERE slug = %s";
        $values = array($slug);
        
        if ($id > 0) {
            $query .= " AND id != %d";
            $values[] = $id;
        }
        
        $count = $wpdb->get_var($wpdb->prepare($query, $values));
        
        if ($count == 0) {
            break;
        }
        
        $slug = $original_slug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

/**
 * Formate une valeur de champ pour l'affichage
 */
function wp_comparator_format_field_value($value, $field_type) {
    if (empty($value)) {
        return '-';
    }
    
    switch ($field_type) {
        case 'url':
            return '<a href="' . esc_url($value) . '" target="_blank">' . esc_html($value) . '</a>';
            
        case 'image':
            return '<img src="' . esc_url($value) . '" alt="" style="max-width: 100px; height: auto;">';
            
        case 'textarea':
            return wpautop(esc_html($value));
            
        case 'checkbox':
            return $value === '1' || $value === 'on' ? 'Oui' : 'Non';
            
        default:
            return esc_html($value);
    }
}

/**
 * Récupère les options d'un champ sous forme de tableau
 */
function wp_comparator_get_field_options($field_options) {
    if (empty($field_options)) {
        return array();
    }
    
    return array_map('trim', explode(',', $field_options));
}

/**
 * Vérifie si un utilisateur peut gérer le comparateur
 */
function wp_comparator_user_can_manage() {
    return current_user_can('manage_comparator');
}

/**
 * Récupère les statistiques du plugin
 */
function wp_comparator_get_stats() {
    global $wpdb;
    
    $table_types = $wpdb->prefix . 'comparator_types';
    $table_categories = $wpdb->prefix . 'comparator_categories';
    $table_fields = $wpdb->prefix . 'comparator_fields';
    $table_items = $wpdb->prefix . 'comparator_items';
    
    return array(
        'types' => $wpdb->get_var("SELECT COUNT(*) FROM $table_types"),
        'categories' => $wpdb->get_var("SELECT COUNT(*) FROM $table_categories"),
        'fields' => $wpdb->get_var("SELECT COUNT(*) FROM $table_fields"),
        'items' => $wpdb->get_var("SELECT COUNT(*) FROM $table_items"),
        'active_items' => $wpdb->get_var("SELECT COUNT(*) FROM $table_items WHERE is_active = 1")
    );
}

/**
 * Nettoie les données lors de la désinstallation
 */
function wp_comparator_cleanup_data() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'comparator_values',
        $wpdb->prefix . 'comparator_fields',
        $wpdb->prefix . 'comparator_categories',
        $wpdb->prefix . 'comparator_items',
        $wpdb->prefix . 'comparator_types'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Supprimer les options
    delete_option('wp_comparator_version');
    delete_option('wp_comparator_settings');
    
    wp_cache_flush();
}

/**
 * Définir les meta tags SEO pour les pages de comparaison
 * Compatible avec Yoast, AIOSEO, RankMath et WordPress natif
 */
function wp_comparator_set_seo_meta($type, $item1, $item2) {
    // Générer les meta tags personnalisés
    $meta_title = '';
    $meta_description = '';
    
    if (!empty($type->meta_title)) {
        $meta_title = str_replace(
            array('{contrat1}', '{assureur1}', '{name1}', '{version1}', '{territorialite1}', 
                  '{contrat2}', '{assureur2}', '{name2}', '{version2}', '{territorialite2}'),
            array(
                stripslashes($item1->contrat ?: $item1->name),
                stripslashes($item1->assureur ?: 'N/A'),
                stripslashes($item1->name),
                stripslashes($item1->version ?: ''),
                stripslashes($item1->territorialite ?: ''),
                stripslashes($item2->contrat ?: $item2->name),
                stripslashes($item2->assureur ?: 'N/A'),
                stripslashes($item2->name),
                stripslashes($item2->version ?: ''),
                stripslashes($item2->territorialite ?: '')
            ),
            stripslashes($type->meta_title)
        );
    }
    
    if (!empty($type->meta_description)) {
        $meta_description = str_replace(
            array('{contrat1}', '{assureur1}', '{name1}', '{version1}', '{territorialite1}', 
                  '{contrat2}', '{assureur2}', '{name2}', '{version2}', '{territorialite2}'),
            array(
                stripslashes($item1->contrat ?: $item1->name),
                stripslashes($item1->assureur ?: 'N/A'),
                stripslashes($item1->name),
                stripslashes($item1->version ?: ''),
                stripslashes($item1->territorialite ?: ''),
                stripslashes($item2->contrat ?: $item2->name),
                stripslashes($item2->assureur ?: 'N/A'),
                stripslashes($item2->name),
                stripslashes($item2->version ?: ''),
                stripslashes($item2->territorialite ?: '')
            ),
            stripslashes($type->meta_description)
        );
    }
    
    // Appliquer les meta tags selon le plugin SEO actif
    if ($meta_title) {
        // Yoast SEO
        add_filter('wpseo_title', function() use ($meta_title) {
            return $meta_title;
        }, 999);
        
        // All in One SEO
        add_filter('aioseo_title', function() use ($meta_title) {
            return $meta_title;
        }, 999);
        
        // RankMath
        add_filter('rank_math/frontend/title', function() use ($meta_title) {
            return $meta_title;
        }, 999);
        
        // WordPress natif (fallback)
        add_filter('document_title_parts', function() use ($meta_title) {
            return array('title' => $meta_title);
        }, 999);
        
        add_filter('wp_title', function() use ($meta_title) {
            return $meta_title;
        }, 999);
    }
    
    if ($meta_description) {
        // Yoast SEO
        add_filter('wpseo_metadesc', function() use ($meta_description) {
            return $meta_description;
        }, 999);
        
        // All in One SEO
        add_filter('aioseo_description', function() use ($meta_description) {
            return $meta_description;
        }, 999);
        
        // RankMath
        add_filter('rank_math/frontend/description', function() use ($meta_description) {
            return $meta_description;
        }, 999);
        
        // WordPress natif (fallback)
        add_action('wp_head', function() use ($meta_description) {
            // Vérifier qu'aucun plugin SEO n'a déjà ajouté une meta description
            if (!wp_comparator_has_seo_plugin()) {
                echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
            }
        }, 1);
    }
}

/**
 * Vérifier si un plugin SEO est actif
 */
function wp_comparator_has_seo_plugin() {
    return (
        defined('WPSEO_VERSION') || // Yoast
        defined('AIOSEO_VERSION') || // All in One SEO
        defined('RANK_MATH_VERSION') // RankMath
    );
}