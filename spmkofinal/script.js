// DOM Elements
const featuredProductsContainer = document.getElementById('featured-products');
const categoriesContainer = document.getElementById('categories-container');
const cartIcon = document.querySelector('.cart');
const cartModal = document.getElementById('cart-modal');
const closeCartBtn = document.getElementById('close-cart');
const cartItemsContainer = document.getElementById('cart-items');
const cartCountElement = document.querySelector('.cart-count');
const cartTotalPrice = document.getElementById('cart-total-price');
const loader = document.getElementById('loader');
const userIcon = document.querySelector('.user');
const searchInput = document.querySelector('.search-bar input');
const searchButton = document.querySelector('.search-bar button');
const authButtons = document.getElementById('auth-buttons');
const loginBtn = document.getElementById('btn-login');
const registerBtn = document.getElementById('btn-register');
const checkoutBtn = document.querySelector('.checkout-btn');
const closeCheckoutBtn = document.getElementById('close-checkout');
const checkoutModal = document.getElementById('checkout-modal');
const checkoutForm = document.getElementById('checkout-form');
const continueShoppingBtn = document.getElementById('continue-shopping');
const successModal = document.getElementById('success-modal');

// API URL
const API_URL = 'https://fakestoreapi.com';

// Store state
let products = [];
let categories = [];
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let currentUser = JSON.parse(localStorage.getItem('user')) || null;

// Initialize the application
function initApp() {
    updateCartCount();
    fetchProducts();
    fetchCategories();
    setupEventListeners();
    updateUserUI();
}

// Set up event listeners
function setupEventListeners() {
    // Cart
    cartIcon.addEventListener('click', toggleCart);
    closeCartBtn.addEventListener('click', toggleCart);
    checkoutBtn.addEventListener('click', handleCheckout);
    continueShoppingBtn.addEventListener('click', () => {
        cartModal.classList.remove('active');
    });
    
    // User
    userIcon.addEventListener('click', toggleUserMenu);
    loginBtn.addEventListener('click', showLoginModal);
    registerBtn.addEventListener('click', showRegisterModal);
    
    // Search
    searchButton.addEventListener('click', handleSearch);
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') handleSearch();
    });
    
    // Checkout
    closeCheckoutBtn.addEventListener('click', () => {
        checkoutModal.style.display = 'none';
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === checkoutModal) {
            checkoutModal.style.display = 'none';
        }
        if (e.target === successModal) {
            successModal.style.display = 'none';
        }
    });
}

// Fetch products from API
async function fetchProducts() {
    try {
        loader.style.display = 'block';
        const response = await fetch(`${API_URL}/products`);
        products = await response.json();
        displayFeaturedProducts(products.slice(0, 8)); // Display first 8 products as featured
    } catch (error) {
        console.error('Error fetching products:', error);
        showNotification('Failed to load products. Please try again later.', 'error');
    } finally {
        loader.style.display = 'none';
    }
}

// Fetch categories from API
async function fetchCategories() {
    try {
        const response = await fetch(`${API_URL}/products/categories`);
        categories = await response.json();
        displayCategories(categories);
    } catch (error) {
        console.error('Error fetching categories:', error);
        showNotification('Failed to load categories. Please try again later.', 'error');
    }
}

// Display featured products
function displayFeaturedProducts(productsToDisplay) {
    featuredProductsContainer.innerHTML = '';
    
    productsToDisplay.forEach(product => {
        const discount = Math.floor(Math.random() * 30) + 10; // Random discount between 10-40%
        const originalPrice = (product.price * (1 + discount/100)).toFixed(2);
        
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${product.image}" alt="${product.title}">
            </div>
            <div class="product-info">
                <p class="product-category">${product.category}</p>
                <h3 class="product-title">${product.title}</h3>
                <div class="product-price">
                    <span class="current-price">$${product.price}</span>
                    <span class="original-price">$${originalPrice}</span>
                    <span class="discount">${discount}% OFF</span>
                </div>
                <div class="product-actions">
                    <button class="add-to-cart" data-id="${product.id}">Add to Cart</button>
                    <button class="wishlist"><i class="far fa-heart"></i></button>
                </div>
            </div>
        `;
        
        featuredProductsContainer.appendChild(productCard);
    });
    
    // Add event listeners to all "Add to Cart" buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', (e) => {
            const productId = parseInt(e.target.getAttribute('data-id'));
            addToCart(productId);
        });
    });
}

// Display categories
function displayCategories(categories) {
    categoriesContainer.innerHTML = '';
    
    categories.forEach(category => {
        const categoryCard = document.createElement('div');
        categoryCard.className = 'category-card';
        categoryCard.style.backgroundImage = `linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://source.unsplash.com/random/300x200/?${category}')`;
        categoryCard.innerHTML = `<h3>${category}</h3>`;
        
        categoryCard.addEventListener('click', () => {
            filterProductsByCategory(category);
        });
        
        categoriesContainer.appendChild(categoryCard);
    });
}

// Filter products by category
function filterProductsByCategory(category) {
    const filteredProducts = products.filter(product => product.category === category);
    displayFeaturedProducts(filteredProducts);
    window.scrollTo({
        top: featuredProductsContainer.offsetTop - 100,
        behavior: 'smooth'
    });
}

// Cart functions
function addToCart(productId) {
    if (!currentUser) {
        showNotification('Please login to add items to the cart', 'error');
        showLoginModal();
        return;
    }

    const product = products.find(p => p.id === productId);
    if (!product) return;

    const existingItem = cart.find(item => item.id === productId);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            title: product.title,
            price: product.price,
            image: product.image,
            quantity: 1
        });
    }

    updateCart();
    showNotification(`${product.title} added to cart`, 'success');
}


function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCart();
}

function updateCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    renderCartItems();
}

function updateCartCount() {
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
    cartCountElement.textContent = totalItems;
}

function renderCartItems() {
    if (!cartItemsContainer) return;
    
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
        cartTotalPrice.textContent = '$0.00';
        return;
    }
    
    cartItemsContainer.innerHTML = '';
    let total = 0;
    
    cart.forEach(item => {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
            <div class="cart-item-img">
                <img src="${item.image}" alt="${item.title}">
            </div>
            <div class="cart-item-info">
                <h4 class="cart-item-title">${item.title}</h4>
                <p class="cart-item-price">$${item.price}</p>
                <div class="cart-item-quantity">
                    <button class="quantity-btn minus" data-id="${item.id}">-</button>
                    <span class="quantity-value">${item.quantity}</span>
                    <button class="quantity-btn plus" data-id="${item.id}">+</button>
                    <button class="cart-item-remove" data-id="${item.id}"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `;
        
        cartItemsContainer.appendChild(cartItem);
        total += item.price * item.quantity;
    });
    
    cartTotalPrice.textContent = `$${total.toFixed(2)}`;
    
    // Add event listeners to quantity buttons
    document.querySelectorAll('.quantity-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const productId = parseInt(e.target.getAttribute('data-id'));
            const item = cart.find(item => item.id === productId);
            
            if (e.target.classList.contains('minus')) {
                if (item.quantity > 1) {
                    item.quantity -= 1;
                } else {
                    removeFromCart(productId);
                    return;
                }
            } else if (e.target.classList.contains('plus')) {
                item.quantity += 1;
            }
            
            updateCart();
        });
    });
    
    // Add event listeners to remove buttons
    document.querySelectorAll('.cart-item-remove').forEach(button => {
        button.addEventListener('click', (e) => {
            const target = e.target.closest('.cart-item-remove');
            const productId = parseInt(target.getAttribute('data-id'));
            removeFromCart(productId);
        });
    });
    
}

function toggleCart() {
    cartModal.classList.toggle('active');
    if (cartModal.classList.contains('active')) {
        renderCartItems();
    }
}

// User functions
function updateUserUI() {
    if (currentUser) {
        authButtons.style.display = 'none';
        userIcon.style.display = 'block';
    } else {
        authButtons.style.display = 'flex';
        userIcon.style.display = 'none';
    }
}

function toggleUserMenu() {
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        userMenu.remove();
        return;
    }
    
    const menu = document.createElement('div');
    menu.className = 'user-menu';
    menu.innerHTML = `
        <ul>
            <li><i class="fas fa-user"></i> ${currentUser.username}</li>
            <li><i class="fas fa-shopping-bag"></i> Orders</li>
            <li><i class="fas fa-cog"></i> Settings</li>
            <li id="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    `;
    
    userIcon.appendChild(menu);
    
    document.getElementById('logout-btn').addEventListener('click', logout);
}

function logout() {
    currentUser = null;
    localStorage.removeItem('user');
    updateUserUI();
    showNotification('Logged out successfully', 'success');
    document.querySelector('.user-menu').remove();
}

function showLoginModal() {
    const modal = document.createElement('div');
    modal.className = 'auth-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <button class="close-modal">&times;</button>
            <h2>Login</h2>
            <form id="login-form">
                <div class="form-group">
                    <label for="login-username">Username</label>
                    <input type="text" id="login-username" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" required>
                </div>
                <button type="submit" class="btn-primary">Login</button>
            </form>
            <p class="auth-switch">Don't have an account? <a href="#" id="switch-to-register">Register</a></p>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    document.querySelector('.close-modal').addEventListener('click', () => {
        modal.remove();
    });
    
    document.getElementById('switch-to-register').addEventListener('click', (e) => {
        e.preventDefault();
        modal.remove();
        showRegisterModal();
    });
    document.getElementById('login-form').addEventListener('submit', (e) => {
        e.preventDefault();
    
        const username = document.getElementById('login-username').value.trim();
        const password = document.getElementById('login-password').value;
    
        fetch('http://localhost:8888/sad/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        })
        .then(res => res.json())
        .then(data => {
            if (data.message === "Login successful") {
                currentUser = data.user;
                localStorage.setItem('user', JSON.stringify(currentUser));
                updateUserUI();
                showNotification('Logged in successfully', 'success');
                document.querySelector('.auth-modal').remove();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(err => {
            console.error("Login error:", err);
            showNotification("Login failed. Please try again.", "error");
        });
    });
    
}

function showRegisterModal() {
    const modal = document.createElement('div');
    modal.className = 'auth-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <button class="close-modal">&times;</button>
            <h2>Register</h2>
            <form id="register-form">
                <div class="form-group">
                    <label for="register-username">Username</label>
                    <input type="text" id="register-username" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <input type="email" id="register-email" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" required>
                </div>
                <div class="form-group">
                    <label for="register-confirm-password">Confirm Password</label>
                    <input type="password" id="register-confirm-password" required>
                </div>
                <button type="submit" class="btn-primary">Register</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="#" id="switch-to-login">Login</a></p>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close modal handler
    modal.querySelector('.close-modal').addEventListener('click', () => {
        modal.remove();
    });
    
    // Switch to login handler
    modal.querySelector('#switch-to-login').addEventListener('click', (e) => {
        e.preventDefault();
        modal.remove();
        showLoginModal();
    });
    
    // Form submission handler
    modal.querySelector('#register-form').addEventListener('submit', function(e) {
        e.preventDefault();
    
        const username = document.getElementById('register-username').value.trim();
        const email = document.getElementById('register-email').value.trim().toLowerCase();
        const password = document.getElementById('register-password').value;
        const confirmPassword = document.getElementById('register-confirm-password').value;
    
        if (password !== confirmPassword) {
            showNotification('Passwords do not match', 'error');
            return;
        }
    
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Registering...';
        submitBtn.disabled = true;
    
        fetch('http://localhost:8888/SAD/register.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({username, email, password})
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.message === 'Email already registered') {
                showNotification(data.message, 'error');
            } else if (data.message === 'User registered successfully') {
                showNotification('Registered successfully', 'success');
                modal.remove();
                // Auto-open login form
                setTimeout(() => showLoginModal(), 1000);
            } else {
                showNotification(data.message || 'Registration failed', 'error');
            }
        })
        .catch(error => {
            console.error('Registration error:', error);
            showNotification('Registration failed. Please try again.', 'error');
        })
        .finally(() => {
            // Restore button state
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });
}

// Checkout functions
function handleCheckout() {
    if (cart.length === 0) {
        showNotification('Your cart is empty', 'error');
        return;
    }
    
    if (!currentUser) {
        showNotification('Please login to proceed to checkout', 'error');
        showLoginModal();
        return;
    }
    
    // Ensure checkout modal exists
    if (!checkoutModal) {
        console.error("Checkout modal not found!");
        return;
    }
    
    checkoutModal.style.display = 'flex';
    renderCheckoutForm();
    if (cart.length === 0) {
        showNotification('Your cart is empty', 'error');
        return;
    }
    
    if (!currentUser) {
        showNotification('Please login to proceed to checkout', 'error');
        showLoginModal();
        return;
    }
    
    checkoutModal.style.display = 'flex';
    renderCheckoutForm();
}

function renderCheckoutForm() {
    if (!checkoutForm) return;
    
    checkoutForm.innerHTML = `
        <div class="form-section">
            <h3>Shipping Information</h3>
            <div class="form-group">
                <label for="shipping-name">Full Name</label>
                <input type="text" id="shipping-name" required>
            </div>
            <div class="form-group">
                <label for="shipping-address">Address</label>
                <input type="text" id="shipping-address" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="shipping-city">City</label>
                    <input type="text" id="shipping-city" required>
                </div>
                <div class="form-group">
                    <label for="shipping-zip">Zip Code</label>
                    <input type="text" id="shipping-zip" required>
                </div>
            </div>
            <div class="form-group">
                <label for="shipping-country">Country</label>
                <input type="text" id="shipping-country" required value="Nepal">
            </div>
        </div>
        
        <div class="form-section">
            <h3>Payment Method</h3>
            <div class="payment-methods">
                <div class="payment-method active" data-method="credit">
                    <i class="far fa-credit-card"></i>
                    <span>Credit Card</span>
                </div>
                <div class="payment-method" data-method="esewa">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjQwIiB2aWV3Qm94PSIwIDAgMTAwIDQwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0yMCAxMEMyMCA0LjQ3NyAyNC40NzcgMCAzMCAwSDcwQzc1LjUyMyAwIDgwIDQuNDc3IDgwIDEwVjMwQzgwIDM1LjUyMyA3NS41MjMgNDAgNzAgNDBIMzBDMjQuNDc3IDQwIDIwIDM1LjUyMyAyMCAzMFYxMFoiIGZpbGw9IiB4IzAwNjY4QyIvPjxwYXRoIGQ9Ik0zNSA4VjMySDMxVjE2LjhIMzAuN0wyNi44IDMySDI0TDIwIDE2LjhIMTkuN1YzMkgxNlY4SDIxTDI1LjMgMjUuM0gyNS42TDMwIDhIMzVaIiBmaWxsPSJ3aGl0ZSIvPjxwYXRoIGQ9Ik00Mi4zNCA4SDUwVjExSDQ2VjE2SDQ5LjVWMTlINDZWMzJINDIuMzRWMTlIMzkuN1YxNkg0Mi4zNFY4WiIgZmlsbD0id2hpdGUiLz48cGF0aCBkPSJNNTEuNSA4SDU1LjNWMTZINTUuNEM1Ni4xMzMgMTQuNjY3IDU3LjQgMTQgNTkuMiAxNEM2MC43MzMgMTQgNjEuNzMzIDE0LjQgNjIuMiAxNS4yQzYyLjY2NyAxNiA2Mi45MzMgMTcuMDY3IDYzIDE4LjRWMzJINTguNlYxOC44QzU4LjUzMyAxOC4xMzMgNTguMzMzIDE3LjczMyA1OC4xIDE3LjZDNTcuODY3IDE3LjQ2NyA1Ny40MzMgMTcuNCA1Ni44IDE3LjRDNTUuODY3IDE3LjQgNTUuMiAxNy43MzMgNTQuOCAxOC40QzU0LjQgMTkuMDY3IDU0LjIgMjAgNTQuMiAyMS4yVjMySDUxLjVWOFoiIGZpbGw9IndoaXRlIi8+PHBhdGggZD0iTTY0LjUgOEg2OFYxNkg2Ny44QzY4LjUzMyAxNC42NjcgNjkuOCAxNCA3MS42IDE0QzczLjEzMyAxNCA3NC4xMzMgMTQuNCA3NC42IDE1LjJDNzUuMDY3IDE2IDc1LjMzMyAxNy4wNjcgNzUuNCAxOC40VjMySDcxVjE4LjhDNzAuOTMzIDE4LjEzMyA3MC43MzMgMTcuNzMzIDcwLjUgMTcuNkM3MC4yNjcgMTcuNDY3IDY5LjgzMyAxNy40IDY5LjIgMTcuNEM2OC4yNjcgMTcuNCA2Ny42IDE3LjczMyA2Ny4yIDE4LjRDNjYuOCAxOS4wNjcgNjYuNiAyMCA2Ni42IDIxLjJWMzJINjQuNVY4WiIgZmlsbD0id2hpdGUiLz48L3N2Zz4=" alt="eSewa" style="height: 20px; margin-right: 5px;">
                    <span>eSewa</span>
                </div>
            </div>
            
            <div class="payment-details" id="credit-card-details">
                <div class="form-group">
                    <label for="card-number">Card Number</label>
                    <input type="text" id="card-number" placeholder="1234 5678 9012 3456" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="card-expiry">Expiry Date</label>
                        <input type="text" id="card-expiry" placeholder="MM/YY" required>
                    </div>
                    <div class="form-group">
                        <label for="card-cvv">CVV</label>
                        <input type="text" id="card-cvv" placeholder="123" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="card-name">Name on Card</label>
                    <input type="text" id="card-name" required>
                </div>
            </div>
            
            <div class="payment-details" id="esewa-details" style="display: none;">
                <div class="esewa-info">
                    <p>You will be redirected to eSewa to complete your payment securely.</p>
                    <div class="form-group">
                        <label for="esewa-mobile">eSewa Mobile Number</label>
                        <input type="tel" id="esewa-mobile" placeholder="98XXXXXXXX" required>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn-primary" id="place-order-btn">Place Order</button>
    `;
    
    // Add event listeners to payment methods
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', () => {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
            method.classList.add('active');
            
            const paymentMethod = method.getAttribute('data-method');
            document.querySelectorAll('.payment-details').forEach(details => {
                details.style.display = 'none';
            });
            
            document.getElementById(`${paymentMethod}-details`).style.display = 'block';
        });
    });
     // FIX: Use event delegation for the form submission
    checkoutForm.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log("Checkout form submitted"); // Debug
        completeOrder();
    });
    
    // FIX: Also add click event to the button as a fallback
    const placeOrderBtn = document.getElementById('place-order-btn');
    if (placeOrderBtn) {
        placeOrderBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Place order button clicked"); // Debug
            completeOrder();
        });
    // Handle form submission
    checkoutForm.addEventListener('submit', (e) => {
        e.preventDefault();
        completeOrder();
    });
}
}

function completeOrder() {
    console.log("Complete order function called");
    
    // Get form values
    const shippingName = document.getElementById('shipping-name')?.value.trim() || '';
    const shippingAddress = document.getElementById('shipping-address')?.value.trim() || '';
    const shippingCity = document.getElementById('shipping-city')?.value.trim() || '';
    const shippingZip = document.getElementById('shipping-zip')?.value.trim() || '';
    const shippingCountry = document.getElementById('shipping-country')?.value.trim() || '';
    
    // Debug form values
    console.log("Form values:", {
        shippingName, shippingAddress, shippingCity, shippingZip, shippingCountry
    });
    
    // Validate form
    if (!shippingName || !shippingAddress || !shippingCity || !shippingZip || !shippingCountry) {
        showNotification('Please fill out all shipping fields', 'error');
        return;
    }
    
    // Get selected payment method
    const activePayment = document.querySelector('.payment-method.active');
    if (!activePayment) {
        showNotification('Please select a payment method', 'error');
        return;
    }
    
    const paymentMethod = activePayment.getAttribute('data-method');
    console.log("Selected payment method:", paymentMethod);
    
    // Show loading state
    const submitBtn = document.getElementById('place-order-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;
    
    // Prepare order data
    const orderData = {
        email: currentUser?.email || '',
        items: cart,
        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
        shipping: {
            name: shippingName,
            address: shippingAddress,
            city: shippingCity,
            zip: shippingZip,
            country: shippingCountry
        },
        payment_method: paymentMethod
    };
    
    console.log("Order data:", orderData);
    
    // Process based on payment method
    if (paymentMethod === 'esewa') {
        processEsewaPayment(orderData);
    } else {
        processNormalPayment(orderData);
    }
}

function processNormalPayment(orderData) {
    console.log("Processing normal payment");
    
    fetch('http://localhost:8888/SAD/checkout.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(orderData)
    })
    .then(response => {
        console.log("Response status:", response.status);
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log("Checkout response:", data);
        
        if (data.message === "Order stored successfully") {
            showNotification("Order placed successfully!", "success");
            cart = [];
            updateCart();
            checkoutModal.style.display = "none";
            successModal.style.display = "flex";
            setTimeout(() => {
                successModal.style.display = "none";
            }, 3000);
        } else {
            showNotification(data.message || "Order failed", "error");
        }
    })
    .catch(error => {
        console.error("Checkout error:", error);
        showNotification("Checkout failed. Please try again.", "error");
    })
    .finally(() => {
        // Restore button state
        const submitBtn = document.getElementById('place-order-btn');
        if (submitBtn) {
            submitBtn.textContent = 'Place Order';
            submitBtn.disabled = false;
        }
    });
}

function processNormalPayment(orderData, submitBtn, originalText) {
    fetch('http://localhost:8888/SAD/checkout.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(orderData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log("Checkout response:", data);
        
        if (data.message === "Order stored successfully") {
            showNotification("Order placed successfully!", "success");
            cart = [];
            updateCart();
            checkoutModal.style.display = "none";
            successModal.style.display = "flex";
            setTimeout(() => {
                successModal.style.display = "none";
            }, 3000);
        } else {
            showNotification(data.message || "Order failed", "error");
        }
    })
    .catch(error => {
        console.error("Checkout error:", error);
        showNotification("Checkout failed. Please try again.", "error");
    })
    .finally(() => {
        // Restore button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function processEsewaPayment(orderData) {
    // First, create the order in your database
    fetch('http://localhost:8888/SAD/checkout.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            ...orderData,
            payment_status: 'pending',
            payment_method: 'esewa'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.order_id) {
            // Redirect to eSewa payment page
            const esewaForm = document.createElement('form');
            esewaForm.method = 'POST';
            esewaForm.action = 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';
            esewaForm.target = '_blank';
            
            const paymentData = {
                amount: orderData.total,
                tax_amount: 0,
                total_amount: orderData.total,
                transaction_uuid: 'ORDER_' + data.order_id,
                product_code: 'EPAYTEST', // Use actual code in production
                product_service_charge: 0,
                product_delivery_charge: 0,
                success_url: 'http://localhost:8888/SAD/esewa_success.php',
                failure_url: 'http://localhost:8888/SAD/esewa_failure.php',
                signed_field_names: 'total_amount,transaction_uuid,product_code',
                signature: '' // You'll generate this on the server side
            };
            
            for (const key in paymentData) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = paymentData[key];
                esewaForm.appendChild(input);
            }
            
            document.body.appendChild(esewaForm);
            esewaForm.submit();
            
            // Listen for payment completion message
            window.addEventListener('message', function(event) {
                if (event.data.type === 'esewa_payment_success') {
                    showNotification("Payment successful! Order completed.", "success");
                    cart = [];
                    updateCart();
                    checkoutModal.style.display = "none";
                } else if (event.data.type === 'esewa_payment_failed') {
                    showNotification("Payment failed. Please try again.", "error");
                }
            });
            
        } else {
            showNotification("Failed to create order: " + data.message, "error");
        }
    })
    .catch(error => {
        console.error("Order creation error:", error);
        showNotification("Failed to process order.", "error");
    })
    .finally(() => {
        // Restore button state
        const submitBtn = document.getElementById('place-order-btn');
        submitBtn.textContent = 'Place Order';
        submitBtn.disabled = false;
    });
}
function simulateEsewaCallback(orderData) {
    // This simulates eSewa returning a successful payment response
    const transactionId = 'ESEWA' + Date.now();
    
    // Add transaction info to order data
    orderData.transaction_id = transactionId;
    orderData.payment_status = 'completed';
    
    // Now process the order with payment confirmation
    fetch('http://localhost:8888/SAD/checkout.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.message === "Order stored successfully") {
            showNotification("Payment successful! Order placed.", "success");
            cart = [];
            updateCart();
            checkoutModal.style.display = "none";
            successModal.style.display = "flex";
            setTimeout(() => {
                successModal.style.display = "none";
            }, 3000);
        } else {
            showNotification("Payment completed but order failed: " + data.message, "error");
        }
    })
    .catch(error => {
        console.error("Order processing error:", error);
        showNotification("Payment completed but order processing failed.", "error");
    })
    .finally(() => {
        // Restore button state
        const submitBtn = document.getElementById('place-order-btn');
        submitBtn.textContent = 'Place Order';
        submitBtn.disabled = false;
    });
}

function handleSearch() {
    const searchTerm = searchInput.value.trim().toLowerCase();
    if (!searchTerm) return;
    
    const filteredProducts = products.filter(product => 
        product.title.toLowerCase().includes(searchTerm) || 
        product.category.toLowerCase().includes(searchTerm)
    );
    
    if (filteredProducts.length === 0) {
        showNotification('No products found matching your search', 'warning');
        return;
    }
    
    displayFeaturedProducts(filteredProducts);
}

// Notification function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', initApp);