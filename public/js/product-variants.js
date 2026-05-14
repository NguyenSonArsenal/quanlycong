// ============================================
// PRODUCT VARIANTS MANAGEMENT
// ============================================

let variantIndex = 0;
let deletedVariantIds = [];

/**
 * Initialize variant index from existing variants
 */
function initializeVariantIndex() {
    const existingVariants = document.querySelectorAll('.variant-item');
    if (existingVariants.length > 0) {
        // Get the highest index from existing variants
        existingVariants.forEach(item => {
            const index = parseInt(item.getAttribute('data-index'));
            if (index >= variantIndex) {
                variantIndex = index + 1;
            }
        });
    }
}

/**
 * Add new variant
 */
function addVariant() {
    const container = document.getElementById('variantsContainer');

    const variantHtml = `
        <div class="variant-item" data-index="${variantIndex}">
            <input type="hidden" name="variants[${variantIndex}][id]" value="">
            
            <div class="variant-header">
                <span class="variant-title">
                    <i class="fas fa-box"></i> Biến thể #${variantIndex + 1}
                </span>
                <button type="button" class="variant-remove-btn" onclick="removeVariant(this)">
                    <i class="fas fa-times"></i> Xóa
                </button>
            </div>
            
            <div class="variant-fields">
                <!-- Đơn vị -->
                <div class="variant-field">
                    <label class="required">Đơn vị</label>
                    <input type="text" 
                           name="variants[${variantIndex}][unit]" 
                           class="form-control"
                           placeholder="Hộp, Vỉ, Chai, Gói..."
                           required>
                </div>
                
                <!-- Số lượng/đơn vị -->
                <div class="variant-field">
                    <label>Số lượng/đơn vị</label>
                    <input type="text" 
                           name="variants[${variantIndex}][quantity_per_unit]" 
                           class="form-control"
                           placeholder="30 gói x 1.6g, 6 vỉ x 4 viên...">
                </div>
                
                <!-- Giá gốc -->
                <div class="variant-field">
                    <label class="required">Giá gốc (VNĐ)</label>
                    <input type="text" 
                           name="variants[${variantIndex}][price]" 
                           class="form-control variant-price"
                           data-index="${variantIndex}"
                           placeholder="0"
                           oninput="formatVariantPrice(this)"
                           required>
                </div>
                
                <!-- % Giảm giá -->
                <div class="variant-field">
                    <label>Giảm giá (%)</label>
                    <input type="number" 
                           name="variants[${variantIndex}][sale]" 
                           class="form-control variant-sale"
                           data-index="${variantIndex}"
                           min="0" 
                           max="100"
                           value="0"
                           placeholder="0"
                           oninput="calculateVariantSalePrice(${variantIndex})">
                </div>
                
                <!-- Giá sau giảm -->
                <div class="variant-field">
                    <label>Giá sau giảm (VNĐ)</label>
                    <input type="text" 
                           name="variants[${variantIndex}][price_sale]" 
                           class="form-control variant-price-sale"
                           id="variantPriceSale${variantIndex}"
                           placeholder="0"
                           readonly>
                </div>
                
                <!-- Tồn kho -->
                <div class="variant-field">
                    <label>Tồn kho</label>
                    <input type="number" 
                           name="variants[${variantIndex}][stock]" 
                           class="form-control"
                           min="0"
                           value="0"
                           placeholder="0">
                </div>
                
                <!-- SKU -->
                <div class="variant-field variant-field-full">
                    <label>Mã SKU (tùy chọn)</label>
                    <input type="text" 
                           name="variants[${variantIndex}][sku]" 
                           class="form-control"
                           placeholder="VD: PANADOL-HOP-30GOI">
                </div>
                
                <!-- Trạng thái -->
                <div class="variant-field variant-field-full">
                    <label>Trạng thái</label>
                    <div class="variant-status-group">
                        <label>
                            <input type="radio" 
                                   name="variants[${variantIndex}][status]" 
                                   value="1" 
                                   checked>
                            <span>Kích hoạt</span>
                        </label>
                        <label>
                            <input type="radio" 
                                   name="variants[${variantIndex}][status]" 
                                   value="-1">
                            <span>Không kích hoạt</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', variantHtml);
    variantIndex++;
}

/**
 * Remove variant
 */
function removeVariant(button) {
    const variantItem = button.closest('.variant-item');
    const variantIdInput = variantItem.querySelector('input[name*="[id]"]');
    const variantId = variantIdInput ? variantIdInput.value : null;

    // If editing existing variant, add to delete list
    if (variantId) {
        deletedVariantIds.push(variantId);
        document.getElementById('deleteVariantsInput').value = deletedVariantIds.join(',');
    }

    // Remove from DOM with animation
    variantItem.style.opacity = '0';
    variantItem.style.transform = 'translateX(-20px)';
    setTimeout(() => {
        variantItem.remove();

        // Show message if no variants left
        const remainingVariants = document.querySelectorAll('.variant-item');
        if (remainingVariants.length === 0) {
            showNoVariantsMessage();
        }
    }, 300);
}

/**
 * Show no variants message
 */
function showNoVariantsMessage() {
    const container = document.getElementById('variantsContainer');
    if (container.children.length === 0) {
        container.innerHTML = '<div class="no-variants-message">Chưa có biến thể nào. Nhấn "Thêm biến thể" để tạo mới.</div>';
    }
}

/**
 * Format variant price with thousand separator
 */
function formatVariantPrice(input) {
    let value = input.value.replace(/\D/g, '');

    if (value && parseInt(value) < 0) {
        value = '0';
    }

    if (value) {
        value = parseInt(value).toLocaleString('vi-VN');
    }

    input.value = value;

    // Auto calculate sale price
    const index = input.getAttribute('data-index');
    calculateVariantSalePrice(index);

    return value;
}

/**
 * Get raw number from formatted value
 */
function getRawVariantNumber(formattedValue) {
    return parseFloat(formattedValue.replace(/\./g, '')) || 0;
}

/**
 * Calculate variant sale price
 */
function calculateVariantSalePrice(index) {
    const priceInput = document.querySelector(`input[name="variants[${index}][price]"]`);
    const saleInput = document.querySelector(`input[name="variants[${index}][sale]"]`);
    const priceSaleInput = document.getElementById(`variantPriceSale${index}`);

    if (!priceInput || !saleInput || !priceSaleInput) return;

    const price = getRawVariantNumber(priceInput.value);
    const salePercent = parseFloat(saleInput.value) || 0;

    if (salePercent > 0 && salePercent <= 100) {
        const salePrice = price - (price * salePercent / 100);
        priceSaleInput.value = Math.round(salePrice).toLocaleString('vi-VN');
    } else {
        priceSaleInput.value = price > 0 ? price.toLocaleString('vi-VN') : '0';
    }
}

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', function () {
    initializeVariantIndex();

    // Format existing variant prices on edit page
    const existingPriceInputs = document.querySelectorAll('.variant-price');
    existingPriceInputs.forEach(input => {
        if (input.value) {
            let value = input.value.replace(/\D/g, '');
            if (value) {
                input.value = parseInt(value).toLocaleString('vi-VN');
            }
        }
    });

    // Format existing sale prices
    const existingSalePriceInputs = document.querySelectorAll('.variant-price-sale');
    existingSalePriceInputs.forEach(input => {
        if (input.value) {
            let value = input.value.replace(/\D/g, '');
            if (value) {
                input.value = parseInt(value).toLocaleString('vi-VN');
            }
        }
    });
});
