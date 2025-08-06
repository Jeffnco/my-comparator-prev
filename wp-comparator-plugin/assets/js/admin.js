jQuery(document).ready(function($) {
    
    // Gestion des onglets
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Retirer la classe active de tous les onglets
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').hide();
        
        // Ajouter la classe active à l'onglet cliqué
        $(this).addClass('nav-tab-active');
        
        // Afficher le contenu correspondant
        var target = $(this).attr('href');
        $(target).show();
    });
    
    // Initialiser le premier onglet
    $('.nav-tab:first').addClass('nav-tab-active');
    $('.tab-content:first').show();
    
    // Gestion des suppressions via AJAX
    window.deleteType = function(typeId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce type ? Toutes les données associées seront perdues.')) {
            $.ajax({
                url: wpComparator.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_comparator_delete_type',
                    type_id: typeId,
                    nonce: wpComparator.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression: ' + response.data);
                    }
                },
                error: function() {
                    alert('Erreur de communication avec le serveur');
                }
            });
        }
    };
    
    window.deleteCategory = function(categoryId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ? Tous les champs associés seront supprimés.')) {
            $.ajax({
                url: wpComparator.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_comparator_delete_category',
                    category_id: categoryId,
                    nonce: wpComparator.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression: ' + response.data);
                    }
                },
                error: function() {
                    alert('Erreur de communication avec le serveur');
                }
            });
        }
    };
    
    window.deleteField = function(fieldId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce champ ?')) {
            $.ajax({
                url: wpComparator.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_comparator_delete_field',
                    field_id: fieldId,
                    nonce: wpComparator.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression: ' + response.data);
                    }
                },
                error: function() {
                    alert('Erreur de communication avec le serveur');
                }
            });
        }
    };
    
    window.deleteItem = function(itemId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Toutes les données associées seront perdues.')) {
            $.ajax({
                url: wpComparator.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_comparator_delete_item',
                    item_id: itemId,
                    nonce: wpComparator.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression: ' + response.data);
                    }
                },
                error: function() {
                    alert('Erreur de communication avec le serveur');
                }
            });
        }
    };
    
    // Gestion du drag & drop pour réorganiser les champs
    if ($('.sortable-fields').length) {
        $('.sortable-fields').sortable({
            handle: '.sort-handle',
            update: function(event, ui) {
                var fieldOrders = {};
                $(this).find('tr').each(function(index) {
                    var fieldId = $(this).data('field-id');
                    fieldOrders[fieldId] = index;
                });
                
                $.ajax({
                    url: wpComparator.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wp_comparator_update_field_order',
                        field_orders: fieldOrders,
                        nonce: wpComparator.nonce
                    },
                    success: function(response) {
                        if (!response.success) {
                            alert('Erreur lors de la mise à jour de l\'ordre');
                        }
                    }
                });
            }
        });
    }
    
    // Auto-génération des slugs
    $('input[name="name"]').on('blur', function() {
        var name = $(this).val();
        var slugField = $('input[name="slug"]');
        
        if (name && !slugField.val()) {
            var slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            slugField.val(slug);
        }
    });
    
    // Validation des formulaires
    $('form').on('submit', function(e) {
        var requiredFields = $(this).find('[required]');
        var isValid = true;
        
        requiredFields.each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires');
        }
    });
    
    // Gestion des champs conditionnels
    $('select[name="field_type"]').on('change', function() {
        toggleFieldOptions();
    });
    
    $('input[name="is_filterable"]').on('change', function() {
        var filterTypeRow = $('#filter_type_row');
        if ($(this).is(':checked')) {
            filterTypeRow.show();
        } else {
            filterTypeRow.hide();
        }
    });
    
    // Prévisualisation des images
    $('input[type="url"]').on('blur', function() {
        var url = $(this).val();
        var preview = $(this).siblings('.image-preview');
        
        if (url && (url.match(/\.(jpeg|jpg|gif|png)$/i))) {
            if (!preview.length) {
                preview = $('<div class="image-preview" style="margin-top: 10px;"></div>');
                $(this).after(preview);
            }
            preview.html('<img src="' + url + '" style="max-width: 100px; height: auto; border: 1px solid #ddd; border-radius: 3px;">');
        } else {
            preview.remove();
        }
    });
    
    // Confirmation avant suppression
    $('.button-link-delete').on('click', function(e) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
            e.preventDefault();
        }
    });
    
    // Tooltips
    if ($.fn.tooltip) {
        $('[title]').tooltip({
            position: { my: "left+15 center", at: "right center" }
        });
    }
    
    // Accordéons
    $('.accordion-header').on('click', function() {
        $(this).next('.accordion-content').slideToggle();
        $(this).find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
    });
    
    // Recherche en temps réel
    $('.search-input').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        var targetTable = $($(this).data('target'));
        
        targetTable.find('tbody tr').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(searchTerm) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });
});

// Fonction pour basculer l'affichage des options de champ
function toggleFieldOptions() {
    const fieldType = document.getElementById('field_type').value;
    const optionsRow = document.getElementById('field_options_row');
    
    if (['select', 'radio', 'checkbox'].includes(fieldType)) {
        optionsRow.style.display = 'table-row';
    } else {
        optionsRow.style.display = 'none';
    }
}

// Fonction pour charger les catégories via AJAX
function loadCategories(typeId) {
    if (!typeId) return;
    
    jQuery.ajax({
        url: wpComparator.ajaxUrl,
        type: 'POST',
        data: {
            action: 'wp_comparator_get_categories',
            type_id: typeId,
            nonce: wpComparator.nonce
        },
        success: function(response) {
            if (response.success) {
                var categorySelect = jQuery('#category_select');
                categorySelect.empty().append('<option value="">-- Choisir une catégorie --</option>');
                
                jQuery.each(response.data, function(index, category) {
                    categorySelect.append('<option value="' + category.id + '">' + category.name + '</option>');
                });
            }
        }
    });
}

// Fonction pour charger les champs via AJAX
function loadFields(categoryId) {
    if (!categoryId) return;
    
    jQuery.ajax({
        url: wpComparator.ajaxUrl,
        type: 'POST',
        data: {
            action: 'wp_comparator_get_fields',
            category_id: categoryId,
            nonce: wpComparator.nonce
        },
        success: function(response) {
            if (response.success) {
                // Mettre à jour la liste des champs
                console.log('Champs chargés:', response.data);
            }
        }
    });
}