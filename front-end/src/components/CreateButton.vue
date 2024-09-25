<template>
  <v-btn depressed 
  class="mr-2"
  :disabled="!activationStore.isActivated"
  color="primary" @click="create">
    Create
    <v-icon right dark> mdi-plus-thick </v-icon>
  </v-btn>
</template>

<script>
import { activationStore } from "@/utils/helpers";
export default {
  name: "CreateButton",
  setup() {
    return { activationStore }
  },
  methods: {
    create() {
      this.$emit("create");
    }
  }
};
</script>
