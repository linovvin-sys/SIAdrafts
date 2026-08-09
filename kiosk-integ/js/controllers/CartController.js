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

var CartController = {
  increaseQuantity(item, menuItems) {
    var menuItem = menuItems.find(function (m) {
      return m.id === item.id;
    });

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
    cart.splice(cart.indexOf(item), 1);
  },

  getTotals(cart) {
    var subtotal = cart.reduce(function (sum, item) {
      return sum + item.price * item.quantity;
    }, 0);
    var vat = subtotal * 0.12;

    return { subtotal: subtotal, vat: vat, total: subtotal + vat };
  },

  checkout(cart, customerName, paymentMethod) {
    var t = CartController.getTotals(cart);

    Object.assign(lastOrder, {
      orderId: 'ORID-' + Math.floor(100000 + Math.random() * 900000),
      customerName: customerName,
      paymentMethod: paymentMethod,
      items: cart.map(function (item) {
        return { name: item.name, price: item.price, quantity: item.quantity };
      }),
      subtotal: t.subtotal,
      vat: t.vat,
      total: t.total,
    });

    cart.splice(0, cart.length);

    return lastOrder.orderId;
  },
};
