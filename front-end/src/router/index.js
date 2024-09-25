import Vue from 'vue'
import VueRouter from 'vue-router'
import auth from '@/services/AuthService'

Vue.use(VueRouter)

const routes = [
  {
    path: '/',
    redirect: 'dashboard',
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/dashboard/Dashboard.vue'),
  },
  //////////////////////////users////////////////////////////////
  //admins
  {
    path: '/admin',
    name: 'admin',
    component: () => import('@/views/users/index.vue'),
  },
  //customers
  {
    path: '/customers',
    name: 'customers',
    component: () => import('@/views/users/index.vue'),
  },
  //drivers
  {
    path: '/drivers',
    name: 'drivers',
    component: () => import('@/views/users/index.vue'),
  },
  {
    path: '/users/view/user=:user_id',
    name: 'view-user',
    component: () => import('@/views/users/view-user.vue'),
  },
  {
    path: '/users/edit/user=:user_id',
    name: 'edit-user',
    component: () => import('@/views/users/edit-user.vue'),
  },
    //////////////////////////buses////////////////////////////////
    {
      path: '/buses',
      name: 'buses',
      component: () => import('@/views/system-setup/buses/index.vue'),
    },
    //////////////////////////buses////////////////////////////////
    {
        path: '/coupons',
        name: 'coupons',
        component: () => import('@/views/coupons/index.vue'),
        },
  //////////////////////////routes////////////////////////////////
  {
    path: '/routes',
    name: 'routes',
    component: () => import('@/views/system-setup/routes/index.vue'),
  },
  {
    path: '/routes/create/route=:route_name',
    name: 'create-route',
    component: () => import('@/views/system-setup/routes/create-edit.vue'),
  },
  {
    path: '/routes/edit/route=:route_id&route_name=:new_route_name',
    name: 'edit-route',
    component: () => import('@/views/system-setup/routes/create-edit.vue'),
  },
  {
    path: '/routes/view/route=:route_id',
    name: 'view-route',
    component: () => import('@/views/system-setup/routes/view.vue'),
  },
  //////////////////////////stops////////////////////////////////
  {
    path: '/stops',
    name: 'stops',
    component: () => import('@/views/system-setup/stops/index.vue'),
  },
  {
    path: '/stops/view/stop=:stop_id',
    name: 'view-stop',
    component: () => import('@/views/system-setup/stops/view.vue'),
  },
  {
    path: '/stops/create',
    name: 'create-stop',
    component: () => import('@/views/system-setup/stops/create-edit.vue'),
  },
  {
    path: '/stops/edit/stop=:stop_id',
    name: 'edit-stop',
    component: () => import('@/views/system-setup/stops/create-edit.vue'),
  },
  {
    path: '/trips',
    name: 'trips',
    component: () => import('@/views/trips/index.vue'),
  },
  //driver-conflicts
  {
    path: '/driver-conflicts',
    name: 'driver-conflicts',
    component: () => import('@/views/trips/driver-conflicts/index.vue'),
  },
  //////////////////////////customers////////////////////////////////
  {
    path: '/trips/create',
    name: 'create-trip',
    component: () => import('@/views/trips/create-edit.vue'),
  },
  {
    path: '/trips/edit/trip=:trip_id&action=:action',
    name: 'edit-trip',
    component: () => import('@/views/trips/create-edit.vue'),
  },
  {
    path: '/trips/view-trip/trip=:trip_id',
    name: 'view-trip',
    component: () => import('@/views/trips/view-trip.vue'),
  },
  {
    path: '/trips/view-calendar/trip=:trip_id&suspension=:suspension_id',
    name: 'view-calendar',
    component: () => import('@/views/trips/calendar/view-calendar.vue'),
  },
  //////////////////////////reservations////////////////////////////////
  {
    path: '/reservations',
    name: 'reservations',
    component: () => import('@/views/reservations/index.vue'),
  },
  {
    path: '/complaints',
    name: 'complaints',
    component: () => import('@/views/complaints/index.vue'),
  },
  //////////////////////////planned-trips////////////////////////////////
  {
    path: '/planned-trips',
    name: 'planned-trips',
    component: () => import('@/views/planned-trips/index.vue'),
  },
  //////////////////////////Payments///////////////////////////////////
  {
    path: '/upcoming-payments',
    name: 'upcoming-payments',
    component: () => import('@/views/payments/upcoming-payments/index.vue'),
  },
  {
    path: '/upcoming-payments/view-payment/user=:user_id',
    name: 'view-upcoming-payment',
    component: () => import('@/views/payments/upcoming-payments/view-payment.vue'),
  },
  {
    path: '/redemptions',
    name: 'redemptions',
    component: () => import('@/views/payments/redemptions/index.vue'),
  },
  //////////////////////////settings///////////////////////////////////
  {
    path: '/settings',
    name: 'settings',
    component: () => import('@/views/settings/index.vue'),
  },
  //////////////////////////activation///////////////////////////////////
  {
    path: '/activate-account',
    name: 'activate-account',
    component: () => import('@/views/activation/index.vue'),
  },
  //privacy-policy
  {
    path: '/privacy-policy',
    name: 'privacy-policy',
    component: () => import('@/views/settings/privacy-policy.vue'),
  },
  //privacy
  {
    path: '/privacy',
    name: 'privacy',
    component: () => import('@/views/settings/privacy-preview.vue'),
    meta: {
      layout: 'blank'
    },
  },
  //terms
  {
    path: '/terms-and-conditions',
    name: 'terms-and-conditions',
    component: () => import('@/views/settings/terms.vue'),
  },
  {
    path: '/terms',
    name: 'terms',
    component: () => import('@/views/settings/terms-preview.vue'),
    meta: {
      layout: 'blank'
    },
  },
  //////////////////////////pages//////////////////////////////////////
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/start-pages/Login.vue'),
    meta: {
      layout: 'blank'
    },
  },
  //ForgotPassword
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/ForgotPassword.vue'),
    meta: {
      layout: 'blank'
    },
  },
  {
    path: '/register',
    name: 'pages-register',
    component: () => import('@/views/start-pages/Register.vue'),
    meta: {
      layout: 'blank'
    },
  },
  {
    path: '/error-404',
    name: 'error-404',
    component: () => import('@/views/Error.vue'),
    meta: {
      layout: 'blank'
    },
  },
  {
    path: '*',
    redirect: 'error-404',
  },
]

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes,
})

// array of routes that do not require auth
const plainRoutes = [
    "/",
    "/home",
    "/login",
    "/register",
    "/forgot-password",
    "/privacy",
    "/terms",
    "/error-404",
    "/error-500",
];

router.beforeEach((to, from, next) => {

    let to_path = to.path;
    // remove = from path
    if (to_path.includes("=")) {
        to_path = to_path.split("=")[0] + "=";
    }
    console.log(to_path);

    let isUserAuth = auth.isUserLoggedIn();
    let isPlainRoute = plainRoutes.includes(to_path);

    //1 - if plain route, go to next
    if (isPlainRoute) {
        return next();
    }
    //2 - if not plain route and not auth, redirect to login
    if (!isUserAuth) {
        return next("/login");
    }

    return next()
    // Specify the current path as the customState parameter, meaning it
    // will be returned to the application after auth
    // auth.login({ target: to.path });
})

export default router
