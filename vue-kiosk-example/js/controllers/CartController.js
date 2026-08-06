var { reactive } = Vue;

var cartItems = reactive([]);

var lastOrder = reactive({
  orderId: '',
  customerName: '',
  paymentMethod: '',
  items: [],
  subtotal: 0,
  vat: 0,
  total: 0,
});

// controller
var CartController = {
  increaseQuantity(item, menuItems) {
    var menuItem = menuItems.find(function (m) { return m.id === item.id; });
    if (item.quantity < menuItem.stock) {
      item.quantity++;
    }
  },

  decreaseQuantity(item) {
    if (item.quantity > 1) {
      item.quantity--;
    }
  },

  removeItem(cart, item) {
    var index = cart.indexOf(item);
    cart.splice(index, 1);
  },

  getSubtotal(cart) {
    return cart.reduce(function (sum, item) { return sum + item.price * item.quantity; }, 0);
  },

  getVat(subtotal) {
    return subtotal * 0.12;
  },

  getTotal(subtotal, vat) {
    return subtotal + vat;
  },

  generateOrderId() {
    var digits = Math.floor(100000 + Math.random() * 900000);
    return 'ORID-' + digits;
  },

  checkout(cart, customerName, paymentMethod) {
    var subtotal = CartController.getSubtotal(cart);
    var vat = CartController.getVat(subtotal);

    lastOrder.orderId = CartController.generateOrderId();
    lastOrder.customerName = customerName;
    lastOrder.paymentMethod = paymentMethod;
    lastOrder.items = cart.map(function (item) {
      return { name: item.name, price: item.price, quantity: item.quantity };
    });
    lastOrder.subtotal = subtotal;
    lastOrder.vat = vat;
    lastOrder.total = CartController.getTotal(subtotal, vat);

    cart.splice(0, cart.length);

    return lastOrder.orderId;
  },
};
