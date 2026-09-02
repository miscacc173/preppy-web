import { createApp } from "vue";
import { createPinia } from "pinia";
import { VueQueryPlugin, QueryClient } from "@tanstack/vue-query";
import { createHead } from "@unhead/vue/client";

import App from "./App.vue";
import router from "./router";
import "./styles.css";
import { fetchCurrentUser, hydrateAppState, setupStateSync } from "./lib/api";

const app = createApp(App);

const queryClient = new QueryClient();
const head = createHead();

app.use(createPinia());
app.use(router);
app.use(VueQueryPlugin, { queryClient });
app.use(head);

setupStateSync();

fetchCurrentUser()
  .then(async (user) => { if (user) await hydrateAppState(); })
  .catch(() => undefined)
  .finally(() => app.mount("#app"));
