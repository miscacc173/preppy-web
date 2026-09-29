import { createApp } from "vue";
import { createPinia } from "pinia";
import { VueQueryPlugin, QueryClient } from "@tanstack/vue-query";
import { createHead } from "@unhead/vue/client";

import App from "./App.vue";
import router from "./router";
import "./styles.css";
import { fetchCurrentUser, hydrateAppState, setupStateSync } from "./lib/api";
import { syncGrantedPushRegistration } from "./lib/pushNotifications";

const app = createApp(App);

const queryClient = new QueryClient();
const head = createHead();

app.use(createPinia());
app.use(router);
app.use(VueQueryPlugin, { queryClient });
app.use(head);

setupStateSync();

const socialLoginCompleting = typeof window !== "undefined" && ["/auth/google/complete", "/auth/apple/complete"].includes(window.location.pathname);

(socialLoginCompleting ? Promise.resolve(null) : fetchCurrentUser())
  .then(async (user) => { if (user) { await hydrateAppState(); void syncGrantedPushRegistration(); } })
  .catch(() => undefined)
  .finally(() => app.mount("#app"));
