var { createRouter, createWebHashHistory } = VueRouter;

var router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/login', component: LoginView, meta: { guest: true } },
    { path: '/register', component: RegisterView, meta: { guest: true } },
    { path: '/', component: DashboardView, meta: { requiresAuth: true } },
  ],
});

router.beforeEach(function (to, from, next) {
  AuthController.checkSession().then(function (data) {
    var loggedIn = !!data.user;

    if (to.meta.requiresAuth && !loggedIn) {
      next('/login');
    } else if (to.meta.guest && loggedIn) {
      next('/');
    } else {
      next();
    }
  });
});
