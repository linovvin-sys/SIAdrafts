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
    <div class="container">
      <nav class="navbar navbar-expand bg-white border-bottom fixed-top">
        <div class="container-fluid">
          <span class="navbar-brand mb-0 h1">🍽️ Kiosk Menu</span>
          <div class="navbar-nav flex-row gap-3">
            <router-link to="/" class="nav-link">Kiosk Menu</router-link>
            <router-link to="/cart" class="nav-link">Shopping Cart</router-link>
          </div>
        </div>
      </nav>

      <div class="d-flex align-items-center gap-2 flex-wrap my-3">
        <button class="btn btn-outline-dark" @click="category = 'All'" :class="{ active: category === 'All' }">All</button>
        <button class="btn btn-outline-dark" @click="category = 'Meals'" :class="{ active: category === 'Meals' }">Meals</button>
        <button class="btn btn-outline-dark" @click="category = 'Drinks'" :class="{ active: category === 'Drinks' }">Drinks</button>
        <button class="btn btn-outline-dark" @click="category = 'Snacks'" :class="{ active: category === 'Snacks' }">Snacks</button>
        <button class="btn btn-outline-dark" @click="category = 'Desserts'" :class="{ active: category === 'Desserts' }">Desserts</button>
        <router-link to="/cart" class="btn btn-dark ms-auto position-relative">
          Cart
          <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle" v-if="cartCount() > 0">{{ cartCount() }}</span>
        </router-link>
      </div>

      <input v-model="searchText" class="form-control mb-3" placeholder="Search items...">

      <div class="alert alert-success" v-if="toastMessage">{{ toastMessage }}</div>

      <div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
        <template v-for="item in searchedItems()" :key="item.id">
          <div class="col" v-if="matchesCategory(item)">
            <div class="card text-center h-100 p-3">
              <div class="menu-icon">{{ item.icon }}</div>
              <div class="fw-bold mt-2">{{ item.name }}</div>
              <div>₱{{ item.price }}</div>
              <div class="text-muted small mb-2">Stock: {{ remainingStock(item) }}</div>
              <button class="btn btn-dark" @click="addToCart(item)" :disabled="remainingStock(item) <= 0">
                {{ remainingStock(item) <= 0 ? 'Out of Stock' : 'Add to Cart' }}
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  `,
};
