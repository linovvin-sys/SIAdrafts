// Plain variables and functions inside setup().
var { ref } = Vue;

var KioskView = {
  setup() {
    var category = ref('All');
    var searchText = ref('');
    var toastMessage = ref('');

    function searchedItems() {
      return MenuController.searchItems(menuItems, searchText.value);
    }

    function matchesCategory(item) {
      return MenuController.matchesCategory(item, category.value);
    }

    function remainingStock(item) {
      return MenuController.remainingStock(cartItems, item);
    }

    function cartCount() {
      return MenuController.cartCount(cartItems);
    }

    function addToCart(item) {
      var added = MenuController.addToCart(cartItems, item);
      if (added) {
        toastMessage.value = item.name + ' has been added to cart!';
        setTimeout(function () { toastMessage.value = ''; }, 2000);
      }
    }

    return { category, searchText, toastMessage, searchedItems, matchesCategory, remainingStock, cartCount, addToCart };
  },
  template: `
    <div class="page">
      <div class="topbar">
        <h1>🍽️ Kiosk Menu</h1>
        <router-link to="/cart" class="cart-btn">
          Cart
          <span class="badge" v-if="cartCount() > 0">{{ cartCount() }}</span>
        </router-link>
      </div>

      <div class="filter-bar">
        <button @click="category = 'All'" :class="{ active: category === 'All' }">All</button>
        <button @click="category = 'Meals'" :class="{ active: category === 'Meals' }">Meals</button>
        <button @click="category = 'Drinks'" :class="{ active: category === 'Drinks' }">Drinks</button>
        <button @click="category = 'Snacks'" :class="{ active: category === 'Snacks' }">Snacks</button>
        <button @click="category = 'Desserts'" :class="{ active: category === 'Desserts' }">Desserts</button>
      </div>

      <input v-model="searchText" class="search-box" placeholder="Search items..." />

      <p class="toast" v-if="toastMessage">{{ toastMessage }}</p>

      <div class="menu-grid">
        <template v-for="item in searchedItems()" :key="item.id">
          <div class="menu-card" v-if="matchesCategory(item)">
            <div class="menu-icon">{{ item.icon }}</div>
            <div class="menu-name">{{ item.name }}</div>
            <div class="menu-price">₱{{ item.price }}</div>
            <div class="menu-stock">Stock: {{ remainingStock(item) }}</div>
            <button @click="addToCart(item)" :disabled="remainingStock(item) <= 0">
              {{ remainingStock(item) <= 0 ? 'Out of Stock' : 'Add to Cart' }}
            </button>
          </div>
        </template>
      </div>
    </div>
  `,
};
