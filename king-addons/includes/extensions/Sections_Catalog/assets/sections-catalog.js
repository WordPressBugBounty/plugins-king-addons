// King Addons Sections Catalog JavaScript

jQuery(document).ready(function ($) {
    'use strict';

    /**
     * Класс для управления каталогом секций
     */
    class SectionsCatalog {
        constructor() {
            this.currentSection = null;
            this.isImporting = false;
            this.bindEvents();
        }

        /**
         * Привязка событий
         */
        bindEvents() {
            // Клик по секции для предпросмотра
            $(document).on('click', '.section-item .preview-btn', this.showSectionPreview.bind(this));
            
            // Клик по кнопке импорта в сетке
            $(document).on('click', '.section-item .import-btn', this.quickImportSection.bind(this));
            
            // Клик по секции (альтернативный способ предпросмотра)
            $(document).on('click', '.section-item', (e) => {
                if (!$(e.target).hasClass('import-btn') && !$(e.target).hasClass('preview-btn')) {
                    this.showSectionPreview.call(this, e);
                }
            });

            // Закрытие попапов
            $(document).on('click', '#close-section-popup', this.closeSectionPreview.bind(this));
            $(document).on('click', '#close-import-popup', this.closeImportPopup.bind(this));
            $(document).on('click', '#close-premium-promo-popup', this.closePremiumPromo.bind(this));

            // Импорт секции из попапа предпросмотра
            $(document).on('click', '#import-section', this.importSectionFromPreview.bind(this));

            // Поиск и фильтры
            $(document).on('keyup', '#section-search', this.debounce(this.filterSections.bind(this), 500));
            $(document).on('change', '#section-category', this.filterSections.bind(this));
            $(document).on('change', '#section-type', this.filterSections.bind(this));
            $(document).on('change', '#section-tags input', this.filterSections.bind(this));
            $(document).on('click', '#reset-filters', this.resetFilters.bind(this));

            // Пагинация
            $(document).on('click', '.pagination a', this.handlePagination.bind(this));

            // Закрытие попапов по клику вне их
            $(document).on('click', '.popup-overlay, #section-preview-popup, #section-importing-popup, #premium-promo-popup', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeSectionPreview();
                    this.closeImportPopup();
                    this.closePremiumPromo();
                }
            });

            // ESC для закрытия попапов
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeSectionPreview();
                    this.closeImportPopup();
                    this.closePremiumPromo();
                }
            });
        }

        /**
         * Показать предпросмотр секции
         */
        showSectionPreview(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $sectionItem = $(e.target).closest('.section-item');
            const sectionKey = $sectionItem.data('section-key');
            const sectionPlan = $sectionItem.data('section-plan');
            
            if (!sectionKey) return;

            this.currentSection = {
                key: sectionKey,
                plan: sectionPlan,
                element: $sectionItem
            };

            // Получаем данные секции
            this.loadSectionData(sectionKey).then((sectionData) => {
                if (sectionData) {
                    this.displaySectionPreview(sectionData);
                }
            });
        }

        /**
         * Загружает данные секции с сервера
         */
        async loadSectionData(sectionKey) {
            try {
                const response = await $.ajax({
                    url: kingAddonsSectionsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_section_data',
                        section_key: sectionKey,
                        nonce: kingAddonsSectionsData.nonce
                    }
                });

                if (response.success) {
                    return response.data;
                } else {
                    console.error('Error loading section data:', response.data);
                    return null;
                }
            } catch (error) {
                console.error('AJAX error:', error);
                return null;
            }
        }

        /**
         * Отображает предпросмотр секции
         */
        displaySectionPreview(sectionData) {
            const screenshotUrl = kingAddonsSectionsData.screenshotsUrl + this.currentSection.key + '.png';
            
            // Обновляем попап с данными секции
            $('#section-preview-image').attr('src', screenshotUrl);
            $('#section-type-display').text(`Type: ${sectionData.section_type || 'General'}`);
            $('#section-parent-template').text(`From: ${sectionData.parent_template || 'Template'}`);
            
            // Настраиваем кнопку импорта
            const $importBtn = $('#import-section');
            $importBtn.attr('data-section-key', this.currentSection.key);
            $importBtn.attr('data-section-plan', this.currentSection.plan);
            
            if (this.currentSection.plan === 'premium' && !kingAddonsSectionsData.isPremium) {
                $importBtn.text('Get Premium Access');
                $importBtn.removeClass('btn-success').addClass('btn-premium');
            } else {
                $importBtn.text('Import Section');
                $importBtn.removeClass('btn-premium').addClass('btn-success');
            }

            // Показываем попап
            $('#section-preview-popup').addClass('show');
        }

        /**
         * Быстрый импорт секции (без предпросмотра)
         */
        quickImportSection(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $sectionItem = $(e.target).closest('.section-item');
            const sectionKey = $sectionItem.data('section-key');
            const sectionPlan = $sectionItem.data('section-plan');
            
            if (!sectionKey) return;

            // Проверяем права на premium секции
            if (sectionPlan === 'premium' && !kingAddonsSectionsData.isPremium) {
                this.showPremiumPromo();
                return;
            }

            this.currentSection = {
                key: sectionKey,
                plan: sectionPlan,
                element: $sectionItem
            };

            this.startSectionImport();
        }

        /**
         * Импорт секции из попапа предпросмотра
         */
        importSectionFromPreview(e) {
            e.preventDefault();
            
            const sectionKey = $(e.target).attr('data-section-key');
            const sectionPlan = $(e.target).attr('data-section-plan');
            
            if (!sectionKey) return;

            // Проверяем права на premium секции
            if (sectionPlan === 'premium' && !kingAddonsSectionsData.isPremium) {
                this.closeSectionPreview();
                this.showPremiumPromo();
                return;
            }

            this.closeSectionPreview();
            this.startSectionImport();
        }

        /**
         * Начинает процесс импорта секции
         */
        async startSectionImport() {
            if (this.isImporting) return;
            
            this.isImporting = true;
            
            // Определяем ID страницы для импорта
            const pageId = await this.getCurrentPageId();
            if (!pageId) {
                this.isImporting = false;
                alert('Could not determine current page ID. Please save the page first or create a new page.');
                return;
            }

            this.showImportProgress();
            this.updateImportProgress(10, 'Preparing section import...');

            try {
                // Запускаем импорт секции
                const response = await $.ajax({
                    url: kingAddonsSectionsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'import_section',
                        section_key: this.currentSection.key,
                        page_id: pageId,
                        nonce: kingAddonsSectionsData.nonce
                    }
                });

                if (response.success) {
                    this.updateImportProgress(30, 'Section import started...');
                    this.processImportImages(pageId);
                } else {
                    throw new Error(response.data || 'Unknown error');
                }
            } catch (error) {
                this.handleImportError(error);
            }
        }

        /**
         * Обрабатывает изображения секции
         */
        async processImportImages(pageId) {
            try {
                this.updateImportProgress(50, 'Processing section images...');
                
                const response = await $.ajax({
                    url: kingAddonsSectionsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'process_import_images'
                    }
                });

                if (response.success) {
                    if (response.data.progress !== undefined) {
                        // Продолжаем обработку изображений
                        const progress = Math.round(50 + (response.data.progress / 100) * 30);
                        this.updateImportProgress(progress, response.data.message || 'Processing images...');
                        
                        // Рекурсивно продолжаем обработку
                        setTimeout(() => this.processImportImages(pageId), 1000);
                    } else if (response.data.processing_complete) {
                        // Завершаем импорт
                        this.finalizeSectionImport(pageId);
                    } else {
                        // Новая страница создана (fallback)
                        this.handleNewPageCreated(response.data);
                    }
                } else {
                    throw new Error(response.data || 'Image processing failed');
                }
            } catch (error) {
                this.handleImportError(error);
            }
        }

        /**
         * Завершает импорт секции
         */
        async finalizeSectionImport(pageId) {
            try {
                this.updateImportProgress(85, 'Finalizing section import...');
                
                const response = await $.ajax({
                    url: kingAddonsSectionsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'king_addons_merge_with_existing_page',
                        page_id: pageId,
                        nonce: kingAddonsSectionsData.nonce
                    }
                });

                if (response.success) {
                    this.updateImportProgress(100, 'Section imported successfully!');
                    this.handleImportSuccess(pageId, response.data);
                } else {
                    throw new Error(response.data || 'Finalization failed');
                }
            } catch (error) {
                this.handleImportError(error);
            }
        }

        /**
         * Обрабатывает успешный импорт
         */
        handleImportSuccess(pageId, data) {
            console.log('✅ Section imported successfully:', data);
            
            setTimeout(() => {
                $('#close-import-popup').show();
                
                // Показываем информацию об успешном импорте
                this.updateImportProgress(100, 
                    `Section imported! Added ${data.imported_elements || 0} new elements. ` +
                    'Reload the page to see changes or close this dialog.'
                );
                
                // Автоматическое закрытие через 3 секунды
                setTimeout(() => {
                    const reload = confirm('Section imported successfully! Reload the page to see the imported content?');
                    if (reload) {
                        window.location.reload();
                    } else {
                        this.closeImportPopup();
                    }
                }, 3000);
            }, 1000);
        }

        /**
         * Обрабатывает создание новой страницы
         */
        handleNewPageCreated(data) {
            this.updateImportProgress(100, 'New page created with section!');
            
            setTimeout(() => {
                $('#close-import-popup').show();
                
                if (data.page_url) {
                    const openPage = confirm('Section imported to a new page! Would you like to open it?');
                    if (openPage) {
                        window.open(data.page_url, '_blank');
                    }
                }
                
                this.closeImportPopup();
            }, 2000);
        }

        /**
         * Обрабатывает ошибки импорта
         */
        handleImportError(error) {
            console.error('❌ Import error:', error);
            this.updateImportProgress(0, `Import failed: ${error.message || error}`);
            
            setTimeout(() => {
                $('#close-import-popup').show();
            }, 2000);
            
            this.isImporting = false;
        }

        /**
         * Получает ID текущей страницы
         */
        async getCurrentPageId() {
            // Пытаемся получить ID из различных источников
            if (window.elementor && window.elementor.config && window.elementor.config.post_id) {
                return window.elementor.config.post_id;
            }
            
            if (window.elementorFrontend && window.elementorFrontend.config && window.elementorFrontend.config.post.id) {
                return window.elementorFrontend.config.post.id;
            }

            // Получаем из URL параметров
            const urlParams = new URLSearchParams(window.location.search);
            const postId = urlParams.get('post') || urlParams.get('page_id');
            if (postId) {
                return parseInt(postId);
            }

            // Последняя попытка - запросить у пользователя
            const pageId = prompt('Please enter the page ID where you want to import this section:');
            return pageId ? parseInt(pageId) : null;
        }

        /**
         * Показывает попап прогресса импорта
         */
        showImportProgress() {
            $('#import-progress-bar').css('width', '0%').text('0%');
            $('#import-progress-text').text('Preparing import...');
            $('#close-import-popup').hide();
            $('#section-importing-popup').addClass('show');
        }

        /**
         * Обновляет прогресс импорта
         */
        updateImportProgress(percent, message) {
            $('#import-progress-bar').css('width', percent + '%').text(percent + '%');
            $('#import-progress-text').text(message);
        }

        /**
         * Показывает премиум промо
         */
        showPremiumPromo() {
            $('#premium-promo-popup').addClass('show');
        }

        /**
         * Закрывает попап предпросмотра
         */
        closeSectionPreview() {
            $('#section-preview-popup').removeClass('show');
            this.currentSection = null;
        }

        /**
         * Закрывает попап импорта
         */
        closeImportPopup() {
            $('#section-importing-popup').removeClass('show');
            this.isImporting = false;
        }

        /**
         * Закрывает премиум промо
         */
        closePremiumPromo() {
            $('#premium-promo-popup').removeClass('show');
        }

        /**
         * Фильтрация секций
         */
        filterSections() {
            const searchQuery = $('#section-search').val();
            const selectedCategory = $('#section-category').val();
            const selectedType = $('#section-type').val();
            const selectedTags = [];

            $('#section-tags input:checked').each(function() {
                selectedTags.push($(this).val());
            });

            this.loadFilteredSections(searchQuery, selectedCategory, selectedType, selectedTags, 1);
        }

        /**
         * Сброс фильтров
         */
        resetFilters() {
            $('#section-search').val('');
            $('#section-category').val('');
            $('#section-type').val('');
            $('#section-tags input:checked').prop('checked', false);
            this.loadFilteredSections('', '', '', [], 1);
        }

        /**
         * Обработка пагинации
         */
        handlePagination(e) {
            e.preventDefault();
            
            const href = $(e.target).attr('href');
            const page = this.getPageFromUrl(href);
            
            const searchQuery = $('#section-search').val();
            const selectedCategory = $('#section-category').val();
            const selectedType = $('#section-type').val();
            const selectedTags = [];

            $('#section-tags input:checked').each(function() {
                selectedTags.push($(this).val());
            });

            this.loadFilteredSections(searchQuery, selectedCategory, selectedType, selectedTags, page);
        }

        /**
         * Извлекает номер страницы из URL
         */
        getPageFromUrl(url) {
            const match = url.match(/paged=(\d+)/);
            return match ? parseInt(match[1]) : 1;
        }

        /**
         * Загружает отфильтрованные секции
         */
        async loadFilteredSections(searchQuery, category, type, tags, page) {
            try {
                // Показываем индикатор загрузки
                $('.sections-grid').addClass('loading');
                
                const response = await $.ajax({
                    url: kingAddonsSectionsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'filter_sections',
                        s: searchQuery,
                        category: category,
                        section_type: type,
                        tags: tags.join(','),
                        paged: page,
                        nonce: kingAddonsSectionsData.nonce
                    }
                });

                if (response.success) {
                    $('.sections-grid').html(response.data.grid_html);
                    $('.pagination').html(response.data.pagination_html);
                    
                    // Скролл к началу результатов
                    this.scrollToTop();
                } else {
                    console.error('Error filtering sections:', response.data);
                }
            } catch (error) {
                console.error('AJAX error:', error);
            } finally {
                $('.sections-grid').removeClass('loading');
            }
        }

        /**
         * Скролл к началу каталога
         */
        scrollToTop() {
            const $target = $('#king-addons-sections-top');
            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - 32 // Учитываем админ бар
                }, 300);
            }
        }

        /**
         * Debounce функция для оптимизации поиска
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Инициализация каталога секций
    const sectionsCatalog = new SectionsCatalog();

    // Добавляем индикаторы загрузки
    const style = `
        <style>
        .sections-grid.loading {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }
        .sections-grid.loading::after {
            content: "Loading...";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255,255,255,0.9);
            padding: 20px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 10;
        }
        </style>
    `;
    $('head').append(style);

    // Глобальные функции для отладки
    window.KingAddonsSectionsCatalog = sectionsCatalog;
    
    console.log('🎨 King Addons Sections Catalog initialized successfully!');
}); 