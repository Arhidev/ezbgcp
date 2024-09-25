<template>
  <div class="auth-wrapper auth-v1">
    <div class="auth-inner">
      <vue-element-loading :active="submiting" />
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

        <!-- title -->
        <v-card-text>
          <p class="mb-2">
            Please sign-in to your account to start
          </p>
        </v-card-text>

        <!-- login form -->
        <v-card-text>
          <v-form
          ref="form"
          v-model="valid"
          lazy-validation>
            <v-text-field
              v-model="email"
              outlined
              label="Email"
              placeholder="john@example.com"
              required
              :rules="emailRules"
              class="mb-3"
            ></v-text-field>

            <v-text-field
              v-model="password"
              outlined
              :type="isPasswordVisible ? 'text' : 'password'"
              label="Password"
              placeholder="············"
              :append-icon="isPasswordVisible ? icons.mdiEyeOffOutline : icons.mdiEyeOutline"
              required
              :rules="passRules"
              @click:append="isPasswordVisible = !isPasswordVisible"
            ></v-text-field>

            <div class="d-flex align-center justify-space-between flex-wrap">
              <v-checkbox
                label="Remember Me"
                hide-details
                class="me-3 mt-1"
              >
              </v-checkbox>

              <!-- forgot link to forgot-password route-->
              <router-link
                class="pt-1"
                to="/forgot-password"
              >
                Forgot Password?
              </router-link>
            </div>

            <v-btn
              block
              color="primary"
              class="mt-6"
              @click="login"
            >
              Login
            </v-btn>
          </v-form>
        </v-card-text>
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
// eslint-disable-next-line object-curly-newline
import { mdiFacebook, mdiTwitter, mdiGithub, mdiGoogle, mdiEyeOutline, mdiEyeOffOutline } from '@mdi/js'
import { ref } from '@vue/composition-api'
import AuthService from "@/services/AuthService";
import { getError } from "@/utils/helpers";
import VueElementLoading from "vue-element-loading";
import {Keys} from '/src/config.js';
export default {
  components: {
    VueElementLoading,
  },
  setup() {
    const isPasswordVisible = ref(false)
    const email = ref('')
    const password = ref('')
    const socialLink = [
      {
        icon: mdiFacebook,
        color: '#4267b2',
        colorInDark: '#4267b2',
      },
      {
        icon: mdiTwitter,
        color: '#1da1f2',
        colorInDark: '#1da1f2',
      },
      {
        icon: mdiGithub,
        color: '#272727',
        colorInDark: '#fff',
      },
      {
        icon: mdiGoogle,
        color: '#db4437',
        colorInDark: '#db4437',
      },
    ]

    return {
      isPasswordVisible,
      email,
      password,
      socialLink,

      icons: {
        mdiEyeOutline,
        mdiEyeOffOutline,
      },
    }
  },
  data() {
    return {
      systemName: Keys.VUE_APP_SYSTEM_NAME,
      valid: true,
      submiting: false,
      emailRules: [
        v => !!v || 'E-mail is required',
        v => /.+@.+\..+/.test(v) || 'E-mail must be valid',
      ],
      passRules: [(v) => !!v || "Password is required"],
    };
  },
  methods: {
    validate() {
      return this.$refs.form.validate();
    },
    async login() {
      if (!this.validate()) return;
      const payload = {
        email: this.email,
        password: this.password,
        notify: this.$notify,
      };
      this.error = null;
      try {
        this.submiting = true;
        const isLoggedIn = await AuthService.login(payload);
        this.submiting = false;
        if(isLoggedIn)
        {
          this.$router.push(this.$router.currentRoute.query.to || '/')
        }
      } catch (error) {
        console.log(error);
        this.submiting = false;
        //this.error = getError(error);
      }
    },
  },
}
</script>

<style lang="scss">
@import '~@/plugins/vuetify/default-preset/preset/pages/auth.scss';
</style>
