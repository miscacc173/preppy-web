// Derives motivation widgets (achievements, weekly progress, community proof,
// ranking, dynamic message) from real session history. Pure functions so the
// dashboard stays a thin view layer.

import type { SessionRecord } from "@/lib/sessionStore";

export type Achievement = {
  id: string;
  emoji: string;
  label: string;
  unlockedAt: string; // ISO
};

function startOfDay(d: Date) {
  const x = new Date(d);
  x.setHours(0, 0, 0, 0);
  return x;
}

function daysBetween(a: Date, b: Date) {
  return Math.round((startOfDay(a).getTime() - startOfDay(b).getTime()) / 86400000);
}

// Consecutive days (ending today or yesterday) with at least one session.
export function computeStreak(sessions: SessionRecord[]): number {
  if (sessions.length === 0) return 0;
  const days = new Set(
    sessions.map((s) => startOfDay(new Date(s.date)).getTime()),
  );
  const today = startOfDay(new Date()).getTime();
  const yesterday = today - 86400000;
  let cursor = days.has(today) ? today : days.has(yesterday) ? yesterday : null;
  if (cursor === null) return 0;
  let streak = 0;
  while (days.has(cursor)) {
    streak += 1;
    cursor -= 86400000;
  }
  return streak;
}

export function computeAchievements(sessions: SessionRecord[]): Achievement[] {
  const list: Achievement[] = [];
  const learn = sessions.filter((s) => s.type === "Learn");
  const exam = sessions.filter((s) => s.type === "Exam");
  const totalQ = sessions.reduce((sum, s) => sum + s.questions.length, 0);
  const streak = computeStreak(sessions);
  const bestAcc = sessions.reduce((m, s) => Math.max(m, s.accuracy), 0);
  const firstDate = sessions.length
    ? sessions[sessions.length - 1].date
    : new Date().toISOString();
  const lastDate = sessions[0]?.date ?? new Date().toISOString();

  if (learn.length >= 1)
    list.push({ id: "first-learn", emoji: "📘", label: "First Learn Mode", unlockedAt: learn[learn.length - 1].date });
  if (exam.length >= 1)
    list.push({ id: "first-exam", emoji: "📝", label: "First Mock Exam", unlockedAt: exam[exam.length - 1].date });
  if (totalQ >= 100)
    list.push({ id: "q100", emoji: "💯", label: "100 Questions Solved", unlockedAt: lastDate });
  if (totalQ >= 500)
    list.push({ id: "q500", emoji: "🏅", label: "500 Questions Solved", unlockedAt: lastDate });
  if (streak >= 7)
    list.push({ id: "streak7", emoji: "🔥", label: "7 Day Study Streak", unlockedAt: lastDate });
  if (streak >= 30)
    list.push({ id: "streak30", emoji: "🌟", label: "30 Day Study Streak", unlockedAt: lastDate });
  if (bestAcc >= 90)
    list.push({ id: "astar", emoji: "🏆", label: "A* Ready", unlockedAt: lastDate });
  // Fast Learner: 5+ sessions in first 7 days of activity
  if (sessions.length >= 5) {
    const firstMs = new Date(firstDate).getTime();
    const within = sessions.filter(
      (s) => new Date(s.date).getTime() - firstMs <= 7 * 86400000,
    ).length;
    if (within >= 5)
      list.push({ id: "fast", emoji: "⚡", label: "Fast Learner", unlockedAt: lastDate });
  }
  if (streak >= 3)
    list.push({ id: "consistency", emoji: "🎯", label: "Consistency Badge", unlockedAt: lastDate });

  // Newest first, cap to a reasonable count for the compact card.
  return list
    .sort((a, b) => new Date(b.unlockedAt).getTime() - new Date(a.unlockedAt).getTime())
    .slice(0, 6);
}

export type WeeklyProgress = {
  sessions: number;
  questions: number;
  minutes: number;
  goal: number; // weekly session goal
  goalPct: number;
  streak: number;
};

export function computeWeekly(sessions: SessionRecord[], goal = 5): WeeklyProgress {
  const now = new Date();
  const weekStart = startOfDay(now);
  weekStart.setDate(weekStart.getDate() - 6); // last 7 days rolling
  const inWeek = sessions.filter((s) => new Date(s.date) >= weekStart);
  const questions = inWeek.reduce((sum, s) => sum + s.questions.length, 0);
  const seconds = inWeek.reduce((sum, s) => sum + s.timeSec, 0);
  const streak = computeStreak(sessions);
  return {
    sessions: inWeek.length,
    questions,
    minutes: Math.round(seconds / 60),
    goal,
    goalPct: Math.min(100, Math.round((inWeek.length / goal) * 100)),
    streak,
  };
}

// Deterministic pseudo-random community counts that gently vary by day and
// hour so the numbers feel alive without pretending to be a real feed.
export function computeCommunity(): { studying: number; mocks: number; questions: number } {
  const now = new Date();
  const seed =
    now.getFullYear() * 10000 + (now.getMonth() + 1) * 100 + now.getDate();
  const rng = (n: number) => ((Math.sin(seed * n) + 1) / 2);
  const hourWeight = Math.min(1, 0.35 + now.getHours() / 24);
  const studying = Math.round((1800 + rng(3) * 1200) * hourWeight);
  const mocks = Math.round((110 + rng(7) * 180) * hourWeight);
  const questions = Math.round((3800 + rng(11) * 4200) * hourWeight);
  return { studying, mocks, questions };
}

// Personal ranking derived from readiness. Higher readiness → higher percentile.
export function computeRanking(readiness: number, weeklySessions: number): {
  percentile: number;
  rank: number;
  delta: number;
} {
  const percentile = Math.max(1, Math.min(99, Math.round(100 - readiness * 0.9)));
  const rank = Math.max(1, Math.round(percentile * 3 + 4));
  const delta = Math.max(-3, Math.min(12, weeklySessions * 2 - 3));
  return { percentile, rank, delta };
}

export function computeMotivation(args: {
  readiness: number;
  grade: string;
  weekly: WeeklyProgress;
  achievementsCount: number;
  sessions: SessionRecord[];
  subjectName: string;
}): { emoji: string; text: string } {
  const { readiness, weekly, sessions, subjectName } = args;

  // Subject improvement week-over-week
  const now = Date.now();
  const week = 7 * 86400000;
  const subjSessions = sessions.filter((s) => s.subject === subjectName);
  const thisWeek = subjSessions.filter((s) => now - new Date(s.date).getTime() <= week);
  const lastWeek = subjSessions.filter((s) => {
    const t = now - new Date(s.date).getTime();
    return t > week && t <= 2 * week;
  });
  if (thisWeek.length >= 2 && lastWeek.length >= 1) {
    const avg = (arr: SessionRecord[]) => arr.reduce((s, x) => s + x.accuracy, 0) / arr.length;
    const delta = Math.round(avg(thisWeek) - avg(lastWeek));
    if (delta >= 5)
      return { emoji: "📈", text: `Your ${subjectName} accuracy improved by ${delta}% this week.` };
    if (delta <= -5)
      return { emoji: "💪", text: `Push a little harder — ${subjectName} dipped ${Math.abs(delta)}% this week.` };
  }

  // Next grade threshold
  const thresholds: { grade: string; at: number }[] = [
    { grade: "A*", at: 90 },
    { grade: "A", at: 80 },
    { grade: "B", at: 70 },
    { grade: "C", at: 55 },
  ];
  const next = thresholds.find((t) => readiness < t.at);
  if (next) {
    const gap = next.at - readiness;
    return { emoji: "🔥", text: `You're only ${gap}% away from reaching ${next.grade === "A" || next.grade === "A*" ? "an" : "a"} ${next.grade}.` };
  }

  if (weekly.goalPct < 100)
    return { emoji: "🎯", text: `Complete ${weekly.goal - weekly.sessions} more session${weekly.goal - weekly.sessions === 1 ? "" : "s"} to hit your weekly goal.` };

  return { emoji: "🏆", text: "You're performing better than last week. Keep the streak alive." };
}
