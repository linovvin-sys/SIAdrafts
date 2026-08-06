// ================= MODEL =================
// Plain static data — a normal JS array, no server involved.
var menuItems = [
  { id: 1, name: 'Chicken Rice Bowl', category: 'Meals', price: 89, stock: 10, icon: '🍗' },
  { id: 2, name: 'Beef Burger', category: 'Meals', price: 99, stock: 8, icon: '🍔' },
  { id: 3, name: 'Spaghetti', category: 'Meals', price: 79, stock: 6, icon: '🍝' },
  { id: 4, name: 'Iced Tea', category: 'Drinks', price: 35, stock: 15, icon: '🧊' },
  { id: 5, name: 'Soda', category: 'Drinks', price: 40, stock: 12, icon: '🥤' },
  { id: 6, name: 'Coffee', category: 'Drinks', price: 45, stock: 10, icon: '☕' },
  { id: 7, name: 'French Fries', category: 'Snacks', price: 55, stock: 9, icon: '🍟' },
  { id: 8, name: 'Nachos', category: 'Snacks', price: 65, stock: 7, icon: '🌽' },
  { id: 9, name: 'Ice Cream', category: 'Desserts', price: 50, stock: 5, icon: '🍦' },
  { id: 10, name: 'Chocolate Cake', category: 'Desserts', price: 60, stock: 0, icon: '🍰' },
];

// ================= CONTROLLER =================
var MenuController = {
  searchItems(items, searchText) {
    var lowerSearch = searchText.toLowerCase();
    return items.filter(function (item) {
      return !lowerSearch || item.name.toLowerCase().indexOf(lowerSearch) !== -1;
    });
  },

  matchesCategory(item, category) {
    return category === 'All' || item.category === category;
  },

  quantityInCart(cart, itemId) {
    var found = cart.find(function (c) { return c.id === itemId; });
    return found ? found.quantity : 0;
  },

  remainingStock(cart, item) {
    return item.stock - MenuController.quantityInCart(cart, item.id);
  },

  addToCart(cart, item) {
    if (MenuController.remainingStock(cart, item) <= 0) {
      return false;
    }

    var existing = cart.find(function (c) { return c.id === item.id; });
    if (existing) {
      existing.quantity++;
    } else {
      cart.push({ id: item.id, name: item.name, price: item.price, quantity: 1, icon: item.icon });
    }
    return true;
  },

  cartCount(cart) {
    return cart.reduce(function (sum, c) { return sum + c.quantity; }, 0);
  },
};
