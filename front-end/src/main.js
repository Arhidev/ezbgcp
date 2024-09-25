
require("./bootstrap");

import '@/plugins/vue-composition-api'
import '@/styles/styles.scss'
import Vue from 'vue'
import App from './App.vue'
import vuetify from './plugins/vuetify'
import router from './router'
import store from './store'
import "./axios";
import "./firebaseConfig";
import Notifications from 'vue-notification'
import VueSweetalert2 from 'vue-sweetalert2';

import VueProgressBar from 'vue-progressbar'

const options = {
  color: '#9155fd',
  failedColor: '#874b4b',
  thickness: '2px',
  transition: {
    speed: '0.5s',
    opacity: '0.6s',
    termination: 500
  },
  autoRevert: true,
  location: 'top',
  inverse: false
}

Vue.use(VueProgressBar, options)

// If you don't need the styles, do not connect
import 'sweetalert2/dist/sweetalert2.min.css';

Vue.use(VueSweetalert2);
Vue.use(Notifications);
Vue.use(require('vue-moment'));

import browserDetect from "vue-browser-detect-plugin";
Vue.use(browserDetect);

import * as VueGoogleMaps from 'vue2-google-maps'
Vue.use(VueGoogleMaps);

Vue.config.productionTip = false

const app = new Vue({
  router,
  store,
  vuetify,
  render: h => h(App),
}).$mount('#app')

global.vm = app
