// Pure data + types for the Learn session flow, ported 1:1 from
// learn.session.tsx (framework-agnostic in the source already).
import type { QuestionVisualSpec } from "@/components/app/question-visual";
import { LEARN_DIFFICULTIES } from "@/lib/wizardStore";

export const DIFFICULTIES = LEARN_DIFFICULTIES;
export const COUNTS = [5, 10, 15, 20] as const;
export const PRACTICE_TIMERS = [
  { v: 0, l: "Untimed" },
  { v: 30, l: "30 min" },
  { v: 45, l: "45 min" },
  { v: 60, l: "60 min" },
] as const;

export const MCQ_LABELS = ["Multiple Choice"] as const;

export type QType = "mcq" | "short";

export interface Question {
  q: string;
  type: QType;
  marks: number;
  opts?: string[];
  correctOpt?: number;
  markScheme: string[];
  hints: string[];
  starAnswer: string;
  visual?: QuestionVisualSpec;
}

export const BANK: Question[] = [
  {
    q: "The diagram shows a uniform beam supported at P with a 10 N weight at Q. Calculate the reaction force at P and state Newton's first law condition for equilibrium.",
    type: "short",
    marks: 6,
    visual: { kind: "physics-beam", caption: "Uniform beam, support at P, load at Q" },
    markScheme: [
      "A body remains at rest or in uniform motion in a straight line (1)",
      "...unless acted on by a resultant external force (1)",
      "Equilibrium: vector sum of forces = 0 (2)",
      "Resultant torque about any point = 0 (2)",
    ],
    hints: [
      "Think about which two conditions must hold for equilibrium.",
      "Mention 'resultant force' specifically — examiners look for this exact phrase.",
      "Take moments about P to find the reaction.",
    ],
    starAnswer:
      "Newton's first law: a body remains at rest, or in uniform motion in a straight line, unless acted upon by a resultant external force. Equilibrium requires the vector sum of forces to be zero AND the resultant torque about any point to be zero.",
  },
  {
    q: "Which of the following is a vector quantity?",
    type: "mcq",
    marks: 1,
    opts: ["Mass", "Speed", "Velocity", "Energy"],
    correctOpt: 2,
    visual: {
      kind: "table",
      caption: "Quantities and their nature",
      rows: [["Quantity", "Has direction?"], ["Mass", "No"], ["Speed", "No"], ["Velocity", "Yes"], ["Energy", "No"]],
    },
    markScheme: ["Velocity has both magnitude and direction (1)"],
    hints: ["Vectors have direction.", "Speed and velocity are not the same.", "Mass and energy are scalars."],
    starAnswer: "Velocity — it has both magnitude and direction.",
  },
  {
    q: "A car of mass 1200 kg accelerates from rest to 25 m/s in 8 s. Calculate the resultant force.",
    type: "short",
    marks: 3,
    visual: { kind: "formula", caption: "Newton's second law", formula: "F = m × a" },
    markScheme: ["a = Δv/t = 25/8 = 3.125 m/s² (1)", "F = ma (1)", "F = 1200 × 3.125 = 3750 N (1)"],
    hints: ["First find acceleration from the change in velocity over time.", "Then apply Newton's second law.", "Show every line of working — method marks matter."],
    starAnswer: "a = 25/8 = 3.125 m/s². F = ma = 1200 × 3.125 = 3750 N.",
  },
  {
    q: "The I–V curve shown is for a filament lamp. Explain its shape.",
    type: "short",
    marks: 4,
    visual: { kind: "physics-iv-curve", caption: "Current vs voltage for a filament lamp" },
    markScheme: [
      "As V increases, current increases (1)",
      "Filament heats up → resistance increases (1)",
      "Gradient (I/V) decreases (1)",
      "Therefore curve flattens at higher voltages (1)",
    ],
    hints: ["Think about temperature.", "Resistance is not constant.", "Relate gradient to resistance."],
    starAnswer: "Current rises with voltage, but as the filament heats up its resistance increases, so the gradient of I against V decreases and the curve flattens.",
  },
  {
    q: "A capacitor of 10 μF is charged to 12 V. What is the energy stored?",
    type: "mcq",
    marks: 1,
    opts: ["120 μJ", "720 μJ", "1.44 mJ", "60 μJ"],
    correctOpt: 1,
    visual: { kind: "physics-circuit", caption: "Capacitor charged from a 9 V source through R₁" },
    markScheme: ["E = ½CV² = ½ × 10e-6 × 144 = 7.2e-4 J = 720 μJ (1)"],
    hints: ["Use E = ½CV².", "Watch your units — μF, not F.", "12² = 144."],
    starAnswer: "E = ½CV² = ½ × 10μF × 12² = 720 μJ.",
  },
];

export type Stage = "setup" | "session" | "review" | "results";

export interface AnswerRecord {
  given: string | number | null;
  skipped: boolean;
  marksAwarded: number;
  timeSec: number;
}

export function fmtTime(s: number) {
  return `${Math.floor(s / 60).toString().padStart(2, "0")}:${(s % 60).toString().padStart(2, "0")}`;
}
