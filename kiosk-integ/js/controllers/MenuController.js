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

var MenuController = {
  searchItems(items, searchText) {
    var s = searchText.toLowerCase();
    return items.filter(function (item) { return !s || item.name.toLowerCase().indexOf(s) !== -1; });
  },
  matchesCategory(item, category) {
    return category === 'All' || item.category === category;
  },
  remainingStock(cart, item) {
    var found = cart.find(function (c) { return c.id === item.id; });
    return item.stock - (found ? found.quantity : 0);
  },
  addToCart(cart, item) {
    if (MenuController.remainingStock(cart, item) <= 0) return false;
    var existing = cart.find(function (c) { return c.id === item.id; });
    if (existing) existing.quantity++;
    else cart.push({ id: item.id, name: item.name, price: item.price, quantity: 1, icon: item.icon });
    return true;
  },
  cartCount(cart) {
    return cart.reduce(function (sum, c) { return sum + c.quantity; }, 0);
  },
};
