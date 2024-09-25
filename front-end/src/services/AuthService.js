import axios from "axios";
import store from "@/store";

import firebase from 'firebase/compat/app';
import 'firebase/compat/auth'
import Router from '../router/index'

const loginEvent = 'freshToken'


export default {
  async login2(payload) {
    await authClient.get("/sanctum/csrf-cookie");
    return authClient.post("/login", payload);
  },
  isUserLoggedIn() {
    let isAuthenticated = false

    // get firebase current user
    const firebaseCurrentUser = firebase.auth().currentUser

    if (firebaseCurrentUser) isAuthenticated = true
    else isAuthenticated = false

    // return localStorage.getItem(loginEvent) != "null";
    return localStorage.getItem(loginEvent) != null && localStorage.getItem(loginEvent) != 'null';
  },
  async login (payload) {
    // If user is already logged in notify and exit
    if (this.isUserLoggedIn()) {
      payload.notify({
        title: 'Login Attempt',
        text: 'You are already logged in!',
        type: 'warning'
      })
      return false
    }
    // Try to sigin
    try {
      var result = await firebase.auth().signInWithEmailAndPassword(payload.email, payload.password);
      var token = await result.user.getIdToken(true);
      var response = await axios.post('/auth/loginViaToken', {
        'device_name': `${vm.$browserDetect.meta.name  }- v${  vm.$browserDetect.meta.version}`,
        token
      });
      const isAdmin = response.data.admin
      if (!isAdmin)
      {
        const error = Error(
          "Your account can not be used here! Only admin accounts."
        );
        error.name = "Not admin";
        throw error;
      }
      const ourToken = response.data.token
      const freshToken = ourToken.split('|')[1]
      localStorage.setItem(loginEvent, freshToken)
      axios.defaults.headers.common['Authorization'] = `Bearer ${freshToken}`
      return true;
    } catch (error) {
      localStorage.setItem(loginEvent, null)
      payload.notify({
        title: 'Error',
        text: error.message,
        type: 'error'
      })
    }
  },
  async logout() {

    // if user is logged in via firebase
    const firebaseCurrentUser = firebase.auth().currentUser

    if (firebaseCurrentUser) {
      await firebase.auth().signOut();
    }

    localStorage.setItem(loginEvent, null)

    // If user clicks on logout -> redirect
    Router.push('/login').catch(() => {})
  },
  logout2() {
    return authClient.post("/logout");
  },
  async forgotPassword(payload) {
    await authClient.get("/sanctum/csrf-cookie");
    return authClient.post("/forgot-password", payload);
  },
  getAuthUser() {
    return authClient.get("/api/users/auth");
  },
  async resetPassword(payload) {
    return axios
    .post('/auth/reset-password', {
      email: payload.email,
    });
  },
  updatePassword(payload) {
    return authClient.put("/user/password", payload);
  },
  async registerUser(payload) {
    await authClient.get("/sanctum/csrf-cookie");
    return authClient.post("/register", payload);
  },
  sendVerification(payload) {
    return authClient.post("/email/verification-notification", payload);
  },
  updateUser(payload) {
    return authClient.put("/user/profile-information", payload);
  },
  checkError(error, router, swal)
  {
    console.log(error)
    if(error.includes('Unauthenticated'))
    {
      this.logout();
      router.push({ name: 'login' });
    }
    else{
      swal("Error", error, "error");
    }
  }
};
