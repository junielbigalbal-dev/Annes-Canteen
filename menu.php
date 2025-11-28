<?php
// Start the session
session_start();

// Include database configuration
require_once 'config/database.php';

// Set page title
$page_title = "Anne's Canteen - Menu";

// Include header
include_once 'includes/header.php';
?>

<!-- Menu Page Section -->
<section class="menu-page-section">
    <div class="container">
        <!-- Page Header -->
        <div class="section-header">
            <h1>Our Complete Menu</h1>
            <p>Browse through our delicious selection of meals, snacks, and beverages</p>
        </div>

        <!-- Menu Controls -->
        <div class="menu-controls">
            <div class="search-bar">
                <input type="text" id="searchInput" class="form-control" placeholder="Search for food items...">
                <button class="btn btn-primary search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="filter-options">
                <select id="categoryFilter" class="form-select filter-select">
                    <option value="">All Categories</option>
                    <option value="breakfast">Breakfast</option>
                    <option value="lunch">Lunch</option>
                    <option value="dinner">Dinner</option>
                    <option value="sides">Sides</option>
                    <option value="beverages">Beverages</option>
                    <option value="desserts">Desserts</option>
                </select>
                
                <select id="priceFilter" class="form-select filter-select">
                    <option value="">All Prices</option>
                    <option value="0-50">Under ₱50</option>
                    <option value="50-100">₱50 - ₱100</option>
                    <option value="100-150">₱100 - ₱150</option>
                    <option value="150+">Above ₱150</option>
                </select>
                
                <select id="sortFilter" class="form-select filter-select">
                    <option value="name">Sort by Name</option>
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                    <option value="popular">Most Popular</option>
                </select>
            </div>
        </div>

        <!-- Menu Items Grid -->
        <div class="menu-grid" id="menuGrid">
            <?php
            // Fetch all available menu items from database
            $query = "SELECT mi.*, c.name as category_name FROM menu_items mi 
                     LEFT JOIN categories c ON mi.category_id = c.category_id 
                     WHERE mi.is_available = 1 ORDER BY c.name, mi.name";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($item = $result->fetch_assoc()) {
                    $category_class = '';
                    $badge_class = '';
                    $badge_text = '';
                    
                    // Set category-specific styling
                    switch($item['category_name']) {
                        case 'Breakfast':
                            $category_class = 'breakfast';
                            break;
                        case 'Lunch':
                            $category_class = 'lunch';
                            break;
                        case 'Dinner':
                            $category_class = 'dinner';
                            break;
                        case 'Sides':
                            $category_class = 'sides';
                            break;
                        case 'Beverages':
                            $category_class = 'beverages';
                            break;
                        case 'Desserts':
                            $category_class = 'desserts';
                            break;
                        default:
                            $category_class = 'other';
                    }
                    
                    // Add special badges for featured items
                    if (isset($item['is_featured']) && $item['is_featured']) {
                        $badge_class = 'featured';
                        $badge_text = 'Featured';
                    }
                    
                    echo '<div class="menu-item" data-category="' . htmlspecialchars($category_class) . '" data-price="' . $item['price'] . '" data-name="' . htmlspecialchars(strtolower($item['name'])) . '">';
                    
                    // Menu item image
                    echo '<div class="menu-item-image">';
                    if ($badge_class) {
                        echo '<span class="badge ' . $badge_class . '">' . $badge_text . '</span>';
                    }
                    echo '<img src="' . (file_exists('assets/images/' . $item['image_url']) ? 'assets/images/' . $item['image_url'] : 'assets/images/placeholder.jpg') . '" alt="' . htmlspecialchars($item['name']) . '">';
                    echo '</div>';
                    
                    // Menu item details
                    echo '<div class="menu-item-details">';
                    echo '<h3>' . htmlspecialchars($item['name']) . '</h3>';
                    echo '<p class="item-desc">' . htmlspecialchars(substr($item['description'], 0, 60)) . '...</p>';
                    echo '<div class="item-footer">';
                    echo '<span class="price">₱' . number_format($item['price'], 2) . '</span>';
                    echo '<button class="btn btn-sm btn-primary add-to-cart" data-id="' . $item['item_id'] . '">Add to Cart</button>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p class="no-items">No menu items available at the moment.</p>';
            }
            ?>
        </div>
    </div>
</section>

<!-- Menu Categories Section -->
<section class="menu-categories-section">
    <div class="container">
        <div class="section-header">
            <h2>Menu Categories</h2>
            <p>Explore our different food categories</p>
        </div>
        
        <div class="categories-grid">
            <div class="category-card" data-category="breakfast">
                <div class="category-icon">
                    <i class="fas fa-coffee"></i>
                </div>
                <h3>Breakfast</h3>
                <p>Delicious breakfast items to start your day</p>
                <span class="item-count">2 items</span>
            </div>
            
            <div class="category-card" data-category="lunch">
                <div class="category-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3>Lunch</h3>
                <p>Hearty meals for lunch</p>
                <span class="item-count">2 items</span>
            </div>
            
            <div class="category-card" data-category="dinner">
                <div class="category-icon">
                    <i class="fas fa-moon"></i>
                </div>
                <h3>Dinner</h3>
                <p>Satisfying dinner options</p>
                <span class="item-count">1 items</span>
            </div>
            
            <div class="category-card" data-category="sides">
                <div class="category-icon">
                    <i class="fas fa-cookie"></i>
                </div>
                <h3>Sides</h3>
                <p>Tasty side dishes</p>
                <span class="item-count">1 items</span>
            </div>
            
            <div class="category-card" data-category="beverages">
                <div class="category-icon">
                    <i class="fas fa-glass-water"></i>
                </div>
                <h3>Beverages</h3>
                <p>Refreshing drinks</p>
                <span class="item-count">1 items</span>
            </div>
            
            <div class="category-card" data-category="desserts">
                <div class="category-icon">
                    <i class="fas fa-ice-cream"></i>
                </div>
                <h3>Desserts</h3>
                <p>Sweet treats</p>
                <span class="item-count">1 items</span>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once 'includes/footer.php';

// Close database connection
$conn->close();
?>

<script>
// Cart management
let cart = [];

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    filterMenuItems();
});

// Category filter
document.getElementById('categoryFilter').addEventListener('change', filterMenuItems);

// Price filter
document.getElementById('priceFilter').addEventListener('change', filterMenuItems);

// Sort filter
document.getElementById('sortFilter').addEventListener('change', function() {
    sortMenuItems(this.value);
});

// Filter menu items
function filterMenuItems() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    const priceRange = document.getElementById('priceFilter').value;
    
    const items = document.querySelectorAll('.menu-item');
    
    items.forEach(item => {
        let show = true;
        
        // Search filter
        if (searchTerm && !item.dataset.name.includes(searchTerm)) {
            show = false;
        }
        
        // Category filter
        if (category && item.dataset.category !== category) {
            show = false;
        }
        
        // Price filter
        if (priceRange) {
            const price = parseFloat(item.dataset.price);
            switch(priceRange) {
                case '0-50':
                    if (price >= 50) show = false;
                    break;
                case '50-100':
                    if (price < 50 || price > 100) show = false;
                    break;
                case '100-150':
                    if (price < 100 || price > 150) show = false;
                    break;
                case '150+':
                    if (price <= 150) show = false;
                    break;
            }
        }
        
        item.style.display = show ? 'block' : 'none';
    });
}

// Sort menu items
function sortMenuItems(sortBy) {
    const grid = document.getElementById('menuGrid');
    const items = Array.from(grid.querySelectorAll('.menu-item'));
    
    items.sort((a, b) => {
        switch(sortBy) {
            case 'name':
                return a.dataset.name.localeCompare(b.dataset.name);
            case 'price-low':
                return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            case 'price-high':
                return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            case 'popular':
                // For demo, we'll sort by price high to low as "popular"
                return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            default:
                return 0;
        }
    });
    
    // Re-append sorted items
    items.forEach(item => grid.appendChild(item));
}

// Add to cart
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart')) {
        e.preventDefault();
        const btn = e.target;
        const itemId = btn.dataset.id;
        const itemName = btn.closest('.menu-item').querySelector('h3').textContent;
        
        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Adding...';
        
        // Send AJAX request
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `item_id=${itemId}&quantity=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart count in header
                updateCartCount(data.cart_count);
                
                // Show success notification
                showNotification(`${data.item_name} added to cart!`, 'success');
                
                // Reset button
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-cart-plus me-1"></i>Add to Cart';
            } else {
                // Show error notification
                showNotification(data.message, 'error');
                
                // Reset button
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-cart-plus me-1"></i>Add to Cart';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error adding item to cart', 'error');
            
            // Reset button
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cart-plus me-1"></i>Add to Cart';
        });
    }
});

// Update cart count in header
function updateCartCount(count) {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}

// Show notification
function showNotification(message, type) {
    const notification = document.createElement('div');
    
    // Use website colors
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #00b894 0%, #00a085 100%)';
        notification.style.color = 'white';
    } else {
        notification.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%)';
        notification.style.color = 'white';
    }
    
    notification.style.position = 'fixed';
    notification.style.top = '100px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.padding = '15px 20px';
    notification.style.borderRadius = '10px';
    notification.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
    notification.style.fontFamily = 'Poppins, sans-serif';
    notification.style.fontSize = '0.95rem';
    notification.style.fontWeight = '500';
    notification.style.transform = 'translateX(400px)';
    notification.style.opacity = '0';
    notification.style.transition = 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
    notification.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; opacity: 0.8; margin-left: 15px;">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 100);
    
    // Remove notification after 4 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 400);
    }, 4000);
}

// Category cards click handler
document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('click', function() {
        const category = this.dataset.category;
        document.getElementById('categoryFilter').value = category;
        filterMenuItems();
        
        // Scroll to menu grid
        document.getElementById('menuGrid').scrollIntoView({ behavior: 'smooth' });
    });
});
</script>

<style>
/* Menu Page Specific Styles */
.menu-page-section {
    padding: 100px 0 50px;
    background-color: #f8f9fa;
}

.menu-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    gap: 20px;
    flex-wrap: wrap;
}

.search-bar {
    position: relative;
    max-width: 400px;
    flex: 1;
}

.search-bar input {
    padding: 12px 20px;
    padding-right: 60px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    height: 50px;
    font-size: 1rem;
    outline: none;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

.search-bar input:focus {
    border-color: #d0d0d0;
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
    background-color: #fff;
}

.search-bar input::placeholder {
    color: #999;
}

.search-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    border-radius: 50%;
    width: 36px;
    height: 36px;
    border: none;
    background: #ff4444;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(255, 68, 68, 0.3);
}

.search-btn:hover {
    background: #ff3333;
    box-shadow: 0 4px 12px rgba(255, 68, 68, 0.4);
    transform: translateY(-50%) scale(1.05);
}

.search-btn i {
    font-size: 14px;
    margin: 0;
}

.filter-options {
    display: flex;
    gap: 15px;
    align-items: center;
}

.filter-select {
    min-width: 150px;
    border-radius: 20px;
    border: 2px solid #e9ecef;
}

.filter-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
}

/* Use existing menu-grid and menu-item styles from index page */
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.menu-item {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: var(--transition);
}

.menu-item:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.menu-item-image {
    height: 200px;
    overflow: hidden;
}

.menu-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.menu-item:hover .menu-item-image img {
    transform: scale(1.1);
}

.menu-item-details {
    padding: 20px;
}

.menu-item-details h3 {
    font-size: 1.25rem;
    margin-bottom: 10px;
    color: var(--dark-color);
}

.item-desc {
    color: var(--text-light);
    margin-bottom: 15px;
}

.item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--primary-color);
}

.badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
}

.badge.featured {
    background: linear-gradient(135deg, #ff6b6b, #ff5252);
    color: white;
}

.cart-summary {
    position: sticky;
    bottom: 20px;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.cart-item {
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-actions {
    display: flex;
    gap: 10px;
    justify-content: space-between;
}

/* Menu Categories Section */
.menu-categories-section {
    padding: 80px 0;
    background: white;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.category-card {
    text-align: center;
    padding: 40px 30px;
    border-radius: 20px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-color);
}

.category-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.category-card h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: var(--dark-color);
}

.category-card p {
    color: var(--text-light);
    margin-bottom: 15px;
}

.item-count {
    background: rgba(255, 107, 107, 0.1);
    color: var(--primary-color);
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 992px) {
    .menu-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-bar {
        max-width: 100%;
    }
    
    .filter-options {
        justify-content: space-between;
        width: 100%;
    }
    
    .filter-select {
        flex: 1;
    }
    
    .menu-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}

@media (max-width: 768px) {
    .menu-page-section {
        padding: 80px 0 30px;
    }
    
    .menu-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-bar {
        max-width: 100%;
    }
    
    .filter-options {
        justify-content: space-between;
        width: 100%;
    }
    
    .filter-select {
        flex: 1;
    }
    
    .menu-grid {
        grid-template-columns: 1fr;
    }
    
    .cart-summary {
        position: relative;
        top: 0;
        margin-top: 40px;
    }
}

@media (max-width: 576px) {
    .menu-page-section {
        padding: 60px 0 40px;
        padding-top: calc(60px + var(--header-height));
    }
    
    .menu-controls {
        flex-direction: column;
        gap: 15px;
    }
    
    .search-bar {
        max-width: 100%;
    }
    
    .filter-options {
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }
    
    .filter-select {
        flex: 1;
    }
    
    .menu-grid {
        grid-template-columns: 1fr;
    }
    
    .cart-actions {
        flex-direction: column;
    }
    
    .cart-actions .btn {
        width: 100%;
    }
}
</style>
