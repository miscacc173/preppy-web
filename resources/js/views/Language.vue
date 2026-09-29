<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { Globe, Calendar, MapPin, Bell, Eye, Check } from "lucide-vue-next";
import Sec from "./language/Sec.vue";
import Choice from "./language/Choice.vue";
import RegionToggle from "./language/RegionToggle.vue";

setPageMeta({ title: "Language & Region — Preppy" });

const lang = ref("en");
const examLang = ref("en");
const dateFmt = ref("dmy");
const timeFmt = ref("24");
const country = ref("PK");
const tz = ref("Asia/Karachi");
const notifLang = ref("en");
const a11y = reactive({ large: false, contrast: false, simple: false });

const previews: Record<string, string> = {
  en: "Welcome back, Aisha — ready to revise?",
  ur: "خوش آمدید عائشہ — آج پڑھنے کے لیے تیار ہیں؟",
  ar: "مرحبًا عائشة — هل أنتِ مستعدة للمراجعة؟",
  fr: "Bon retour Aisha — prête à réviser ?",
};

const langLabel = computed(() => ({ en: "English", ur: "Urdu", ar: "Arabic", fr: "French" }) as Record<string, string>);
const dir = computed(() => (lang.value === "ar" || lang.value === "ur" ? "rtl" : "ltr"));
</script>

<template>
  <AppShell title="Language & Region" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 max-w-[1100px]">
      <Sec :icon="Globe" title="Application Language">
        <p class="text-[12px] text-ink3">Current: <span class="font-semibold text-ink">{{ langLabel[lang] }}</span></p>
        <Choice v-model="lang" :options="[{ id: 'en', label: 'English' }, { id: 'ur', label: 'اردو' }, { id: 'ar', label: 'العربية' }, { id: 'fr', label: 'Français' }]" />
        <div class="p-3 rounded-lg bg-bg-soft border border-border text-[13px] text-ink2" :dir="dir">
          <p class="text-[11px] text-ink3 uppercase tracking-wider mb-1">Preview</p>
          {{ previews[lang] }}
        </div>
      </Sec>

      <Sec :icon="Globe" title="Exam Language">
        <p class="text-[12px] text-ink3">Controls question language, instructions and platform content during exams.</p>
        <Choice v-model="examLang" :options="[{ id: 'en', label: 'English' }, { id: 'ur', label: 'Urdu (where supported)' }]" />
      </Sec>

      <Sec :icon="Calendar" title="Date & Time Format">
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Date format</label>
          <Choice v-model="dateFmt" :options="[{ id: 'dmy', label: 'DD/MM/YYYY' }, { id: 'mdy', label: 'MM/DD/YYYY' }]" />
        </div>
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Time format</label>
          <Choice v-model="timeFmt" :options="[{ id: '12', label: '12-hour' }, { id: '24', label: '24-hour' }]" />
        </div>
      </Sec>

      <Sec :icon="MapPin" title="Regional Preferences">
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Country</label>
          <select v-model="country" class="w-full px-3 py-2 rounded-lg border border-border bg-bg-soft text-[13px] text-ink">
            <option value="PK">Pakistan</option>
            <option value="GB">United Kingdom</option>
            <option value="AE">UAE</option>
            <option value="IN">India</option>
            <option value="US">United States</option>
          </select>
        </div>
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Time Zone</label>
          <select v-model="tz" class="w-full px-3 py-2 rounded-lg border border-border bg-bg-soft text-[13px] text-ink">
            <option>Asia/Karachi</option>
            <option>Asia/Dubai</option>
            <option>Europe/London</option>
            <option>Asia/Kolkata</option>
            <option>America/New_York</option>
          </select>
        </div>
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Academic Region</label>
          <p class="text-[13px] text-ink">South Asia · Cambridge International</p>
        </div>
      </Sec>

      <Sec :icon="Bell" title="Notifications Language">
        <p class="text-[12px] text-ink3">Language used for email notifications, study reminders and exam alerts.</p>
        <Choice v-model="notifLang" :options="[{ id: 'en', label: 'English' }, { id: 'ur', label: 'اردو' }, { id: 'ar', label: 'العربية' }, { id: 'fr', label: 'Français' }]" />
      </Sec>

      <Sec :icon="Eye" title="Accessibility Options">
        <RegionToggle v-model:on="a11y.large" label="Larger Text" />
        <RegionToggle v-model:on="a11y.contrast" label="High Contrast Mode" />
        <RegionToggle v-model:on="a11y.simple" label="Simplified Language" />
      </Sec>
    </div>

    <div class="mt-6 flex justify-end max-w-[1100px]">
      <button class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue text-white text-[13px] font-semibold hover:bg-blue-mid transition">
        <Check :size="14" /> Save Settings
      </button>
    </div>
  </AppShell>
</template>
