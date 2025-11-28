<?php
// Start the session
session_start();

// Include database configuration
require_once 'config/database.php';

// Set page title
$page_title = "Anne's Canteen - Home";

// Include header
include_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Delicious Food from Anne's Canteen</h1>
            <p>Order your favorite meals from Anne's Canteen and enjoy a quick and convenient dining experience.</p>
            <div class="hero-buttons">
                <a href="menu.php" class="btn btn-primary">Order Now</a>
                <a href="#menu" class="btn btn-outline-light">View Menu</a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3>Fast Delivery</h3>
                <p>Quick and efficient service to get your food to you as soon as possible.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3>Fresh Food</h3>
                <p>Prepared with the freshest ingredients for the best taste.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Our support team is always here to help you with any questions.</p>
            </div>
        </div>
    </div>
</section>

<!-- Menu Section -->
<section id="menu" class="menu-section">
    <div class="container">
        <div class="section-header">
            <h2>Our Popular Dishes</h2>
            <p>Check out our most popular menu items</p>
        </div>
        <div class="menu-grid">
            <?php
            // Fetch popular menu items from database
            $query = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY RAND() LIMIT 4";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($item = $result->fetch_assoc()) {
                    echo '<div class="menu-item">';
                    echo '<div class="menu-item-image">';
                    echo '<img src="' . (file_exists('assets/images/' . $item['image_url']) ? 'assets/images/' . $item['image_url'] : 'assets/images/placeholder.jpg') . '" alt="' . htmlspecialchars($item['name']) . '">';
                    echo '</div>';
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
        <div class="text-center mt-4">
            <a href="menu.php" class="btn btn-outline-primary">View Full Menu</a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to order your favorite food?</h2>
            <p>Join thousands of satisfied customers who enjoy our delicious meals.</p>
            <a href="menu.php" class="btn btn-light">Order Now</a>
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
// Add to cart functionality for index page
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

// Show notification function
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
</script>
?>
