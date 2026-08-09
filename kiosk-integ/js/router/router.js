var { createRouter, createWebHashHistory } = VueRouter;

var router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', component: KioskView },
    { path: '/cart', component: CartView },
    { path: '/receipt/:orderId', component: ReceiptView, props: true },
  ],
});
