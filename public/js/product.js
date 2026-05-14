// ============================================
// IMAGE PREVIEW FUNCTIONS
// ============================================

/**
 * Preview main product image
 */
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const wrapper = document.getElementById('imagePreviewWrapper');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
            preview.src = e.target.result;
            wrapper.style.display = 'inline-block';
        }

        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Remove main product image
 */
function removeImage() {
    document.getElementById('image').value = '';
    document.getElementById('imagePreviewWrapper').style.display = 'none';
    document.getElementById('imagePreview').src = '';
}

// ============================================
// GALLERY IMAGES FUNCTIONS
// ============================================

let galleryFiles = [];

/**
 * Preview gallery images (for create page)
 */
function previewGalleryImages(input) {
    // Check if we're on edit page (has unifiedGalleryGrid)
    const unifiedGrid = document.getElementById('unifiedGalleryGrid');

    if (unifiedGrid) {
        // Edit page logic
        previewGalleryImagesEdit(input);
    } else {
        // Create page logic
        previewGalleryImagesCreate(input);
    }
}

/**
 * Preview gallery images for CREATE page
 */
function previewGalleryImagesCreate(input) {
    const grid = document.getElementById('galleryPreviewGrid');

    if (input.files && input.files.length > 0) {
        // Append new files to existing array instead of replacing
        const newFiles = Array.from(input.files);
        galleryFiles = [...galleryFiles, ...newFiles];

        // Update file input with all files
        const dt = new DataTransfer();
        galleryFiles.forEach(file => dt.items.add(file));
        input.files = dt.files;

        // Rebuild entire grid with all files
        grid.innerHTML = '';
        grid.style.display = 'grid';

        galleryFiles.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = function (e) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'gallery-preview-item';
                itemDiv.innerHTML = `
          <img src="${e.target.result}" alt="Gallery ${index + 1}">
          <button type="button" class="remove-gallery-image" onclick="removeGalleryImage(${index})">&times;</button>
        `;
                grid.appendChild(itemDiv);
            };

            reader.readAsDataURL(file);
        });
    }
}

/**
 * Preview gallery images for EDIT page
 */
function previewGalleryImagesEdit(input) {
    const grid = document.getElementById('unifiedGalleryGrid');
    const uploadZone = grid.querySelector('.gallery-upload-item');

    if (input.files && input.files.length > 0) {
        // Append new files to existing array
        const newFiles = Array.from(input.files);
        galleryFiles = [...galleryFiles, ...newFiles];

        // Update file input with all files
        const dt = new DataTransfer();
        galleryFiles.forEach(file => dt.items.add(file));
        input.files = dt.files;

        // Remove old preview items (new images only)
        const oldPreviews = grid.querySelectorAll('[data-type="new"]');
        oldPreviews.forEach(item => item.remove());

        // Add all new images before upload zone
        galleryFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'gallery-preview-item';
                itemDiv.setAttribute('data-type', 'new');
                itemDiv.setAttribute('data-index', index);
                itemDiv.innerHTML = `
          <img src="${e.target.result}" alt="New ${index + 1}">
          <button type="button" class="remove-gallery-image" onclick="removeGalleryImage(${index})">&times;</button>
          <div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>
        `;
                grid.insertBefore(itemDiv, uploadZone);
            };
            reader.readAsDataURL(file);
        });
    }
}

/**
 * Remove gallery image
 */
function removeGalleryImage(index) {
    // Remove file from array
    galleryFiles.splice(index, 1);

    // Update file input
    const input = document.getElementById('galleryImages');
    const dt = new DataTransfer();
    galleryFiles.forEach(file => dt.items.add(file));
    input.files = dt.files;

    // Check if we're on edit page
    const unifiedGrid = document.getElementById('unifiedGalleryGrid');

    if (unifiedGrid) {
        // Edit page: rebuild new images in unified grid
        const uploadZone = unifiedGrid.querySelector('.gallery-upload-item');
        const oldPreviews = unifiedGrid.querySelectorAll('[data-type="new"]');
        oldPreviews.forEach(item => item.remove());

        if (galleryFiles.length === 0) {
            return;
        }

        galleryFiles.forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'gallery-preview-item';
                itemDiv.setAttribute('data-type', 'new');
                itemDiv.setAttribute('data-index', idx);
                itemDiv.innerHTML = `
          <img src="${e.target.result}" alt="New ${idx + 1}">
          <button type="button" class="remove-gallery-image" onclick="removeGalleryImage(${idx})">&times;</button>
          <div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>
        `;
                unifiedGrid.insertBefore(itemDiv, uploadZone);
            };
            reader.readAsDataURL(file);
        });
    } else {
        // Create page: rebuild preview
        const grid = document.getElementById('galleryPreviewGrid');
        grid.innerHTML = '';

        if (galleryFiles.length === 0) {
            grid.style.display = 'none';
            return;
        }

        galleryFiles.forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'gallery-preview-item';
                itemDiv.innerHTML = `
          <img src="${e.target.result}" alt="Gallery ${idx + 1}">
          <button type="button" class="remove-gallery-image" onclick="removeGalleryImage(${idx})">&times;</button>
        `;
                grid.appendChild(itemDiv);
            };
            reader.readAsDataURL(file);
        });
    }
}

// ============================================
// EDIT PAGE SPECIFIC FUNCTIONS
// ============================================

let deletedImageIds = [];

/**
 * Delete existing gallery image (Edit page only)
 */
function deleteExistingImage(imageId) {
    if (!confirm('Bạn có chắc muốn xóa ảnh này?')) {
        return;
    }

    deletedImageIds.push(imageId);
    document.getElementById('deleteImagesInput').value = deletedImageIds.join(',');

    const item = document.querySelector(`[data-image-id="${imageId}"]`);
    if (item) {
        item.style.opacity = '0.5';
        item.style.pointerEvents = 'none';
        setTimeout(() => item.remove(), 300);
    }
}

/**
 * Update sort order for gallery images (Edit page only)
 */
function updateSortOrder() {
    const grid = document.getElementById('unifiedGalleryGrid');
    if (!grid) return;

    const items = grid.querySelectorAll('.gallery-preview-item[data-image-id]');
    const sortOrder = [];

    items.forEach((item, index) => {
        const imageId = item.getAttribute('data-image-id');
        if (imageId) {
            sortOrder.push({
                id: imageId,
                sort: index
            });
        }
    });

    // Save to hidden input as JSON
    const sortOrderInput = document.getElementById('sortOrderInput');
    if (sortOrderInput) {
        sortOrderInput.value = JSON.stringify(sortOrder);
    }
}

// ============================================
// PRICE FORMATTING & VALIDATION FUNCTIONS
// ============================================

/**
 * Format number with thousand separator (Vietnamese format)
 */
function formatNumber(input) {
    // Remove all non-digit characters
    let value = input.value.replace(/\D/g, '');

    // Prevent negative numbers
    if (value && parseInt(value) < 0) {
        value = '0';
    }

    // Format with thousand separator (dot)
    if (value) {
        value = parseInt(value).toLocaleString('vi-VN');
    }

    input.value = value;
    return value;
}

/**
 * Get raw number value (remove formatting)
 */
function getRawNumber(formattedValue) {
    return parseFloat(formattedValue.replace(/\./g, '')) || 0;
}

/**
 * Validate sale percentage (0-100)
 */
function validateSalePercent(input) {
    // Remove all non-digit characters
    let value = input.value.replace(/\D/g, '');

    // Convert to number
    let numValue = parseInt(value) || 0;

    // Ensure it's between 0 and 100
    if (numValue < 0) {
        numValue = 0;
    } else if (numValue > 100) {
        numValue = 100;
    }

    input.value = numValue;
    return numValue;
}

/**
 * Auto calculate sale price based on price and sale percentage
 */
function calculateSalePrice() {
    const priceInput = document.getElementById('price');
    const saleInput = document.getElementById('sale');
    const priceSaleInput = document.getElementById('price_sale');

    if (!priceInput || !saleInput || !priceSaleInput) return;

    const price = getRawNumber(priceInput.value);
    const salePercent = parseFloat(saleInput.value) || 0;

    if (salePercent > 0 && salePercent <= 100) {
        const salePrice = price - (price * salePercent / 100);
        priceSaleInput.value = Math.round(salePrice).toLocaleString('vi-VN');
    } else {
        // Không giảm giá: giá sale = giá gốc
        priceSaleInput.value = price > 0 ? price.toLocaleString('vi-VN') : '0';
    }
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    // ===== Price formatting and validation =====
    const priceInput = document.getElementById('price');
    const saleInput = document.getElementById('sale');
    const priceSaleInput = document.getElementById('price_sale');

    if (priceInput && saleInput && priceSaleInput) {
        // Format existing values on page load (for edit page)
        if (priceInput.value) {
            let value = priceInput.value.replace(/\D/g, '');
            if (value) {
                priceInput.value = parseInt(value).toLocaleString('vi-VN');
            }
        }
        if (priceSaleInput.value) {
            let value = priceSaleInput.value.replace(/\D/g, '');
            if (value) {
                priceSaleInput.value = parseInt(value).toLocaleString('vi-VN');
            }
        }

        // Price input - only allow numbers, no negative
        priceInput.addEventListener('input', function () {
            formatNumber(this);
            calculateSalePrice();
        });

        // Prevent non-numeric input on keypress
        priceInput.addEventListener('keypress', function (e) {
            if (e.key && !/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Sale percentage input - only allow numbers 0-100
        saleInput.addEventListener('input', function () {
            validateSalePercent(this);
            calculateSalePrice();
        });

        // Prevent non-numeric input on keypress for sale
        saleInput.addEventListener('keypress', function (e) {
            if (e.key && !/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Validate on blur
        priceInput.addEventListener('blur', function () {
            formatNumber(this);
            calculateSalePrice();
        });

        saleInput.addEventListener('blur', function () {
            validateSalePercent(this);
            calculateSalePrice();
        });
    }

    // ===== Initialize SortableJS for drag & drop (Edit page only) =====
    const grid = document.getElementById('unifiedGalleryGrid');
    if (grid && typeof Sortable !== 'undefined') {
        new Sortable(grid, {
            animation: 150,
            draggable: '.gallery-preview-item',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            filter: '.gallery-upload-item',
            onEnd: function (evt) {
                updateSortOrder();
            }
        });
    }
});
