<template>
  <div class="px-6 py-4"
  v-html="privacyPolicy"></div>
</template>

<script>

import VueElementLoading from "vue-element-loading";

export default {
  components: {
    VueElementLoading,
  },
  data() {
    return {
      isLoading: false,
      privacyPolicy: null,
    };
  },
  mounted() {
    this.getPrivacy();
  },
  methods: {
    getPrivacy() {
      this.isLoading = true;
      axios
        .get("/docs/privacy-policy")
        .then((response) => {
          this.isLoading = false;
          this.privacyPolicy = response.data.privacy;
          console.log(this.privacyPolicy);
        })
        .catch((error) => {
          this.isLoading = false;
          this.$notify({
            title: "Error",
            text: "Error while retrieving privacy policy",
            type: "error",
          });
        });
    },
  },
};
</script>
