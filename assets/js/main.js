// Mobile Navigation Toggle
const hamburger = document.querySelector('.hamburger');
const navLinks = document.querySelector('.nav-links');

if (hamburger) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navLinks.classList.toggle('active');
    });
}

// Close mobile menu when clicking on a nav link
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navLinks.classList.remove('active');
    });
});

// Sticky Navigation on Scroll
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.padding = '10px 0';
        navbar.style.background = 'rgba(255, 255, 255, 0.98)';
        navbar.style.boxShadow = '0 2px 15px rgba(0, 0, 0, 0.1)';
    } else {
        navbar.style.padding = '15px 0';
        navbar.style.background = '#fff';
        navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
    }
});

// Smooth Scrolling for Anchor Links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80, // Adjust for fixed header
                behavior: 'smooth'
            });
        }
    });
});

// Add active class to current navigation link
const sections = document.querySelectorAll('section');
const navItems = document.querySelectorAll('.nav-links a');

window.addEventListener('scroll', () => {
    let current = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        
        if (pageYOffset >= (sectionTop - 200)) {
            current = section.getAttribute('id');
        }
    });
    
    navItems.forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('href') === `#${current}`) {
            item.classList.add('active');
        }
    });
});

// Add to Cart Functionality
function addToCart(productId, productName, price) {
    // In a real application, you would add this to a cart in local storage or send to a server
    console.log(`Added to cart: ${productName} - $${price}`);
    
    // Show success message
    showNotification(`${productName} added to cart!`);
}

// Notification System
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    
    // Add styles
    notification.style.position = 'fixed';
    notification.style.bottom = '20px';
    notification.style.right = '20px';
    notification.style.backgroundColor = '#4CAF50';
    notification.style.color = 'white';
    notification.style.padding = '15px 25px';
    notification.style.borderRadius = '5px';
    notification.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
    notification.style.zIndex = '1000';
    notification.style.transform = 'translateY(100px)';
    notification.style.opacity = '0';
    notification.style.transition = 'all 0.3s ease';
    
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        notification.style.transform = 'translateY(0)';
        notification.style.opacity = '1';
    }, 100);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateY(100px)';
        notification.style.opacity = '0';
        
        // Remove from DOM after animation
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Sample food data (in a real app, this would come from a database)
const foodItems = [
    {
        id: 1,
        name: 'Cheeseburger Deluxe',
        description: 'Juicy beef patty with cheese, lettuce, and special sauce',
        price: 5.99,
        image: 'burger.jpg',
        category: 'lunch',
        isSpecial: true
    },
    {
        id: 2,
        name: 'Margherita Pizza',
        description: 'Classic pizza with tomato sauce, mozzarella, and basil',
        price: 8.99,
        image: 'pizza.jpg',
        category: 'lunch',
        isSpecial: false
    },
    {
        id: 3,
        name: 'Caesar Salad',
        description: 'Fresh romaine lettuce with Caesar dressing, croutons, and parmesan',
        price: 6.49,
        image: 'salad.jpg',
        category: 'lunch',
        isSpecial: true
    },
    {
        id: 4,
        name: 'Chicken Wrap',
        description: 'Grilled chicken, lettuce, tomato, and sauce in a tortilla',
        price: 5.49,
        image: 'wrap.jpg',
        category: 'lunch',
        isSpecial: false
    },
    {
        id: 5,
        name: 'French Fries',
        description: 'Crispy golden fries with a pinch of salt',
        price: 2.99,
        image: 'fries.jpg',
        category: 'sides',
        isSpecial: false
    },
    {
        id: 6,
        name: 'Chocolate Brownie',
        description: 'Warm chocolate brownie with chocolate sauce',
        price: 3.49,
        image: 'brownie.jpg',
        category: 'dessert',
        isSpecial: true
    }
];

// Initialize the page with food items
function initFoodItems() {
    const foodGrid = document.querySelector('.food-grid');
    
    if (!foodGrid) return;
    
    // Clear existing items
    foodGrid.innerHTML = '';
    
    // Add food items to the grid
    foodItems.forEach(item => {
        if (item.isSpecial) {
            const foodItem = document.createElement('div');
            foodItem.className = 'food-card';
            foodItem.innerHTML = `
                <div class="food-image">
                    <img src="assets/images/${item.image}" alt="${item.name}">
                    <span class="discount">15% OFF</span>
                </div>
                <div class="food-details">
                    <h3>${item.name}</h3>
                    <p>${item.description}</p>
                    <div class="food-footer">
                        <span class="price">$${item.price.toFixed(2)}</span>
                        <button class="btn btn-sm btn-primary" 
                                onclick="addToCart(${item.id}, '${item.name}', ${item.price.toFixed(2)})">
                            Add to Cart
                        </button>
                    </div>
                </div>
            `;
            foodGrid.appendChild(foodItem);
        }
    });
}

// Initialize the page when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    initFoodItems();
    
    // Initialize cart count from server-side data
    updateCartCountFromServer();
    
    // Add animation to features on scroll
    const features = document.querySelectorAll('.feature');
    
    const featureObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });
    
    features.forEach(feature => {
        feature.style.opacity = '0';
        feature.style.transform = 'translateY(30px)';
        feature.style.transition = 'all 0.6s ease';
        featureObserver.observe(feature);
    });
});

// Update cart count from server (PHP session)
function updateCartCountFromServer() {
    // The cart count is now rendered by PHP, so we just need to ensure it's properly displayed
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        const count = parseInt(cartCountElement.textContent.trim());
        if (count > 0) {
            cartCountElement.style.display = 'inline-block';
        } else {
            cartCountElement.style.display = 'inline-block';
        }
    }
}

// Update cart count globally
function updateCartCount(count) {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        // Show/hide based on count
        if (count > 0) {
            cartCountElement.style.display = 'inline-block';
        } else {
            cartCountElement.style.display = 'inline-block';
        }
    }
}
