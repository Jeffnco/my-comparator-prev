<div class="wp-comparator-single-container">
    <div class="comparator-header">
        <h1 class="item-title"><?php echo esc_html($item->name); ?></h1>
    </div>
    
    <!-- Informations générales du contrat -->
    <div class="category-section">
        <h2 class="category-title">Informations générales</h2>
        
        <div class="fields-list">
            <?php if ($item->logo_url): ?>
                <div class="field-row">
                    <div class="field-label">
                        <strong>Logo</strong>
                    </div>
                    <div class="field-value">
                        <div class="field-short-value">
                            <img src="<?php echo esc_url($item->logo_url); ?>" alt="<?php echo esc_attr($item->name); ?>" style="max-width: 150px; height: auto;">
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($item->description): ?>
                <div class="field-row">
                    <div class="field-label">
                        <strong>Description</strong>
                    </div>
                    <div class="field-value">
                        <div class="field-short-value">
                            <?php echo wp_kses_post(stripslashes($item->description)); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($item->contrat): ?>
                <div class="field-row">
                    <div class="field-label">
                        <strong>Contrat</strong>
                    </div>
                    <div class="field-value">
                        <div class="field-short-value">
                            <?php echo esc_html($item->contrat); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($item->version): ?>
                <div class="field-row">
                    <div class="field-label">
                        <strong>Version</strong>
                    </div>
                    <div class="field-value">
                        <div class="field-short-value">
                            <?php echo esc_html($item->version); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($item->assureur): ?>
                <div class="field-row">
                    <div class="field-label">
                        <strong>Assureur</strong>
                    </div>
                    <div class="field-value">
                        <div class="field-short-value">
                            <?php echo esc_html($item->assureur); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($item->territorialite): ?>
                <div class="field-row">
                    <div class="field-label">
                        <strong>Territorialité</strong>
                    </div>
                    <div class="field-value">
                        <div class="field-short-value">
                            <?php echo esc_html($item->territorialite); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($item->document_url): ?>
                <div class="field-row">
                    <div class="field-label">
                        <strong>Documentation</strong>
                    </div>
                    <div class="field-value">
                        <div class="field-short-value">
                            <a href="<?php echo esc_url($item->document_url); ?>" target="_blank" class="button-document">
                                <span class="dashicons dashicons-media-document"></span>
                                Télécharger le document
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($item_data)): ?>
        <div class="comparator-single-content">
            <?php foreach ($item_data as $category_data): ?>
                <?php $category = $category_data['category']; ?>
                <?php $fields = $category_data['fields']; ?>
                
                <?php if (!empty($fields)): ?>
                    <div class="category-section">
                        <h2 class="category-title">
                            <?php echo esc_html($category->name); ?>
                            <?php if ($category->has_info_button == 1 && !empty($category->info_content)): ?>
                                <button class="info-btn" type="button" title="Information">
                                    <span class="dashicons dashicons-info"></span>
                                </button>
                                <div class="info-tooltip" style="display: none; position: absolute; top: 100%; left: 0; margin-top: 5px; background: #333; color: white; padding: 10px; border-radius: 6px; font-size: 14px; max-width: 300px; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.3); line-height: 1.4;">
                                    <?php echo nl2br(esc_html($category->info_content)); ?>
                            <?php endif; ?>
                        </h2>
                        
                        <div class="fields-list">
                            <?php foreach ($fields as $field_data): ?>
                                <?php 
                                $field = $field_data['field']; 
                                $value = isset($field_data['values'][$item->id]) ? $field_data['values'][$item->id] : '';
                                
                                // Récupérer la description longue pour ce champ et cet item
                                global $wpdb;
                                $table_field_descriptions = $wpdb->prefix . 'comparator_field_descriptions';
                                $long_description = $wpdb->get_var($wpdb->prepare(
                                    "SELECT long_description FROM $table_field_descriptions WHERE item_id = %d AND field_id = %d",
                                    $item->id, $field->id
                                ));
                                
                                // DEBUG: Vérifier ce qui est récupéré
                                if (current_user_can('manage_options')) {
                                    echo "<!-- DEBUG: field_id={$field->id}, item_id={$item->id}, long_desc=" . ($long_description ? 'OUI' : 'NON') . " -->";
                                }
                                ?>
                                
                                <div class="field-row">
                                    <div class="field-label">
                                        <strong><?php echo esc_html($field->name); ?></strong>
                                        <?php if ($field->short_description): ?>
                                            <button class="info-btn-small" type="button" title="<?php echo esc_attr($field->short_description); ?>">
                                                <span class="dashicons dashicons-info"></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="field-value">
                                        <div class="field-short-value">
                                            <?php 
                                            if (!empty($value)) {
                                                echo wp_kses_post(nl2br(stripslashes($value)));
                                            } else {
                                                echo '<span style="color: #999;">-</span>';
                                            }
                                            ?>
                                        </div>
                                        
                                        <?php if (!empty($long_description)): ?>
                                        <div class="field-more-info" style="margin-top: 10px;">
                                            <button class="btn-more-info" type="button">En savoir plus</button>
                                            <div class="more-info-content" style="display: none;">
                                                <?php echo wp_kses_post(nl2br(stripslashes($long_description))); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-data-message">
            <p>Aucune donnée disponible pour cet élément.</p>
        </div>
    <?php endif; ?>
    
    <div class="comparator-actions">
        <a href="javascript:history.back()" class="button-back">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            Retour
        </a>
        
        <?php if (isset($_GET['compare_with'])): ?>
            <a href="?compare=<?php echo esc_attr($item->slug . ',' . $_GET['compare_with']); ?>&type=<?php echo esc_attr($type->slug); ?>" class="button-compare">
                <span class="dashicons dashicons-analytics"></span>
                Comparer
            </a>
        <?php endif; ?>
    </div>
</div>
