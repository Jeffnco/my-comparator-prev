<div class="wrap">
    <h1>Champs et Catégories</h1>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'field_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Champ ajouté avec succès !</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'field_updated'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Champ modifié avec succès !</p>
        </div>
    <?php endif; ?>
    
    <div class="wp-comparator-admin-content">
        <!-- Sélecteur de type -->
        <div class="admin-section">
            <h2>Sélectionner un type de comparateur</h2>
            <form method="get" action="">
                <input type="hidden" name="page" value="wp-comparator-fields">
                <select name="type_id" onchange="this.form.submit()">
                    <option value="">-- Choisir un type --</option>
                    <?php if (isset($types) && is_array($types)): ?>
                        <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type->id; ?>" <?php selected($selected_type, $type->id); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
        </div>
        
        <?php if ($selected_type): ?>
            <?php 
            // Gestion de l'édition d'un champ
            $edit_field_id = isset($_GET['edit_field']) ? intval($_GET['edit_field']) : 0;
            $edit_field = null;
            if ($edit_field_id && isset($fields)) {
                foreach ($fields as $field) {
                    if ($field->id == $edit_field_id) {
                        $edit_field = $field;
                        break;
                    }
                }
            }
            ?>
            
            <!-- Formulaire d'ajout/modification de champ -->
            <div class="admin-section">
                <h2><?php echo $edit_field ? 'Modifier le champ' : 'Ajouter un nouveau champ'; ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field($edit_field ? 'wp_comparator_update_field' : 'wp_comparator_add_field', '_wpnonce'); ?>
                    <input type="hidden" name="wp_comparator_action" value="<?php echo $edit_field ? 'update_field' : 'add_field'; ?>">
                    <input type="hidden" name="type_id" value="<?php echo $selected_type; ?>">
                    <?php if ($edit_field): ?>
                        <input type="hidden" name="field_id" value="<?php echo $edit_field->id; ?>">
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="name">Nom du champ</label>
                            </th>
                            <td>
                                <input type="text" id="name" name="name" class="regular-text" value="<?php echo $edit_field ? esc_attr($edit_field->name) : ''; ?>" required>
                                <p class="description">Ex: Délais d'attente, Maladie, Mode d'indemnisation</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Type de champ</th>
                            <td>
                                <label>
                                    <input type="radio" name="field_type" value="category" <?php echo ($edit_field && $edit_field->field_type == 'category') ? 'checked' : ''; ?> onchange="toggleFieldOptions()">
                                    Catégorie
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="field_type" value="description" <?php echo (!$edit_field || $edit_field->field_type == 'description') ? 'checked' : ''; ?> onchange="toggleFieldOptions()">
                                    Champ description
                                </label>
                            </td>
                        </tr>
                        <tr id="category_options" style="display: <?php echo ($edit_field && $edit_field->field_type == 'category') ? 'table-row' : 'none'; ?>;">
                            <th scope="row">Bouton + infos</th>
                            <td>
                                <label>
                                    <input type="radio" name="has_info_button" value="1" <?php echo ($edit_field && $edit_field->has_info_button == 1) ? 'checked' : ''; ?> onchange="toggleInfoContent()">
                                    Oui
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="has_info_button" value="0" <?php echo (!$edit_field || $edit_field->has_info_button == 0) ? 'checked' : ''; ?> onchange="toggleInfoContent()">
                                    Non
                                </label>
                                <div id="info_content_div" style="margin-top: 10px; display: <?php echo ($edit_field && $edit_field->has_info_button == 1) ? 'block' : 'none'; ?>;">
                                    <label><strong>Contenu de l'infobulle :</strong></label>
                                    <textarea name="info_content" rows="3" class="large-text" placeholder="Contenu de l'infobulle..."><?php echo $edit_field ? esc_textarea($edit_field->info_content) : ''; ?></textarea>
                                </div>
                            </td>
                        </tr>
                        <tr id="parent_category_row" style="display: <?php echo (!$edit_field || $edit_field->field_type == 'description') ? 'table-row' : 'none'; ?>;">
                            <th scope="row">
                                <label for="parent_category_id">Catégorie parente</label>
                            </th>
                            <td>
                                <select id="parent_category_id" name="parent_category_id">
                                    <option value="">-- Choisir une catégorie --</option>
                                    <?php if (isset($categories) && is_array($categories)): ?>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category->id; ?>" <?php echo ($edit_field && $edit_field->parent_category_id == $category->id) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($category->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <p class="description">Obligatoire pour les champs description</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="short_description">Description courte</label>
                            </th>
                            <td>
                                <textarea id="short_description" name="short_description" rows="2" class="large-text"><?php echo $edit_field ? esc_textarea($edit_field->short_description) : ''; ?></textarea>
                                <p class="description">Description qui apparaîtra directement</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="sort_order">Ordre d'affichage</label>
                            </th>
                            <td>
                                <input type="number" id="sort_order" name="sort_order" value="<?php echo $edit_field ? $edit_field->sort_order : '0'; ?>" min="0" class="small-text">
                            </td>
                        </tr>
                        <tr id="filterable_row" style="display: <?php echo (!$edit_field || $edit_field->field_type == 'description') ? 'table-row' : 'none'; ?>;">
                            <th scope="row">Filtrable</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="is_filterable" name="is_filterable" value="1" <?php echo ($edit_field && $edit_field->is_filterable) ? 'checked' : ''; ?> onchange="toggleFilterOptions()">
                                    Ce champ peut être utilisé comme filtre
                                </label>
                            </td>
                        </tr>
                        <tr id="filter_name_row" style="display: <?php echo ($edit_field && $edit_field->is_filterable) ? 'table-row' : 'none'; ?>;">
                            <th scope="row">
                                <label for="filter_name">Nom du filtre</label>
                            </th>
                            <td>
                                <input type="text" id="filter_name" name="filter_name" class="regular-text" value="<?php echo $edit_field ? esc_attr($edit_field->filter_name) : ''; ?>" placeholder="Ex: Délai d'attente">
                                <p class="description">Nom qui apparaîtra dans les filtres sur le site</p>
                            </td>
                        </tr>
                        <tr id="filter_options_row" style="display: <?php echo ($edit_field && $edit_field->is_filterable) ? 'table-row' : 'none'; ?>;">
                            <th scope="row">
                                <label for="filter_options">Options du filtre</label>
                            </th>
                            <td>
                                <div id="filter-options-container">
                                    <?php 
                                    $existing_options = array();
                                    if ($edit_field && !empty($edit_field->filter_options)) {
                                        $existing_options = array_map('trim', explode(',', $edit_field->filter_options));
                                    }
                                    
                                    if (!empty($existing_options)): 
                                        foreach ($existing_options as $index => $option): ?>
                                            <div class="filter-option-row" style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                                                <input type="text" name="filter_option[]" value="<?php echo esc_attr($option); ?>" class="regular-text" placeholder="Option du filtre">
                                                <button type="button" class="button remove-option" onclick="removeFilterOption(this)">Supprimer</button>
                                            </div>
                                        <?php endforeach;
                                    else: ?>
                                        <div class="filter-option-row" style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                                            <input type="text" name="filter_option[]" class="regular-text" placeholder="Option du filtre">
                                            <button type="button" class="button remove-option" onclick="removeFilterOption(this)">Supprimer</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="add-filter-option" class="button" onclick="addFilterOption()">Ajouter une option</button>
                                <p class="description">Définissez les options disponibles pour ce filtre (ex: Oui, Non, Partiellement)</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php echo $edit_field ? 'Modifier le champ' : 'Ajouter le champ'; ?>">
                        <?php if ($edit_field): ?>
                            <a href="?page=wp-comparator-fields&type_id=<?php echo $selected_type; ?>" class="button">Annuler</a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <!-- Liste des champs existants -->
            <div class="admin-section">
                <h2>Champs et catégories existants</h2>
                
                <?php if (!isset($fields) || empty($fields)): ?>
                    <p>Aucun champ créé pour ce type.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Ordre</th>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Catégorie parente</th>
                                <th>Filtrable</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fields as $field): ?>
                                <tr>
                                    <td><?php echo $field->sort_order; ?></td>
                                    <td>
                                        <strong><?php echo esc_html($field->name); ?></strong>
                                        <?php if ($field->field_type == 'category'): ?>
                                            <span class="dashicons dashicons-category" style="color: #0073aa;" title="Catégorie"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $field->field_type == 'category' ? 'Catégorie' : 'Champ description'; ?>
                                        <?php if ($field->has_info_button): ?>
                                            <span class="dashicons dashicons-info" style="color: #666;" title="Avec bouton info"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($field->parent_category_id): ?>
                                            <?php
                                            $parent = null;
                                            foreach ($categories as $cat) {
                                                if ($cat->id == $field->parent_category_id) {
                                                    $parent = $cat;
                                                    break;
                                                }
                                            }
                                            echo $parent ? esc_html($parent->name) : 'Catégorie supprimée';
                                            ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($field->is_filterable): ?>
                                            <span class="dashicons dashicons-filter" style="color: #28a745;" title="Filtrable"></span>
                                            <?php echo esc_html($field->filter_name); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?page=wp-comparator-fields&type_id=<?php echo $selected_type; ?>&edit_field=<?php echo $field->id; ?>" class="button button-small">
                                            Éditer
                                        </a>
                                        <button class="button button-small button-link-delete" onclick="deleteField(<?php echo $field->id; ?>)">
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="admin-section">
                <p>Veuillez d'abord sélectionner un type de comparateur pour gérer ses champs.</p>
                <p><a href="<?php echo admin_url('admin.php?page=wp-comparator-types'); ?>" class="button">Créer un nouveau type</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFieldOptions() {
    const categoryRadio = document.querySelector('input[name="field_type"][value="category"]');
    const categoryOptions = document.getElementById('category_options');
    const parentCategoryRow = document.getElementById('parent_category_row');
    const filterableRow = document.getElementById('filterable_row');
    
    if (categoryRadio.checked) {
        categoryOptions.style.display = 'table-row';
        parentCategoryRow.style.display = 'none';
        filterableRow.style.display = 'none';
        // Masquer aussi les options de filtre si on passe en catégorie
        document.getElementById('filter_name_row').style.display = 'none';
        document.getElementById('filter_options_row').style.display = 'none';
    } else {
        categoryOptions.style.display = 'none';
        parentCategoryRow.style.display = 'table-row';
        filterableRow.style.display = 'table-row';
    }
}

function toggleFilterOptions() {
    const isFilterable = document.getElementById('is_filterable').checked;
    const filterNameRow = document.getElementById('filter_name_row');
    const filterOptionsRow = document.getElementById('filter_options_row');
    
    if (isFilterable) {
        filterNameRow.style.display = 'table-row';
        filterOptionsRow.style.display = 'table-row';
    } else {
        filterNameRow.style.display = 'none';
        filterOptionsRow.style.display = 'none';
    }
}

function addFilterOption() {
    const container = document.getElementById('filter-options-container');
    const newRow = document.createElement('div');
    newRow.className = 'filter-option-row';
    newRow.style.cssText = 'margin-bottom: 10px; display: flex; align-items: center; gap: 10px;';
    newRow.innerHTML = `
        <input type="text" name="filter_option[]" class="regular-text" placeholder="Option du filtre">
        <button type="button" class="button remove-option" onclick="removeFilterOption(this)">Supprimer</button>
    `;
    container.appendChild(newRow);
}

function removeFilterOption(button) {
    const container = document.getElementById('filter-options-container');
    if (container.children.length > 1) {
        button.parentElement.remove();
    } else {
        alert('Vous devez garder au moins une option.');
    }
}

function addFilterOption() {
    const container = document.getElementById('filter-options-container');
    const newRow = document.createElement('div');
    newRow.className = 'filter-option-row';
    newRow.style.cssText = 'margin-bottom: 10px; display: flex; align-items: center; gap: 10px;';
    newRow.innerHTML = `
        <input type="text" name="filter_option[]" class="regular-text" placeholder="Option du filtre">
        <button type="button" class="button remove-option" onclick="removeFilterOption(this)">Supprimer</button>
    `;
    container.appendChild(newRow);
}

function removeFilterOption(button) {
    const container = document.getElementById('filter-options-container');
    if (container.children.length > 1) {
        button.parentElement.remove();
    } else {
        alert('Vous devez garder au moins une option.');
    }
}

function toggleInfoContent() {
    const radioYes = document.querySelector('input[name="has_info_button"][value="1"]');
    const infoDiv = document.getElementById('info_content_div');
    
    if (radioYes && radioYes.checked) {
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}

function deleteField(fieldId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce champ ?')) {
        console.log('Suppression du champ:', fieldId);
    }
}

// Initialiser l'affichage au chargement
document.addEventListener('DOMContentLoaded', function() {
    toggleFieldOptions();
    toggleInfoContent();
    toggleFilterOptions();
});
</script>