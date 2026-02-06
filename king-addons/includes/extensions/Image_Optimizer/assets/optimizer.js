/**
 * King Addons - Image Optimizer Core
 * 
 * Browser-based image conversion using Canvas API (WebP only).
 *
 * @package King_Addons
 */

(function($) {
    'use strict';

    // Check if kingImageOptimizer is defined
    if (typeof kingImageOptimizer === 'undefined') {
        console.error('King Image Optimizer: Configuration not found');
        return;
    }

    /**
     * King Image Optimizer Class
     */
    class KingImageOptimizer {
        constructor(config) {
            this.config = config;
            this.settings = config.settings || {};
            this.strings = config.strings || {};
        }

        /**
         * Initialize the optimizer
         */
        init() {
            // no-op (canvas-only)
        }

        /**
         * Optimize an image
         * 
         * @param {Object} imageData - Image data object
         * @param {Object} options - Optimization options
         * @returns {Promise<Object>} - Optimized image result
         */
        async optimize(imageData, options = {}) {
            const defaults = {
                quality: this.settings.quality || 82,
                resize: this.settings.resize_enabled || false,
                maxWidth: this.settings.max_width || 2048
            };

            const opts = { ...defaults, ...options };

            // Canvas-only WebP conversion
            return this.optimizeWithCanvas(imageData, opts);
        }

        /**
         * Optimize using Canvas API (FREE)
         */
        async optimizeWithCanvas(imageData, options) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';

                img.onload = () => {
                    try {
                        let { width, height } = img;
                        
                        // Resize if needed
                        if (options.resize && width > options.maxWidth) {
                            const ratio = options.maxWidth / width;
                            width = options.maxWidth;
                            height = Math.round(height * ratio);
                        }

                        // Create canvas
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        // WebP output only
                        const mimeType = 'image/webp';
                        const extension = 'webp';

                        // Convert quality to 0-1 range
                        const quality = options.quality / 100;

                        // Export to data URL
                        const dataUrl = canvas.toDataURL(mimeType, quality);
                        
                        // Calculate sizes
                        const base64Data = dataUrl.split(',')[1];
                        const optimizedSize = Math.round(base64Data.length * 0.75); // Approximate binary size
                        const originalSize = imageData.filesize || 0;
                        const savedBytes = Math.max(0, originalSize - optimizedSize);

                        resolve({
                            success: true,
                            data: dataUrl,
                            originalSize: originalSize,
                            optimizedSize: optimizedSize,
                            savedBytes: savedBytes,
                            savingsPercent: originalSize > 0 ? Math.round((savedBytes / originalSize) * 100) : 0,
                            format: extension,
                            width: width,
                            height: height,
                            method: 'canvas'
                        });

                    } catch (error) {
                        reject({
                            success: false,
                            error: error.message
                        });
                    }
                };

                img.onerror = () => {
                    reject({
                        success: false,
                        error: 'Failed to load image'
                    });
                };

                // Load image from URL
                if (imageData.url) {
                    img.src = imageData.url;
                } else if (imageData.data) {
                    img.src = imageData.data;
                } else {
                    reject({
                        success: false,
                        error: 'No image source provided'
                    });
                }
            });
        }

        /**
         * Format bytes to human readable
         */
        static formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 B';
            
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    }

    // Initialize and export
    window.KingImageOptimizer = KingImageOptimizer;
    window.kingOptimizer = new KingImageOptimizer(kingImageOptimizer);
    window.kingOptimizer.init();

})(jQuery);
