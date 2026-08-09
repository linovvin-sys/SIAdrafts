var { ref } = Vue;

var CartView = {
  setup() {
    var customerName = ref('');
    var paymentMethod = ref('Cash');

    function totals() {
      var subtotal = CartController.getSubtotal(cartItems);
      var vat = CartController.getVat(subtotal);
      return { subtotal: subtotal, vat: vat, total: CartController.getTotal(subtotal, vat) };
    }

    function confirmPayment() {
      var orderId = CartController.checkout(cartItems, customerName.value, paymentMethod.value);
      window.location.hash = '#/receipt/' + orderId;
    }

    return { cartItems, menuItems, customerName, paymentMethod, CartController, totals, confirmPayment };
  },
  template: `
    <div class="container">
      <nav class="navbar navbar-expand bg-white border-bottom fixed-top">
        <div class="container-fluid">
          <span class="navbar-brand mb-0 h1">🛒 Shopping Cart</span>
          <div class="navbar-nav flex-row gap-3">
            <router-link to="/" class="nav-link">Kiosk Menu</router-link>
            <router-link to="/cart" class="nav-link">Shopping Cart</router-link>
          </div>
        </div>
      </nav>

      <p class="mt-3" v-if="cartItems.length === 0">Your cart is currently empty.</p>

      <div class="row mt-3" v-else>
        <div class="col-md-8">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in cartItems" :key="item.id">
                <td>{{ item.icon }} {{ item.name }}</td>
                <td>₱{{ item.price }}</td>
                <td>
                  <button class="btn btn-sm btn-outline-secondary" @click="CartController.decreaseQuantity(item)">-</button>
                  {{ item.quantity }}
                  <button class="btn btn-sm btn-outline-secondary" @click="CartController.increaseQuantity(item, menuItems)">+</button>
                </td>
                <td>₱{{ item.price * item.quantity }}</td>
                <td><button class="btn btn-sm btn-outline-danger" @click="CartController.removeItem(cartItems, item)">Remove</button></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="col-md-4">
          <div class="card p-3">
            <h2 class="h5">Order Summary</h2>
            <input v-model="customerName" class="form-control mb-2" placeholder="Customer Name">
            <select v-model="paymentMethod" class="form-select mb-2">
              <option value="Cash">Cash</option>
              <option value="GCash">GCash</option>
              <option value="Card">Card</option>
            </select>
            <p class="mb-1">Subtotal: ₱{{ totals().subtotal.toFixed(2) }}</p>
            <p class="mb-1">VAT (12%): ₱{{ totals().vat.toFixed(2) }}</p>
            <p><strong>Total: ₱{{ totals().total.toFixed(2) }}</strong></p>
            <button class="btn btn-dark w-100" @click="confirmPayment" :disabled="customerName.trim() === ''">Confirm Payment</button>
          </div>
        </div>
      </div>
    </div>
  `,
};
