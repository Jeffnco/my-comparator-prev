<?php
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

// Ajouter les meta tags au head
if ($meta_title) {
    add_filter('wp_title', function() use ($meta_title) {
        return $meta_title;
    });
    add_filter('document_title_parts', function() use ($meta_title) {
        return array('title' => $meta_title);
    });
}

if ($meta_description) {
    add_action('wp_head', function() use ($meta_description) {
        echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
    });
}
?>

<div class="wp-comparator-compare-page">
    <div class="container">
        <?php if (!empty($type->intro_text)): ?>
            <div class="comparison-intro">
                <?php 
                $intro_text = str_replace(
                    array('{contrat1}', '{assureur1}', '{contrat2}', '{assureur2}'),
                    array(
                        stripslashes($item1->contrat ?: $item1->name),
                        stripslashes($item1->assureur ?: 'N/A'),
                        stripslashes($item2->contrat ?: $item2->name),
                        stripslashes($item2->assureur ?: 'N/A')
                    ),
                    stripslashes($type->intro_text)
                );
                echo wp_kses_post($intro_text);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Informations générales des contrats -->
        <div class="contracts-overview">
            <div class="contract-card">
                <div class="contract-header">
                    <?php if ($item1->logo_url): ?>
                        <img src="<?php echo esc_url($item1->logo_url); ?>" alt="<?php echo esc_attr($item1->name); ?>" class="contract-logo">
                    <?php endif; ?>
                    <h2><?php echo esc_html($item1->name); ?></h2>
                </div>
                <div class="contract-info">
                    <?php if ($item1->contrat): ?>
                        <p><strong>Contrat :</strong> <?php echo esc_html(stripslashes($item1->contrat)); ?></p>
                    <?php endif; ?>
                    <?php if ($item1->description): ?>
                        <div class="contract-description">
                            <strong>Description :</strong>
                            <p><?php echo wp_kses_post(nl2br(stripslashes($item1->description))); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($item1->assureur): ?>
                        <p><strong>Assureur :</strong> <?php echo esc_html(stripslashes($item1->assureur)); ?></p>
                    <?php endif; ?>
                    <?php if ($item1->version): ?>
                        <p><strong>Version :</strong> <?php echo esc_html(stripslashes($item1->version)); ?></p>
                    <?php endif; ?>
                    <?php if ($item1->territorialite): ?>
                        <p><strong>Territorialité :</strong> <?php echo esc_html(stripslashes($item1->territorialite)); ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($item1->document_url): ?>
                    <div class="contract-actions">
                        <a href="<?php echo esc_url($item1->document_url); ?>" target="_blank" class="btn-document">
                            <span class="dashicons dashicons-media-document"></span>
                            Télécharger le document
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="vs-separator">
                <span>VS</span>
            </div>
            
            <div class="contract-card">
                <div class="contract-header">
                    <?php if ($item2->logo_url): ?>
                        <img src="<?php echo esc_url($item2->logo_url); ?>" alt="<?php echo esc_attr($item2->name); ?>" class="contract-logo">
                    <?php endif; ?>
                    <h2><?php echo esc_html($item2->name); ?></h2>
                </div>
                <div class="contract-info">
                    <?php if ($item2->contrat): ?>
                        <p><strong>Contrat :</strong> <?php echo esc_html(stripslashes($item2->contrat)); ?></p>
                    <?php endif; ?>
                    <?php if ($item2->description): ?>
                        <div class="contract-description">
                            <strong>Description :</strong>
                            <p><?php echo wp_kses_post(nl2br(stripslashes($item2->description))); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($item2->assureur): ?>
                        <p><strong>Assureur :</strong> <?php echo esc_html(stripslashes($item2->assureur)); ?></p>
                    <?php endif; ?>
                    <?php if ($item2->version): ?>
                        <p><strong>Version :</strong> <?php echo esc_html(stripslashes($item2->version)); ?></p>
                    <?php endif; ?>
                    <?php if ($item2->territorialite): ?>
                        <p><strong>Territorialité :</strong> <?php echo esc_html(stripslashes($item2->territorialite)); ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($item2->document_url): ?>
                    <div class="contract-actions">
                        <a href="<?php echo esc_url($item2->document_url); ?>" target="_blank" class="btn-document">
                            <span class="dashicons dashicons-media-document"></span>
                            Télécharger le document
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tableau de comparaison détaillée -->
        <div class="comparison-table-container">
            <?php if (!empty($comparison_data)): ?>
                <?php foreach ($comparison_data as $category_data): ?>
                    <?php $category = $category_data['category']; ?>
                    <?php $fields = $category_data['fields']; ?>
                    
                    <?php if (!empty($fields)): ?>
                        <div class="comparison-category">
                            <h3 class="category-title">
                                <?php echo esc_html($category->name); ?>
                                <?php if ($category->has_info_button == 1 && !empty($category->info_content)): ?>
                                    <button class="info-btn" type="button" title="Information">
                                        <span class="dashicons dashicons-info"></span>
                                    </button>
                                    <div class="info-tooltip">
                                        <?php echo nl2br(esc_html($category->info_content)); ?>
                                    </div>
                                <?php endif; ?>
                            </h3>
                            
                            <div class="comparison-table">
                                <div class="table-header">
                                    <div class="field-column">Critère</div>
                                    <div class="item-column"><?php echo esc_html($item1->name); ?></div>
                                    <div class="item-column"><?php echo esc_html($item2->name); ?></div>
                                </div>
                                
                                <?php foreach ($fields as $field_data): ?>
                                    <?php 
                                    $field = $field_data['field']; 
                                    $value1 = isset($field_data['values'][$item1->id]) ? $field_data['values'][$item1->id] : '';
                                    $value2 = isset($field_data['values'][$item2->id]) ? $field_data['values'][$item2->id] : '';
                                    $long_desc1 = isset($field_data['long_descriptions'][$item1->id]) ? $field_data['long_descriptions'][$item1->id] : '';
                                    $long_desc2 = isset($field_data['long_descriptions'][$item2->id]) ? $field_data['long_descriptions'][$item2->id] : '';
                                    ?>
                                    
                                    <div class="table-row">
                                        <div class="field-column">
                                            <strong><?php echo esc_html($field->name); ?></strong>
                                            <?php if ($field->short_description): ?>
                                                <button class="info-btn-small" type="button" title="<?php echo esc_attr($field->short_description); ?>">
                                                    <span class="dashicons dashicons-info"></span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="item-column">
                                            <div class="field-value">
                                                <?php 
                                                if (!empty($value1)) {
                                                    echo wp_kses_post(nl2br(stripslashes($value1)));
                                                } else {
                                                    echo '<span class="no-data">-</span>';
                                                }
                                                ?>
                                            </div>
                                            <?php if (!empty($long_desc1)): ?>
                                                <div class="field-more-info">
                                                    <button class="btn-more-info" type="button">En savoir plus</button>
                                                    <div class="more-info-content" style="display: none;">
                                                        <?php echo wp_kses_post(nl2br(stripslashes($long_desc1))); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="item-column">
                                            <div class="field-value">
                                                <?php 
                                                if (!empty($value2)) {
                                                    echo wp_kses_post(nl2br(stripslashes($value2)));
                                                } else {
                                                    echo '<span class="no-data">-</span>';
                                                }
                                                ?>
                                            </div>
                                            <?php if (!empty($long_desc2)): ?>
                                                <div class="field-more-info">
                                                    <button class="btn-more-info" type="button">En savoir plus</button>
                                                    <div class="more-info-content" style="display: none;">
                                                        <?php echo wp_kses_post(nl2br(stripslashes($long_desc2))); ?>
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
            <?php else: ?>
                <div class="no-comparison-data">
                    <p>Aucune donnée de comparaison disponible pour ces contrats.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions en bas de page -->
        <div class="comparison-actions">
            <a href="javascript:history.back()" class="btn-back">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                Retour à la sélection
            </a>
            
            <div class="individual-links">
                <a href="?single=<?php echo esc_attr($item1->slug); ?>&type=<?php echo esc_attr($type->slug); ?>" class="btn-single">
                    Voir <?php echo esc_html($item1->name); ?> en détail
                </a>
                <a href="?single=<?php echo esc_attr($item2->slug); ?>&type=<?php echo esc_attr($type->slug); ?>" class="btn-single">
                    Voir <?php echo esc_html($item2->name); ?> en détail
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.wp-comparator-compare-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.comparison-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
}

.comparison-title {
    font-size: 2.5em;
    margin: 0 0 20px 0;
    color: #333;
    font-weight: 700;
}

.comparison-intro {
    font-size: 1.2em;
    color: #666;
    line-height: 1.6;
    max-width: 800px;
    margin: 0 auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #0073aa;
}

.contracts-overview {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 30px;
    margin-bottom: 50px;
    align-items: center;
}

.contract-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.contract-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.contract-header {
    text-align: center;
    margin-bottom: 20px;
}

.contract-logo {
    max-width: 120px;
    max-height: 60px;
    object-fit: contain;
    margin-bottom: 15px;
}

.contract-header h2 {
    margin: 0;
    color: #0073aa;
    font-size: 1.5em;
    font-weight: 600;
}

.contract-info p {
    margin: 8px 0;
    color: #666;
    line-height: 1.5;
}

.contract-description {
    margin: 15px 0;
    padding: 15px;
    background: #f8f9fa;
    border-left: 4px solid #0073aa;
    border-radius: 4px;
}

.contract-description strong {
    display: block;
    margin-bottom: 8px;
    color: #0073aa;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contract-description p {
    margin: 0;
    color: #555;
    line-height: 1.6;
    font-size: 14px;
}

.contract-actions {
    margin-top: 20px;
    text-align: center;
}

.btn-document {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #dc3545;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-document:hover {
    background: #c82333;
    color: white;
    transform: translateY(-1px);
}

.vs-separator {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #0073aa, #005a87);
    color: white;
    border-radius: 50%;
    font-size: 1.5em;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(0,115,170,0.3);
}

.comparison-table-container {
    margin-bottom: 40px;
}

.comparison-category {
    margin-bottom: 40px;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.category-title {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    margin: 0;
    padding: 20px 25px;
    font-size: 1.4em;
    color: #333;
    border-bottom: 2px solid #0073aa;
    position: relative;
    font-weight: 600;
}

.comparison-table {
    display: grid;
    grid-template-columns: 300px 1fr 1fr;
    gap: 0;
}

.table-header {
    display: contents;
}

.table-header > div {
    background: #f8f9fa;
    padding: 15px 20px;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #e9ecef;
}

.table-row {
    display: contents;
}

.table-row:nth-child(even) .field-column,
.table-row:nth-child(even) .item-column {
    background: #f8f9fa;
}

.field-column,
.item-column {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    min-height: 60px;
}

.field-column {
    background: #f1f3f4;
    border-right: 2px solid #e9ecef;
    font-weight: 600;
    color: #333;
}

.field-value {
    color: #666;
    line-height: 1.5;
    margin-bottom: 10px;
}

.no-data {
    color: #999;
    font-style: italic;
}

.field-more-info {
    margin-top: 10px;
}

.btn-more-info {
    background: none;
    border: none;
    color: #0073aa;
    cursor: pointer;
    text-decoration: underline;
    font-size: 14px;
    padding: 0;
}

.btn-more-info:hover {
    color: #005a87;
}

.more-info-content {
    display: none;
    margin-top: 10px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    font-size: 14px;
    line-height: 1.5;
}

.more-info-content.show {
    display: block;
}

.comparison-actions {
    text-align: center;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.btn-back,
.btn-single {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-back {
    background: #6c757d;
    color: white;
}

.btn-back:hover {
    background: #5a6268;
    color: white;
}

.btn-single {
    background: #28a745;
    color: white;
    margin: 0 5px;
}

.btn-single:hover {
    background: #218838;
    color: white;
}

.individual-links {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.info-btn,
.info-btn-small {
    background: none;
    border: none;
    cursor: pointer;
    color: #0073aa;
    padding: 0;
    display: inline-flex;
    align-items: center;
    font-size: 16px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    justify-content: center;
    transition: all 0.2s ease;
}

.info-btn:hover,
.info-btn-small:hover {
    background: #0073aa;
    color: white;
}

.info-tooltip {
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 5px;
    background: #333;
    color: white;
    padding: 10px;
    border-radius: 6px;
    font-size: 14px;
    max-width: 300px;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    line-height: 1.4;
    display: none;
}

.no-comparison-data {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    font-size: 1.2em;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .comparison-title {
        font-size: 1.8em;
    }
    
    .contracts-overview {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .vs-separator {
        width: 60px;
        height: 60px;
        font-size: 1.2em;
    }
    
    .comparison-table {
        grid-template-columns: 1fr;
    }
    
    .table-header {
        display: none;
    }
    
    .table-row {
        display: block;
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 20px;
    }
    
    .field-column,
    .item-column {
        display: block;
        border-right: none;
        border-bottom: 1px solid #e9ecef;
    }
    
    .field-column {
        background: #0073aa;
        color: white;
        font-weight: bold;
        text-align: center;
    }
    
    .comparison-actions {
        flex-direction: column;
        text-align: center;
    }
    
    .individual-links {
        justify-content: center;
    }
}
</style>