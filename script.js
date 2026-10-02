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

let esewaQrIntervalId = null;
let esewaQrTimeoutId = null;

function clearEsewaQrTimers() {
    if (esewaQrIntervalId != null) {
        clearInterval(esewaQrIntervalId);
        esewaQrIntervalId = null;
    }
    if (esewaQrTimeoutId != null) {
        clearTimeout(esewaQrTimeoutId);
        esewaQrTimeoutId = null;
    }
}

function hideEsewaQrModal() {
    const modal = document.getElementById('esewa-qr-modal');
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
}

function resetPlaceOrderButton() {
    const btn = document.getElementById('place-order-btn');
    if (btn) {
        btn.textContent = 'Place Order';
        btn.disabled = false;
    }
}

function isCheckoutSuccess(data) {
    if (!data || typeof data !== 'object') return false;
    if (data.success === false) return false;
    if (data.success === true) return true;
    return Number(data.order_id) > 0;
}

function handleCheckoutSuccess() {
    hideEsewaQrModal();
    clearEsewaQrTimers();
    showNotification('Order placed successfully!', 'success');
    cart = [];
    updateCart();
    if (checkoutModal) checkoutModal.style.display = 'none';
    if (successModal) {
        successModal.style.display = 'flex';
        setTimeout(() => {
            successModal.style.display = 'none';
        }, 3000);
    }
}

function getCheckoutUserEmail() {
    const fromUser = currentUser && (currentUser.email || currentUser.user_email);
    if (fromUser) {
        return String(fromUser).trim();
    }
    try {
        const raw = localStorage.getItem('user');
        const u = raw ? JSON.parse(raw) : null;
        if (u && (u.email || u.user_email)) {
            return String(u.email || u.user_email).trim();
        }
    } catch (_) {}
    return '';
}

function buildCheckoutRequestBody(payload) {
    const safe = payload && typeof payload === 'object' ? payload : {};
    const email =
        getCheckoutUserEmail() ||
        String((safe.email || safe.user_email) || '').trim();
    const { email: _omitEmail, user_email: _omitUserEmail, ...rest } = safe;
    return { ...rest, email };
}

function doCheckoutFetch(payload) {
    const body = buildCheckoutRequestBody(payload);
    return fetch(`${API_URL}checkout.php`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify(body),
    }).then(async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            console.error('Checkout HTTP error', response.status, data);
            throw new Error(data.error || data.message || 'Order failed');
        }
        if (data.success === false) {
            throw new Error(data.error || data.message || 'Order failed');
        }
        return data;
    });
}

/**
 * Submits checkout. eSewa QR + 15s delay run only here when payment_method === 'esewa'.
 */
function placeOrder(payload) {
    const normalized = buildCheckoutRequestBody(payload);
    const method = String(normalized.payment_method || '').toLowerCase();

    if (method !== 'esewa') {
        return doCheckoutFetch(normalized);
    }

    return new Promise((resolve, reject) => {
        clearEsewaQrTimers();
        const modal = document.getElementById('esewa-qr-modal');
        if (modal) {
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
        }
        const countEl = document.getElementById('esewa-qr-countdown');
        let remaining = 15;
        const renderTick = () => {
            if (countEl) {
                countEl.innerHTML = `${remaining}<span>second${remaining === 1 ? '' : 's'} remaining</span>`;
            }
        };
        renderTick();
        esewaQrIntervalId = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                if (esewaQrIntervalId != null) {
                    clearInterval(esewaQrIntervalId);
                    esewaQrIntervalId = null;
                }
            } else {
                renderTick();
            }
        }, 1000);

        esewaQrTimeoutId = setTimeout(() => {
            esewaQrTimeoutId = null;
            if (esewaQrIntervalId != null) {
                clearInterval(esewaQrIntervalId);
                esewaQrIntervalId = null;
            }
            hideEsewaQrModal();
            requestAnimationFrame(() => {
                doCheckoutFetch(normalized).then(resolve).catch(reject);
            });
        }, 15000);
    });
}

// Project root (served via local PHP / static host) — used for local product API
const API_URL = './';

// Store state
let products = [];
let categories = [];
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let currentUser = JSON.parse(localStorage.getItem('user')) || null;

const FAVORITES_STORAGE_KEY = 'shopeasy_favorites';

function formatNpr(amount) {
    const n = Math.round(Number(amount) * 100) / 100;
    if (Number.isNaN(n)) return 'रू 0.00';
    return 'रू ' + n.toFixed(2);
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function getUnitPrice(product) {
    if (!product) return 0;
    const list = Number(product.price);
    const dp = product.discount_price != null && product.discount_price !== ''
        ? Number(product.discount_price)
        : NaN;
    if (!Number.isNaN(dp) && dp > 0 && dp < list) {
        return Math.round(dp * 100) / 100;
    }
    return Math.round(list * 100) / 100;
}

function loadFavoriteIds() {
    try {
        const raw = localStorage.getItem(FAVORITES_STORAGE_KEY);
        const arr = raw ? JSON.parse(raw) : [];
        return Array.isArray(arr) ? arr.map((id) => Number(id)) : [];
    } catch {
        return [];
    }
}

function saveFavoriteIds(ids) {
    const unique = [...new Set(ids.map(Number))];
    localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify(unique));
}

function isFavorite(productId) {
    return loadFavoriteIds().includes(Number(productId));
}

function toggleFavoriteProduct(productId) {
    const id = Number(productId);
    let ids = loadFavoriteIds();
    if (ids.includes(id)) {
        ids = ids.filter((x) => x !== id);
    } else {
        ids.push(id);
    }
    saveFavoriteIds(ids);
    return ids.includes(id);
}

const favoritesProductsEl = document.getElementById('favorites-products');

// Initialize the application
function initApp() {
    updateCartCount();
    fetchProducts();
    setupEventListeners();
    updateUserUI();
    renderFavoritesPanel();
    
    // Theme toggle
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            const icon = themeToggle.querySelector('i');
            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
        themeToggle.querySelector('i').className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    try {
        const params = new URLSearchParams(window.location.search);
        if (params.get('loginRequired') === '1') {
            showLoginModal();
        }
    } catch (_) {}
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

function renderFavoritesPanel() {
    if (!favoritesProductsEl) return;
    const ids = loadFavoriteIds();
    const favProducts = ids.map((id) => products.find((p) => p.id === id)).filter(Boolean);
    if (favProducts.length === 0) {
        favoritesProductsEl.innerHTML = '<p class="favorites-empty">No favorites yet. Tap the heart on a product to save it here.</p>';
        return;
    }
    favoritesProductsEl.innerHTML = '';
    favProducts.forEach((product) => {
        const list = Number(product.price);
        const unit = getUnitPrice(product);
        const hasSale = unit < list;
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <div class="product-image">
                <img src="${product.image_url}" alt="${escapeHtml(product.title)}">
            </div>
            <div class="product-info">
                <p class="product-category">${escapeHtml(product.category)}</p>
                <h3 class="product-title">${escapeHtml(product.name)}</h3>
                <div class="product-price">
                    ${hasSale
                        ? `<span class="current-price">${formatNpr(unit)}</span><span class="original-price">${formatNpr(list)}</span>`
                        : `<span class="current-price">${formatNpr(list)}</span>`}
                </div>
                <div class="product-actions">
                    <button class="add-to-cart" data-id="${product.id}">Add to Cart</button>
                    <button class="wishlist is-fav" data-id="${product.id}"><i class="fas fa-heart"></i></button>
                </div>
            </div>
        `;
        card.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart') || e.target.closest('.wishlist')) return;
            window.location.href = `product_details.php?id=${encodeURIComponent(product.id)}`;
        });
        favoritesProductsEl.appendChild(card);
    });
    favoritesProductsEl.querySelectorAll('.add-to-cart').forEach((button) => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            addToCart(parseInt(button.getAttribute('data-id'), 10));
        });
    });
    favoritesProductsEl.querySelectorAll('.wishlist').forEach((button) => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = parseInt(button.getAttribute('data-id'), 10);
            toggleFavoriteProduct(id);
            renderFavoritesPanel();
            syncWishlistIcons();
        });
    });
}

function syncWishlistIcons() {
    document.querySelectorAll('.wishlist[data-id]').forEach((btn) => {
        const id = parseInt(btn.getAttribute('data-id'), 10);
        const on = isFavorite(id);
        btn.classList.toggle('is-fav', on);
        const icon = btn.querySelector('i');
        if (icon) icon.className = on ? 'fas fa-heart' : 'far fa-heart';
    });
}

// Fetch products from local PHP (MySQL via admin dashboard)
async function fetchProducts() {
    try {
        loader.style.display = 'block';
        const response = await fetch(`${API_URL}get_products.php`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const data = await response.json();
        console.log('Products from get_products.php:', data);

        if (!Array.isArray(data)) {
            throw new Error(data && data.error ? data.error : 'Invalid response: expected a JSON array of products');
        }

        products = data;
        displayFeaturedProducts(products.slice(0, 8)); // Display first 8 products as featured
        fetchCategories();
        renderFavoritesPanel();
        syncWishlistIcons();
    } catch (error) {
        console.error('Error fetching products:', error);
        showNotification('Failed to load products. Please try again later.', 'error');
    } finally {
        loader.style.display = 'none';
    }
}

// Build category list from loaded products (same strings as product.category for filtering)
function fetchCategories() {
    try {
        const seen = new Set();
        categories = [];
        for (const product of products) {
            const name = product.category;
            if (name && !seen.has(name)) {
                seen.add(name);
                categories.push(name);
            }
        }
        displayCategories(categories);
    } catch (error) {
        console.error('Error building categories:', error);
        showNotification('Failed to load categories. Please try again later.', 'error');
    }
}

// Display featured products
function displayFeaturedProducts(productsToDisplay) {
    featuredProductsContainer.innerHTML = '';

    productsToDisplay.forEach((product) => {
        const list = Number(product.price);
        const unit = getUnitPrice(product);
        const hasSale = unit < list;
        const pctOff = hasSale && list > 0 ? Math.round((1 - unit / list) * 100) : 0;

        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${product.image_url}" alt="${escapeHtml(product.title)}">
            </div>
            <div class="product-info">
                <p class="product-category">${escapeHtml(product.category)}</p>
                <h3 class="product-title">${escapeHtml(product.name)}</h3>
                <div class="product-price">
                    ${hasSale
                        ? `<span class="current-price">${formatNpr(unit)}</span><span class="original-price">${formatNpr(list)}</span><span class="discount">${pctOff}% OFF</span>`
                        : `<span class="current-price">${formatNpr(list)}</span>`}
                </div>
                <div class="product-actions">
                    <button type="button" class="add-to-cart" data-id="${product.id}">Add to Cart</button>
                    <button type="button" class="wishlist ${isFavorite(product.id) ? 'is-fav' : ''}" data-id="${product.id}" aria-label="Favorite"><i class="${isFavorite(product.id) ? 'fas' : 'far'} fa-heart"></i></button>
                </div>
            </div>
        `;

        productCard.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart') || e.target.closest('.wishlist')) return;
            window.location.href = `product_details.php?id=${encodeURIComponent(product.id)}`;
        });

        featuredProductsContainer.appendChild(productCard);
    });

    document.querySelectorAll('#featured-products .add-to-cart').forEach((button) => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            const productId = parseInt(button.getAttribute('data-id'), 10);
            addToCart(productId);
        });
    });

    document.querySelectorAll('#featured-products .wishlist').forEach((button) => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            const productId = parseInt(button.getAttribute('data-id'), 10);
            const on = toggleFavoriteProduct(productId);
            button.classList.toggle('is-fav', on);
            const icon = button.querySelector('i');
            if (icon) icon.className = on ? 'fas fa-heart' : 'far fa-heart';
            renderFavoritesPanel();
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
            name: product.name,
            price: getUnitPrice(product),
            image: product.image_url,
            quantity: 1
        });
    }

    updateCart();
    showNotification(`${product.name} added to cart`, 'success');
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
        cartTotalPrice.textContent = formatNpr(0);
        return;
    }
    
    cartItemsContainer.innerHTML = '';
    let total = 0;
    
    cart.forEach(item => {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
            <div class="cart-item-img">
                <img src="${item.image_url}" alt="${item.name}">
            </div>
            <div class="cart-item-info">
                <h4 class="cart-item-title">${item.name}</h4>
                <p class="cart-item-price">${formatNpr(item.price)}</p>
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
    
    cartTotalPrice.textContent = formatNpr(total);
    
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
    document.querySelectorAll('header .nav-protected').forEach((el) => {
        el.style.display = currentUser ? 'list-item' : 'none';
    });
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
            <li><i class="fas fa-user"></i> ${escapeHtml(currentUser.username || currentUser.email || 'User')}</li>
            <li id="favorites-menu-btn" style="cursor:pointer;"><i class="fas fa-heart"></i> Favorites</li>
            <li><a href="my_orders.php" style="color:inherit;text-decoration:none;"><i class="fas fa-shopping-bag"></i> Orders</a></li>
            <li><a href="settings.php" style="color:inherit;text-decoration:none;"><i class="fas fa-cog"></i> Settings</a></li>
            <li id="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    `;
    
    userIcon.appendChild(menu);
    
    document.getElementById('favorites-menu-btn').addEventListener('click', () => {
        document.getElementById('favorites')?.scrollIntoView({ behavior: 'smooth' });
        menu.remove();
    });
    
    document.getElementById('logout-btn').addEventListener('click', logout);
}

function logout() {
    fetch(`${API_URL}logout_session.php`, { method: 'POST', credentials: 'include' })
        .catch(() => {})
        .finally(() => {
            currentUser = null;
            localStorage.removeItem('user');
            updateUserUI();
            showNotification('Logged out successfully', 'success');
            document.querySelector('.user-menu')?.remove();
        });
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
    
        fetch(`${API_URL}login.php`, {
            method: 'POST',
            credentials: 'include',
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
    
        fetch(`${API_URL}register.php`, {
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

    if (!checkoutModal) {
        console.error('Checkout modal not found!');
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
                <label for="shipping-email">Email Address</label>
                <input type="email" id="shipping-email" name="shipping_email" required value="${getCheckoutUserEmail() || currentUser?.email || ''}">
            </div>
            <div class="form-group">
                <label for="shipping-name">Full Name</label>
                <input type="text" id="shipping-name" name="shipping_name" required autocomplete="name">
            </div>
            <div class="form-group">
                <label for="shipping-address">Address</label>
                <input type="text" id="shipping-address" name="shipping_address" required autocomplete="street-address">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="shipping-city">City</label>
                    <input type="text" id="shipping-city" name="shipping_city" required>
                </div>
                <div class="form-group">
                    <label for="shipping-zip">Zip Code</label>
                    <input type="text" id="shipping-zip" name="shipping_zip" required>
                </div>
            </div>
            <div class="form-group">
                <label for="shipping-country">Country</label>
                <input type="text" id="shipping-country" name="shipping_country" required value="Nepal">
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
                    <input type="text" id="card-number" placeholder="1234 5678 9012 3456 (simulation — optional)">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="card-expiry">Expiry Date</label>
                        <input type="text" id="card-expiry" placeholder="MM/YY (optional)">
                    </div>
                    <div class="form-group">
                        <label for="card-cvv">CVV</label>
                        <input type="text" id="card-cvv" placeholder="123 (optional)">
                    </div>
                </div>
                <div class="form-group">
                    <label for="card-name">Name on Card</label>
                    <input type="text" id="card-name" placeholder="Optional for demo">
                </div>
            </div>

            <div class="payment-details" id="esewa-details" style="display: none;">
                <div class="esewa-info">
                    <p>You will be redirected to eSewa to complete your payment securely.</p>
                    <div class="form-group">
                        <label for="esewa-mobile">eSewa Mobile Number</label>
                        <input type="tel" id="esewa-mobile" placeholder="98XXXXXXXX (optional for demo)">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary" id="place-order-btn">Place Order</button>
    `;

    document.querySelectorAll('.payment-method').forEach((method) => {
        method.addEventListener('click', () => {
            document.querySelectorAll('.payment-method').forEach((m) => m.classList.remove('active'));
            method.classList.add('active');
            const paymentMethod = method.getAttribute('data-method');
            document.querySelectorAll('.payment-details').forEach((details) => {
                details.style.display = 'none';
            });
            const panel = document.getElementById(`${paymentMethod}-details`);
            if (panel) panel.style.display = 'block';
        });
    });

    checkoutForm.onsubmit = (e) => {
        e.preventDefault();
        completeOrder();
    };
}

function completeOrder() {
    console.log("Complete order function called");
    
    // Get form values
    const shippingEmail = document.getElementById('shipping-email')?.value.trim() || '';
    const shippingName = document.getElementById('shipping-name')?.value.trim() || '';
    const shippingAddress = document.getElementById('shipping-address')?.value.trim() || '';
    const shippingCity = document.getElementById('shipping-city')?.value.trim() || '';
    const shippingZip = document.getElementById('shipping-zip')?.value.trim() || '';
    const shippingCountry = document.getElementById('shipping-country')?.value.trim() || '';
    
    // Debug form values
    console.log("Form values:", {
        shippingEmail, shippingName, shippingAddress, shippingCity, shippingZip, shippingCountry
    });
    
    // Validate form
    if (!shippingEmail || !shippingName || !shippingAddress || !shippingCity || !shippingZip || !shippingCountry) {
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
    
    const submitBtn = document.getElementById('place-order-btn');
    if (!submitBtn) return;
    
    // Prepare order data
    const checkoutEmail = shippingEmail;
    const orderData = {
        email: checkoutEmail,
        items: cart,
        total: Math.round(cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) * 100) / 100,
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
    
    if (paymentMethod === 'esewa') {
        submitBtn.disabled = true;
        submitBtn.textContent = 'eSewa — scan QR';
        placeOrder({
            ...orderData,
            payment_method: 'esewa',
            payment_status: 'pending',
        })
            .then((data) => {
                if (isCheckoutSuccess(data)) {
                    handleCheckoutSuccess();
                } else {
                    showNotification(data.message || 'Order failed', 'error');
                }
            })
            .catch((error) => {
                console.error('Order creation error:', error);
                showNotification(error.message || 'Failed to process order.', 'error');
            })
            .finally(() => {
                resetPlaceOrderButton();
            });
        return;
    }
    
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;
    processNormalPayment(orderData);
}

function processNormalPayment(orderData) {
    console.log('Processing normal payment');

    placeOrder({
        ...orderData,
        payment_method: 'credit',
        payment_status: 'Completed',
    })
        .then((data) => {
            console.log('Checkout response:', data);
            if (isCheckoutSuccess(data)) {
                handleCheckoutSuccess();
            } else {
                showNotification(data.message || 'Order failed', 'error');
            }
        })
        .catch((error) => {
            console.error('Checkout error:', error);
            showNotification(error.message || 'Checkout failed. Please try again.', 'error');
        })
        .finally(() => {
            resetPlaceOrderButton();
        });
}

function handleSearch() {
    const searchTerm = searchInput.value.trim().toLowerCase();
    if (!searchTerm) return;
    
    const filteredProducts = products.filter(product => 
        product.name.toLowerCase().includes(searchTerm) || 
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