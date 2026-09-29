<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { BookOpen, Target, Brain, Sliders, MessageSquare, Clock, Bell, Sparkles, Check } from "lucide-vue-next";
import Section from "./study-preferences/Section.vue";
import Pill from "./study-preferences/Pill.vue";
import Toggle from "./study-preferences/Toggle.vue";
import { platformRepository } from "@/data/repositories/platformRepository";

setPageMeta({ title: "Study Preferences — Preppy" });

const SUBJECTS = ref<string[]>([]);
const enabled = reactive<Record<string, boolean>>({});
const primary = ref("");
const targetGrade = ref("");
const weeklyHours = ref(0);
const examDate = ref("");
const style = ref("");
const difficulty = ref("");
const qTypes = reactive<Record<string, boolean>>({ "Multiple Choice": false, "Structured Questions": false, "Extended Response": false, "Practical Questions": false });
const fb = reactive({ detailed: false, quick: false, model: false, hints: false });
const defaults = reactive({ learnLen: 0, examLen: 0, qCount: 0 });
const reminders = reactive({ daily: false, weekly: false, beforeExam: false });
const coach = ref("");
const focus = ref("");
const loading = ref(true);
const saving = ref(false);
const message = ref("");
const error = ref("");

onMounted(async () => {
  try {
    const bundle = await platformRepository.account();
    const p = bundle.account.preferences;
    SUBJECTS.value = bundle.account.subjects.map((subject) => subject.name);
    SUBJECTS.value.forEach((subject) => { enabled[subject] = true; });
    primary.value = p.primarySubject || SUBJECTS.value[0] || "";
    targetGrade.value = p.targetGrade;
    weeklyHours.value = p.weeklyHours;
    examDate.value = p.examDate;
    style.value = p.learningStyle;
    difficulty.value = p.difficulty;
    Object.assign(qTypes, p.questionTypes);
    Object.assign(fb, p.feedback);
    Object.assign(defaults, p.sessionDefaults);
    coach.value = p.coach;
    focus.value = p.focus;
    reminders.daily = bundle.notifications.find((item) => item.type === "study_reminder")?.enabled ?? false;
    reminders.weekly = bundle.notifications.find((item) => item.type === "performance_summary")?.enabled ?? false;
    reminders.beforeExam = bundle.notifications.find((item) => item.type === "exam_countdown")?.enabled ?? false;
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Study preferences could not be loaded.";
  } finally {
    loading.value = false;
  }
});

async function save() {
  saving.value = true;
  message.value = "";
  error.value = "";
  try {
    await Promise.all([
      platformRepository.updateAccount({ preferences: {
        primary_subject: primary.value, target_grade: targetGrade.value, weekly_hours: weeklyHours.value,
        exam_date: examDate.value, learning_style: style.value, difficulty: difficulty.value,
        question_types: { ...qTypes }, feedback: { ...fb },
        session_defaults: { learn_len: defaults.learnLen, exam_len: defaults.examLen, question_count: defaults.qCount },
        coach: coach.value, focus: focus.value,
      } }),
      platformRepository.saveNotifications([
        { type: "study_reminder", channel: "push", enabled: reminders.daily, schedule: {} },
        { type: "performance_summary", channel: "email", enabled: reminders.weekly, schedule: {} },
        { type: "exam_countdown", channel: "push", enabled: reminders.beforeExam, schedule: {} },
      ]),
    ]);
    message.value = "Study preferences saved.";
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Study preferences could not be saved.";
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <AppShell title="Study Preferences" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 max-w-[1100px]">
      <p v-if="loading" class="lg:col-span-2 rounded-xl border border-border bg-bg-card p-4 text-[13px] text-ink3">Loading preferences…</p>
      <p v-if="message" class="lg:col-span-2 rounded-xl bg-mint-light p-4 text-[13px] text-mint">{{ message }}</p>
      <p v-if="error" class="lg:col-span-2 rounded-xl bg-pink-light p-4 text-[13px] text-pink">{{ error }}</p>
      <Section :icon="BookOpen" title="Preferred Subjects" desc="Enable subjects and pick a primary focus.">
        <div class="space-y-2">
          <div v-for="s in SUBJECTS" :key="s" class="flex items-center justify-between p-3 rounded-lg bg-bg-soft border border-border">
            <div class="flex items-center gap-3">
              <button :class="`w-4 h-4 rounded-full border-2 grid place-items-center ${primary === s ? 'border-blue' : 'border-border'}`" @click="primary = s">
                <span v-if="primary === s" class="w-2 h-2 rounded-full bg-blue" />
              </button>
              <span class="text-[13px] text-ink font-medium">{{ s }}</span>
              <span v-if="primary === s" class="text-[10px] font-semibold text-blue bg-blue-light rounded-full px-2 py-0.5">Primary</span>
            </div>
            <Toggle v-model:on="enabled[s]" label="" />
          </div>
        </div>
      </Section>

      <Section :icon="Target" title="Study Goals">
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Target Grade</label>
          <div class="flex flex-wrap gap-2">
            <Pill v-for="g in ['A*', 'A', 'B', 'C']" :key="g" :active="targetGrade === g" @click="targetGrade = g">{{ g }}</Pill>
          </div>
        </div>
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Weekly Study Hours: <span class="text-ink font-semibold">{{ weeklyHours }}h</span></label>
          <input v-model.number="weeklyHours" type="range" min="1" max="40" class="w-full" />
        </div>
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Target Exam Date</label>
          <input v-model="examDate" type="date" class="w-full px-3 py-2 rounded-lg border border-border bg-bg-soft text-[13px] text-ink" />
        </div>
      </Section>

      <Section :icon="Brain" title="Learning Style">
        <div class="flex flex-wrap gap-2">
          <Pill v-for="o in [{ id: 'topic', l: 'Learn by Topic' }, { id: 'papers', l: 'Learn by Past Papers' }, { id: 'mixed', l: 'Mixed Learning Path' }]" :key="o.id" :active="style === o.id" @click="style = o.id">
            {{ o.l }}
          </Pill>
        </div>
      </Section>

      <Section :icon="Sliders" title="Practice Preferences">
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Question Difficulty</label>
          <div class="flex flex-wrap gap-2">
            <Pill v-for="d in ['easy', 'medium', 'hard', 'mixed']" :key="d" :active="difficulty === d" @click="difficulty = d">{{ d[0].toUpperCase() + d.slice(1) }}</Pill>
          </div>
        </div>
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Question Types</label>
          <div class="space-y-1">
            <label v-for="q in Object.keys(qTypes)" :key="q" class="flex items-center gap-2 text-[13px] text-ink2 cursor-pointer">
              <input v-model="qTypes[q]" type="checkbox" class="accent-blue" />
              {{ q }}
            </label>
          </div>
        </div>
      </Section>

      <Section :icon="MessageSquare" title="Feedback Preferences">
        <Toggle v-model:on="fb.detailed" label="Detailed Examiner Feedback" />
        <Toggle v-model:on="fb.quick" label="Quick Feedback" />
        <Toggle v-model:on="fb.model" label="Show Model Answers Automatically" />
        <Toggle v-model:on="fb.hints" label="Show Progressive Hints" />
      </Section>

      <Section :icon="Clock" title="Study Session Defaults">
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Learn Mode session length: <span class="text-ink font-semibold">{{ defaults.learnLen }} min</span></label>
          <input v-model.number="defaults.learnLen" type="range" min="5" max="90" class="w-full" />
        </div>
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Exam Practice session length: <span class="text-ink font-semibold">{{ defaults.examLen }} min</span></label>
          <input v-model.number="defaults.examLen" type="range" min="5" max="180" class="w-full" />
        </div>
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Default number of questions: <span class="text-ink font-semibold">{{ defaults.qCount }}</span></label>
          <input v-model.number="defaults.qCount" type="range" min="5" max="50" class="w-full" />
        </div>
      </Section>

      <Section :icon="Bell" title="Notifications & Reminders">
        <Toggle v-model:on="reminders.daily" label="Daily study reminders" />
        <Toggle v-model:on="reminders.weekly" label="Weekly progress reminders" />
        <Toggle v-model:on="reminders.beforeExam" label="Before-exam reminders" />
      </Section>

      <Section :icon="Sparkles" title="AI Coaching Preferences">
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Feedback tone</label>
          <div class="flex flex-wrap gap-2">
            <Pill :active="coach === 'supportive'" @click="coach = 'supportive'">More Supportive</Pill>
            <Pill :active="coach === 'direct'" @click="coach = 'direct'">More Direct</Pill>
          </div>
        </div>
        <div>
          <label class="text-[12px] text-ink3 mb-1.5 block">Coaching focus</label>
          <div class="flex flex-wrap gap-2">
            <Pill :active="focus === 'weak'" @click="focus = 'weak'">Focus on Weak Areas</Pill>
            <Pill :active="focus === 'grade'" @click="focus = 'grade'">Focus on Grade Improvement</Pill>
          </div>
        </div>
      </Section>
    </div>

    <div class="mt-6 flex justify-end max-w-[1100px]">
      <button :disabled="saving || loading" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue text-white text-[13px] font-semibold hover:bg-blue-mid transition disabled:opacity-50" @click="save">
        <Check :size="14" /> {{ saving ? "Saving…" : "Save Preferences" }}
      </button>
    </div>
  </AppShell>
</template>
