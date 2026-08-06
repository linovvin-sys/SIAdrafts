// Plain variables and functions inside setup().
var { ref } = Vue;

var CartView = {
  setup() {
    var customerName = ref('');
    var paymentMethod = ref('Cash');

    function increaseQuantity(item) {
      CartController.increaseQuantity(item, menuItems);
    }

    function decreaseQuantity(item) {
      CartController.decreaseQuantity(item);
    }

    function removeItem(item) {
      CartController.removeItem(cartItems, item);
    }

    function subtotal() {
      return CartController.getSubtotal(cartItems);
    }

    function vat() {
      return CartController.getVat(subtotal());
    }

    function total() {
      return CartController.getTotal(subtotal(), vat());
    }

    function confirmPayment() {
      var orderId = CartController.checkout(cartItems, customerName.value, paymentMethod.value);
      window.location.hash = '#/receipt/' + orderId;
    }

    return { cartItems, customerName, paymentMethod, increaseQuantity, decreaseQuantity, removeItem, subtotal, vat, total, confirmPayment };
  },
  template: `
    <div class="page">
      <div class="topbar">
        <h1>🛒 Shopping Cart</h1>
        <router-link to="/">&larr; Back to Menu</router-link>
      </div>

      <p v-if="cartItems.length === 0">Your cart is currently empty.</p>

      <div class="cart-layout" v-else>
        <table>
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
                <button class="secondary" @click="decreaseQuantity(item)">-</button>
                {{ item.quantity }}
                <button class="secondary" @click="increaseQuantity(item)">+</button>
              </td>
              <td>₱{{ item.price * item.quantity }}</td>
              <td><button class="secondary" @click="removeItem(item)">Remove</button></td>
            </tr>
          </tbody>
        </table>

        <div class="summary">
          <h2>Order Summary</h2>
          <input v-model="customerName" placeholder="Customer Name" />
          <select v-model="paymentMethod">
            <option value="Cash">Cash</option>
            <option value="GCash">GCash</option>
            <option value="Card">Card</option>
          </select>
          <p>Subtotal: ₱{{ subtotal().toFixed(2) }}</p>
          <p>VAT (12%): ₱{{ vat().toFixed(2) }}</p>
          <p><strong>Total: ₱{{ total().toFixed(2) }}</strong></p>
          <button @click="confirmPayment" :disabled="customerName.trim() === ''">Confirm Payment</button>
        </div>
      </div>
    </div>
  `,
};
