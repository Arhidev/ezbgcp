<template>
  <div class="px-6 py-4"
  v-html="terms"></div>
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
      terms: null,
    };
  },
  mounted() {
    this.getTerms();
  },
  methods: {
    getTerms() {
      this.isLoading = true;
      axios
        .get("/docs/terms")
        .then((response) => {
          this.isLoading = false;
          this.terms = response.data.terms;
        })
        .catch((error) => {
          this.isLoading = false;
          this.$notify({
            title: "Error",
            text: "Error while retrieving terms and conditions",
            type: "error",
          });
        });
    },
  },
};
</script>
