<template>
  <v-tooltip bottom color="primary" v-if="!activationStore.isActivated">
    <template v-slot:activator="{ on }">
      <v-icon v-on="on" color="primary">
        mdi-information-outline
      </v-icon>
    </template>
    <span>Activate the system to create {{model}}</span>
  </v-tooltip>
</template>

<script>
import { activationStore } from "@/utils/helpers";
export default {
  name: "ActivationToolTip",
  props: {
    model: {
      type: String,
      default: "",
    },
  },
  setup() {
    return { activationStore }
  },
};
</script>
