<template>
  <div class="auth-wrapper auth-v1">
    <div class="auth-inner">
      <v-card class="auth-card">
        <!-- logo -->
        <v-card-title class="d-flex align-center justify-center py-7">
            <v-img
              :src="require('@/assets/images/logos/logo.png')"
              max-height="30px"
              max-width="30px"
              alt="logo"
              contain
              class="me-3 "
            ></v-img>

            <h2 class="text-2xl font-weight-semibold">
                {{ systemName }}
            </h2>
        </v-card-title>

    <h2 class="mb-4 text-xl font-bold text-center">Forgot Password</h2>
    <v-card-text>
    <form
      class="p-5 bg-white border rounded shadow"
    >
      <v-text-field
        type="email"
        outlined
        label="Email"
        name="email"
        v-model="email"
        placeholder="luke@jedi.com"
        class="mb-4"
      />
      <v-btn
        block
        color="primary"
        class="mt-6"
        @click="forgotPassword"
      >
        Send
      </v-btn>
    </form>
    </v-card-text>
        <v-card-text class="d-flex align-center justify-center flex-wrap mt-2">
          <router-link :to="{ name:'pages-login' }">
            Go to login page
          </router-link>
        </v-card-text>
    <FlashMessage :message="message" :error="error" />
      </v-card>
    </div>

    <!-- background triangle shape  -->
    <img
      class="auth-mask-bg"
      height="173"
      :src="require(`@/assets/images/misc/mask-${$vuetify.theme.dark ? 'dark':'light'}.png`)"
    >

    <!-- tree -->
    <v-img
      class="auth-tree"
      width="247"
      height="185"
      src="@/assets/images/misc/tree.png"
    ></v-img>

    <!-- tree  -->
    <v-img
      class="auth-tree-3"
      width="377"
      height="289"
      src="@/assets/images/misc/tree-3.png"
    ></v-img>
  </div>
</template>

<script>
import { getError } from "@/utils/helpers";
import BaseBtn from "@/components/BaseBtn";
import BaseInput from "@/components/BaseInput";
import AuthService from "@/services/AuthService";
import FlashMessage from "@/components/FlashMessage";
import {Keys} from '/src/config.js';
export default {
  name: "ForgotPassword",
  components: {
    BaseBtn,
    BaseInput,
    FlashMessage,
    Keys
  },
  data() {
    return {
      email: null,
      error: null,
      message: null,
      systemName: Keys.VUE_APP_SYSTEM_NAME,
    };
  },
  methods: {
    forgotPassword() {
      this.error = null;
      this.message = null;
      const payload = {
        email: this.email,
      };
      AuthService.resetPassword(payload)
        .then(() => (this.message = "Reset password email sent."))
        .catch((error) => (
          this.error = error.response.data)
          );
    },
  },
};
</script>

<style lang="scss">
@import '~@/plugins/vuetify/default-preset/preset/pages/auth.scss';
</style>
