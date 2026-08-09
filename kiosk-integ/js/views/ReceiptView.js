var ReceiptView = {
  props: ['orderId'],
  setup(props) {
    return { lastOrder };
  },
  template: `
    <div class="container">
      <h1>🧾 Receipt</h1>
      <p>Order ID: <strong>{{ orderId }}</strong></p>
      <p>Customer: {{ lastOrder.customerName }}</p>
      <p>Payment Method: {{ lastOrder.paymentMethod }}</p>

      <table class="table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in lastOrder.items" :key="item.name">
            <td>{{ item.name }}</td>
            <td>{{ item.quantity }}</td>
            <td>₱{{ item.price }}</td>
            <td>₱{{ item.price * item.quantity }}</td>
          </tr>
        </tbody>
      </table>

      <p>Subtotal: ₱{{ lastOrder.subtotal.toFixed(2) }}</p>
      <p>VAT (12%): ₱{{ lastOrder.vat.toFixed(2) }}</p>
      <p><strong>Total Paid: ₱{{ lastOrder.total.toFixed(2) }}</strong></p>

      <router-link to="/" class="btn btn-dark mt-3">Order More Food</router-link>
    </div>
  `,
};
