jQuery(document).ready(function ($) {
    // Sidebar Navigation
    $('.evo-flex-nav-item').on('click', function (e) {
        e.preventDefault();
        const target = $(this).data('target');

        $('.evo-flex-nav-item').removeClass('active');
        $(this).addClass('active');

        const $section = $(target);
        if ($section.length) {
            $('.evo-flex-section').removeClass('active');
            $section.addClass('active');
        }

        localStorage.setItem('evo_flex_active_tab', target);
    });

    // Auto-activate last tab or default to first
    const lastTab = localStorage.getItem('evo_flex_active_tab');
    if (lastTab && $(`.evo-flex-nav-item[data-target="${lastTab}"]`).length) {
        $(`.evo-flex-nav-item[data-target="${lastTab}"]`).click();
    } else {
        $('.evo-flex-nav-item[data-target="#section-display"]').click();
    }

    // Collapsible Gateways
    $(document).on('click', '.gateway-header', function () {
        const card = $(this).closest('.gateway-card');
        const content = card.find('.gateway-content');

        card.toggleClass('active');
        content.slideToggle(300);
    });

    // Installments Manager
    $(document).on('click', '.add-inst-row', function () {
        const gateway = $(this).data('gateway');
        const list = $(this).siblings('.installments-list');
        const nextMonth = list.find('.installment-row').length + 1;

        const row = `
            <div class="installment-row">
                <div class="inst-col">
                    <input type="number" name="evo_flex_settings[gateways][${gateway}][installments][${nextMonth}][months]" value="${nextMonth}" class="inst-months" />
                    <span>x</span>
                </div>
                <div class="inst-col">
                    <input type="number" step="0.01" name="evo_flex_settings[gateways][${gateway}][installments][${nextMonth}][rate]" value="0" class="inst-rate" />
                    <span>% juros</span>
                </div>
                <button type="button" class="remove-inst-row"><i class="fa-solid fa-trash-can"></i></button>
            </div>
        `;
        list.append(row);
    });

    $(document).on('click', '.remove-inst-row', function () {
        $(this).closest('.installment-row').remove();
    });

    // Media Uploader
    $(document).on('click', '.evo-flex-upload-btn', function (e) {
        e.preventDefault();
        const btn = $(this);
        const input = btn.siblings('input[type="text"]');
        
        const frame = wp.media({
            title: 'Escolher Ícone',
            button: { text: 'Usar este ícone' },
            multiple: false
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            input.val(attachment.url);
        });

        frame.open();
    });

    // JSON Model Manager
    let loadedLayouts = {};

    function loadCustomLayouts() {
        const list = $('#evo-flex-custom-list');
        if (!list.length) return;

        $.ajax({
            url: evoFlexAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'evo_flex_get_custom_layouts',
                nonce: evoFlexAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    loadedLayouts = response.data;
                    if (Object.keys(loadedLayouts).length > 0) {
                        list.empty();
                        Object.keys(loadedLayouts).forEach(id => {
                            const layout = loadedLayouts[id];
                            const card = `
                                <div class="evo-flex-card" style="margin-bottom: 0;">
                                    <div class="evo-flex-card-body" style="padding: 15px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <strong style="display: block;">${layout.name}</strong>
                                                <small style="color: #64748b; font-size: 11px;">ID: ${id}</small>
                                            </div>
                                            <div style="display: flex; gap: 8px;">
                                                <button type="button" class="button button-small evo-flex-btn-download-layout" data-id="${id}" title="Baixar JSON"><i class="fa-solid fa-download"></i></button>
                                                <button type="button" class="button button-small evo-flex-btn-delete-layout" data-id="${id}" title="Excluir"><i class="fa-solid fa-trash-can"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            list.append(card);
                        });
                    }
                }
            }
        });
    }

    loadCustomLayouts();

    // Download Logic
    $(document).on('click', '.evo-flex-btn-download-layout', function() {
        const id = $(this).data('id');
        const layout = loadedLayouts[id];
        if (!layout) return;

        // Prepare the JSON for export
        const exportData = {
            id: id,
            name: layout.name,
            html: layout.html,
            css: layout.css
        };

        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(exportData, null, 2));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "evo-layout-" + id + ".json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    });

    $('#evo-flex-btn-import').on('click', function() {
        const jsonText = $('#evo-flex-import-json').val();
        if (!jsonText) return;

        try {
            const data = JSON.parse(jsonText);
            
            $.ajax({
                url: evoFlexAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'evo_flex_import_layout',
                    nonce: evoFlexAdmin.nonce,
                    layout_data: data
                },
                success: function(response) {
                    if (response.success) {
                        alert('Modelo importado com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao importar: ' + response.data);
                    }
                }
            });
        } catch (e) {
            alert('JSON inválido. Verifique a sintaxe.');
        }
    });

    $(document).on('click', '.evo-flex-btn-delete-layout', function() {
        if (!confirm('Tem certeza que deseja excluir este modelo?')) return;
        
        const id = $(this).data('id');
        $.ajax({
            url: evoFlexAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'evo_flex_delete_layout',
                nonce: evoFlexAdmin.nonce,
                layout_id: id
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    // Preview Sync
    let previewTimeout;
    function updateLayoutPreview() {
        const val = $('#evo_flex_layout_select').val();
        const preview = $('#evo-flex-layout-preview');
        
        if (!val) {
            preview.html('<div class="preview-placeholder">Selecione um modelo para ver o preview</div>');
            return;
        }

        preview.css('opacity', '0.5');

        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(function() {
            $.ajax({
                url: evoFlexAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'evo_flex_get_layout_preview',
                    nonce: evoFlexAdmin.nonce,
                    layout_id: val
                },
                success: function(response) {
                    if (response.success) {
                        const $content = $(response.data);
                        const $styles = $content.filter('style.evo-flex-dynamic-preview-css');
                        const $html = $content.not('style.evo-flex-dynamic-preview-css');

                        // Clean old preview styles and append new ones to head
                        $('.evo-flex-dynamic-preview-css').remove();
                        if ($styles.length) {
                            $('head').append($styles);
                        }

                        preview.html($html).css('opacity', '1');
                    } else {
                        preview.html('<div class="preview-placeholder">Erro ao carregar preview: ' + (response.data || 'Erro desconhecido') + '</div>').css('opacity', '1');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Evo Flex Preview Error:', status, error);
                    preview.html('<div class="preview-placeholder">Erro de conexão: ' + error + '</div>').css('opacity', '1');
                }
            });
        }, 300);
    }

    $('#evo_flex_layout_select').on('change', updateLayoutPreview);
    
    // Trigger on load
    if ($('#evo_flex_layout_select').val()) {
        updateLayoutPreview();
    }
});
