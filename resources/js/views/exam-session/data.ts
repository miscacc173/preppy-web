// Pure data + types for the Exam session flow, ported 1:1 from
// exam.session.tsx (framework-agnostic in the source already).
import type { QuestionVisualSpec } from "@/components/app/question-visual";

export interface Paper {
  id: string;
  name: string;
  minutes: number;
  questions: number;
}

export const PAPERS_BY_SUBJECT: Record<string, Paper[]> = {
  Physics: [
    { id: "p1", name: "Paper 1 — Multiple choice", minutes: 75, questions: 40 },
    { id: "p2", name: "Paper 2 — AS Structured", minutes: 75, questions: 8 },
    { id: "p4", name: "Paper 4 — A2 Structured", minutes: 120, questions: 12 },
  ],
  Chemistry: [
    { id: "p1", name: "Paper 1 — Multiple choice", minutes: 60, questions: 40 },
    { id: "p2", name: "Paper 2 — AS Structured", minutes: 75, questions: 8 },
    { id: "p4", name: "Paper 4 — A2 Structured", minutes: 120, questions: 10 },
  ],
  Mathematics: [
    { id: "p1", name: "Paper 1 — Pure 1", minutes: 105, questions: 10 },
    { id: "p3", name: "Paper 3 — Pure 3", minutes: 105, questions: 10 },
    { id: "p5", name: "Paper 5 — Mechanics", minutes: 75, questions: 7 },
  ],
  Biology: [
    { id: "p1", name: "Paper 1 — Multiple choice", minutes: 75, questions: 40 },
    { id: "p2", name: "Paper 2 — AS Structured", minutes: 75, questions: 8 },
    { id: "p4", name: "Paper 4 — A2 Structured", minutes: 120, questions: 12 },
  ],
  Economics: [
    { id: "p1", name: "Paper 1 — Multiple choice", minutes: 60, questions: 30 },
    { id: "p2", name: "Paper 2 — Data response & essays", minutes: 90, questions: 6 },
  ],
  Business: [
    { id: "p1", name: "Paper 1 — Short answer", minutes: 75, questions: 8 },
    { id: "p2", name: "Paper 2 — Case study", minutes: 90, questions: 6 },
  ],
};

export function papersForSubject(subject: string): Paper[] {
  if (!subject) return [];
  return PAPERS_BY_SUBJECT[subject] ?? [
    { id: "p1", name: "Paper 1 — Multiple choice", minutes: 60, questions: 40 },
    { id: "p2", name: "Paper 2 — Structured", minutes: 90, questions: 8 },
  ];
}

export interface ExamQ {
  q: string;
  visual?: QuestionVisualSpec;
  model: string;
  examiner: string;
  improvement: string;
  strengths: string[];
  marks: number;
}

export const SAMPLE_Q: ExamQ[] = [
  {
    q: "Define gravitational field strength and state its unit.",
    visual: { kind: "formula", caption: "Definition reference", formula: "g = F / m" },
    marks: 3,
    model: "Gravitational field strength is the gravitational force exerted per unit mass on a small test mass placed at that point. SI unit: N kg⁻¹ (equivalent to m s⁻²).",
    examiner: "Most candidates state the formula but omit 'per unit mass' or forget the unit. The definition must be a sentence, not just the equation.",
    improvement: "Always frame definitions as 'X per unit Y' and finish with the SI unit.",
    strengths: ["Correct formula", "Recognises vector nature"],
  },
  {
    q: "A 2 kg mass is dropped from 20 m. Calculate impact speed (g = 9.81 m/s²).",
    marks: 3,
    model: "Use v² = u² + 2as with u = 0, a = 9.81, s = 20. v² = 392.4 → v ≈ 19.8 m s⁻¹.",
    examiner: "Method marks awarded for correct kinematic equation; final mark requires a numerical answer with unit.",
    improvement: "Always include units on the final line and show the substituted equation.",
    strengths: ["Correct equation selected", "Substitution shown"],
  },
  {
    q: "The diagram shows a beam supported at point P with a 10 N weight at Q. Calculate the reaction force at P.",
    visual: { kind: "physics-beam", caption: "Beam supported at P, load at Q" },
    marks: 4,
    model: "Take moments about the second support: R_P × d = 10 × x → R_P = (10 × x) / d. Vertical equilibrium gives the second reaction.",
    examiner: "Common error is forgetting that vertical equilibrium and moment equilibrium must both be satisfied.",
    improvement: "State BOTH equilibrium conditions before computing.",
    strengths: ["Diagram interpretation", "Moments concept used"],
  },
  {
    q: "State the photoelectric equation and identify each term.",
    visual: { kind: "formula", caption: "Einstein's photoelectric equation", formula: "hf = φ + ½mv²" },
    marks: 3,
    model: "hf = φ + ½mv_max². Here hf is photon energy, φ is the work function (minimum energy to release an electron), ½mv_max² is the maximum kinetic energy of the emitted electron.",
    examiner: "Mark scheme requires naming every symbol, not just writing the equation.",
    improvement: "Pair each symbol with its meaning AND its unit.",
    strengths: ["Equation correctly recalled"],
  },
  {
    q: "Sketch the I–V curve for a filament lamp and explain its shape.",
    visual: { kind: "physics-iv-curve", caption: "Reference I–V axes" },
    marks: 4,
    model: "Curve starts linear, then flattens. As V rises, I rises and the filament heats up; resistance increases (lattice ions vibrate more, scattering electrons), so gradient I/V decreases.",
    examiner: "Top-band answers explicitly link temperature rise → resistance rise → gradient change.",
    improvement: "Always state the causal chain: temperature → resistance → gradient.",
    strengths: ["Curve drawn correctly", "Identifies non-ohmic behaviour"],
  },
  {
    q: "Calculate the energy released when a 0.5 kg block falls 8 m.",
    marks: 2,
    model: "E = mgh = 0.5 × 9.81 × 8 = 39.24 J.",
    examiner: "Straightforward; ensure unit (J) is included.",
    improvement: "Always show the substitution line.",
    strengths: ["Formula recall"],
  },
  {
    q: "Describe how the strong nuclear force differs from the electromagnetic force.",
    marks: 4,
    model:
      "Strong force acts between quarks/hadrons over very short range (~1 fm), is attractive at nucleon scale but repulsive at very short distance, and is independent of charge. EM force acts between charged particles over infinite range with 1/r² dependence and can be attractive or repulsive depending on sign of charges.",
    examiner: "Comparison must mention range, sign behaviour and which particles each force acts on.",
    improvement: "Use a 3-column compare: range / particles / sign behaviour.",
    strengths: ["Both forces named", "Range contrast attempted"],
  },
  {
    q: "A wave has frequency 50 Hz and wavelength 6.4 m. Find its speed.",
    marks: 2,
    model: "v = fλ = 50 × 6.4 = 320 m s⁻¹.",
    examiner: "Standard wave-equation recall; unit required.",
    improvement: "Always quote v = fλ before substituting.",
    strengths: ["Equation correct"],
  },
];

export type Stage = "setup" | "active" | "review" | "results" | "feedback";

export function fmtTime(s: number) {
  return `${Math.floor(s / 60).toString().padStart(2, "0")}:${(s % 60).toString().padStart(2, "0")}`;
}
